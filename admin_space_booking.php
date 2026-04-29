<?php
session_start();
include 'connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['club_id']) && isset($_POST['booking_date']) && isset($_POST['slot'])) {
        $action = $_POST['action'];
        $target_club_id = intval($_POST['club_id']);
        $target_date = $_POST['booking_date'];
        $target_slot = intval($_POST['slot']);

        if ($action === 'approve') {
            // 1. Update the selected club's booking to 'Confirmed'
            $approve = $con->prepare("UPDATE space_bookings SET status = 'Confirmed' WHERE club_id = ? AND booking_date = ? AND slot = ?");
            $approve->bind_param("isi", $target_club_id, $target_date, $target_slot);
            $approve->execute();
            $approve->close();

            // 2. Delete any other 'Pending' requests for this exact date and slot (prevent double booking)
            $clear_others = $con->prepare("DELETE FROM space_bookings WHERE booking_date = ? AND slot = ? AND status = 'Pending'");
            $clear_others->bind_param("si", $target_date, $target_slot);
            $clear_others->execute();
            $clear_others->close();

        } elseif ($action === 'cancel') {
            // Delete the pending request entirely
            $delete = $con->prepare("DELETE FROM space_bookings WHERE club_id = ? AND booking_date = ? AND slot = ?");
            $delete->bind_param("isi", $target_club_id, $target_date, $target_slot);
            $delete->execute();
            $delete->close();
        }

        // Refresh the page to show updated list
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

// --- FETCH ALL PENDING BOOKINGS SORTED BY APPLIED TIME (EARLIEST FIRST) ---
$pending_bookings = [];
// We select 'created_at' and sort by it ascending
$query = "SELECT sb.booking_date, sb.slot, sb.club_id, sb.created_at, c.club_name 
          FROM space_bookings sb 
          JOIN clubs c ON sb.club_id = c.club_id 
          WHERE sb.status = 'Pending' 
          ORDER BY sb.created_at ASC";

$result = $con->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $pending_bookings[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Space Booking Approvals</title>
    <style>
        :root { --pink: #ff4d8d; --dark-bg: #0b0b13; --card: #161621; }
        body, html { margin: 0; padding: 0; height: 100%; background: var(--dark-bg); font-family: 'Segoe UI', sans-serif; color: white; }
        
        .container { max-width: 1100px; margin: 50px auto; padding: 20px; }
        
        h1 { color: var(--pink); text-transform: uppercase; letter-spacing: 2px; margin-bottom: 30px; text-align: center; }

        /* --- BACK BUTTON --- */
        .back-btn {
            background: #111; padding: 10px 18px; border-radius: 8px;
            border: 2px solid var(--pink); box-shadow: 0 0 10px rgba(255, 77, 141, 0.2);
            color: var(--pink); font-size: 12px; font-weight: bold; text-decoration: none;
            text-transform: uppercase; transition: all 0.3s ease; display: inline-block; margin-bottom: 20px;
        }
        .back-btn:hover { background: var(--pink); color: #fff; box-shadow: 0 0 15px rgba(255, 77, 141, 0.5); }

        /* --- TABLE STYLES --- */
        .table-wrapper { background: var(--card); border-radius: 12px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.5); border: 1px solid rgba(255,255,255,0.05); }
        table { width: 100%; border-collapse: collapse; }
        thead { background: rgba(255, 77, 141, 0.15); }
        th { padding: 18px; text-align: left; color: var(--pink); font-size: 13px; text-transform: uppercase; letter-spacing: 1px; border-bottom: 2px solid var(--pink); }
        td { padding: 18px; border-bottom: 1px solid rgba(255, 255, 255, 0.05); font-size: 15px; color: #ddd; vertical-align: middle; }
        tr:hover td { background: rgba(255, 255, 255, 0.02); color: white; }

        /* --- ACTION BUTTONS --- */
        .action-form { display: inline-block; margin: 0; }
        .btn { padding: 8px 15px; border-radius: 6px; font-size: 12px; font-weight: bold; border: none; cursor: pointer; text-transform: uppercase; transition: 0.3s; margin-right: 5px; }
        .btn-approve { background: rgba(46, 213, 115, 0.2); color: #2ed573; border: 1px solid #2ed573; }
        .btn-approve:hover { background: #2ed573; color: white; box-shadow: 0 0 15px rgba(46, 213, 115, 0.4); }
        
        .btn-cancel { background: rgba(255, 71, 87, 0.2); color: #ff4757; border: 1px solid #ff4757; }
        .btn-cancel:hover { background: #ff4757; color: white; box-shadow: 0 0 15px rgba(255, 71, 87, 0.4); }

        .empty-state { text-align: center; padding: 50px; color: #888; font-style: italic; font-size: 16px; }
        
        /* Time badge styling */
        .time-badge { background: #222230; padding: 6px 12px; border-radius: 20px; font-size: 12px; color: #00d2d3; border: 1px solid rgba(0, 210, 211, 0.3); display: inline-block; }
    </style>
</head>
<body>

<div class="container">
    <a href="admin_dashboard.php" class="back-btn">← Back to Dashboard</a>
    <h1>Pending Slot Approvals</h1>

    <div class="table-wrapper">
        <?php if (empty($pending_bookings)): ?>
            <div class="empty-state">No pending booking requests at the moment.</div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Applied Time</th>
                        <th>Target Date</th>
                        <th>Slot</th>
                        <th>Club Name</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pending_bookings as $booking): ?>
                        <tr>
                            <td>
                                <span class="time-badge">
                                    <?= date('M j, Y - g:i A', strtotime($booking['created_at'])) ?>
                                </span>
                            </td>
                            <td style="font-weight: bold; color: #fff;">
                                <?= date('F j, Y', strtotime($booking['booking_date'])) ?>
                            </td>
                            <td>Slot <?= htmlspecialchars($booking['slot']) ?></td>
                            <td style="color: #ff8800; font-weight: bold;"><?= htmlspecialchars($booking['club_name']) ?></td>
                            <td>
                                <form method="POST" class="action-form">
                                    <input type="hidden" name="action" value="approve">
                                    <input type="hidden" name="club_id" value="<?= $booking['club_id'] ?>">
                                    <input type="hidden" name="booking_date" value="<?= $booking['booking_date'] ?>">
                                    <input type="hidden" name="slot" value="<?= $booking['slot'] ?>">
                                    <button type="submit" class="btn btn-approve" onclick="return confirm('Approve this slot for <?= htmlspecialchars(addslashes($booking['club_name'])) ?>?');">Approve</button>
                                </form>

                                <form method="POST" class="action-form">
                                    <input type="hidden" name="action" value="cancel">
                                    <input type="hidden" name="club_id" value="<?= $booking['club_id'] ?>">
                                    <input type="hidden" name="booking_date" value="<?= $booking['booking_date'] ?>">
                                    <input type="hidden" name="slot" value="<?= $booking['slot'] ?>">
                                    <button type="submit" class="btn btn-cancel" onclick="return confirm('Reject and cancel this request?');">Reject</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

</body>
</html>