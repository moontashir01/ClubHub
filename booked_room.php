<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'connection.php';


$conn = new mysqli($db_server, $db_user, $db_pass, $db_name, $port);

if ($conn->connect_error) {
    die("Database Connection failed: " . $conn->connect_error);
}


if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && isset($_POST['booking_id'])) {
    $booking_id = $_POST['booking_id'];
    $action = $_POST['action'];

    // এখানে booking_id ধরে আপডেট করা হচ্ছে, যাতে স্পেসিফিক বুকিংটাই আপডেট হয়
    if ($action === 'approve') {
        $update_sql = "UPDATE room_bookings SET status='Approved' WHERE booking_id=?"; 
    } elseif ($action === 'reject') {
        $update_sql = "UPDATE room_bookings SET status='Rejected' WHERE booking_id=?";
    }

    if (isset($update_sql)) {
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("i", $booking_id);
        $stmt->execute();
        
        header("Location: booked_room.php");
        exit();
    }
}


$pending_count = $conn->query("SELECT COUNT(*) as count FROM room_bookings WHERE status='Pending' OR status='pending'")->fetch_assoc()['count'];
$approved_count = $conn->query("SELECT COUNT(*) as count FROM room_bookings WHERE status='Approved' OR status='approved'")->fetch_assoc()['count'];
$total_count = $conn->query("SELECT COUNT(*) as count FROM room_bookings")->fetch_assoc()['count'];


$sql = "SELECT * FROM room_bookings ORDER BY booking_date DESC, start_time ASC";
$result = $conn->query($sql);


$availability_sql = "SELECT * FROM room_bookings WHERE status='Approved' OR status='approved' ORDER BY booking_date ASC, start_time ASC";
$availability_result = $conn->query($availability_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Room Booking Management</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background-color: #0d1117; color: #c9d1d9; padding: 30px; }
        .container { max-width: 1300px; margin: 0 auto; display: flex; flex-direction: column; gap: 20px; }
        
        .top-bar { display: flex; align-items: center; margin-bottom: 10px; }
        .back-btn { background-color: #21262d; color: #c9d1d9; text-decoration: none; padding: 8px 16px; border-radius: 6px; border: 1px solid #30363d; font-size: 14px; transition: 0.3s; margin-right: 20px; }
        .back-btn:hover { background-color: #30363d; color: #fff; }
        h2 { color: #ffffff; font-size: 26px; font-weight: 600; }

        .stats-container { display: flex; gap: 20px; }
        .stat-card { flex: 1; background: #161b22; border: 1px solid #30363d; border-radius: 12px; padding: 20px; text-align: center; box-shadow: 0 4px 15px rgba(0,0,0,0.2); }
        .stat-card h3 { font-size: 13px; color: #8b949e; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px; }
        .stat-card .number { font-size: 32px; font-weight: 600; }
        .stat-card.pending .number { color: #d29922; }
        .stat-card.approved .number { color: #3fb950; }
        .stat-card.total .number { color: #58a6ff; }

        .main-layout { display: flex; gap: 20px; align-items: flex-start; }
        
        .table-section { flex: 2.5; background: #161b22; border-radius: 15px; border: 1px solid #30363d; padding: 20px; }
        
        .filter-tabs { display: flex; gap: 10px; margin-bottom: 20px; justify-content: center; }
        .filter-btn { padding: 8px 20px; border-radius: 20px; font-size: 14px; font-weight: 500; cursor: pointer; border: none; background: #21262d; color: #8b949e; transition: 0.3s; }
        .filter-btn.active, .filter-btn:hover { background: #ff477e; color: white; }

        table { width: 100%; border-collapse: collapse; text-align: left; }
        thead { background-color: #21262d; }
        th, td { padding: 14px 15px; border-bottom: 1px solid #30363d; font-size: 14px; }
        th { color: #8b949e; font-weight: 500; text-transform: uppercase; font-size: 12px; }
        tbody tr:hover { background-color: #1f242c; }
        .club-name { font-weight: 600; color: #58a6ff; }
        
        .badge { padding: 5px 10px; border-radius: 15px; font-size: 11px; font-weight: 600; display: inline-block; text-transform: uppercase;}
        .badge.pending { background: rgba(210, 153, 34, 0.15); color: #d29922; border: 1px solid rgba(210, 153, 34, 0.4); }
        .badge.approved { background: rgba(46, 160, 67, 0.15); color: #3fb950; border: 1px solid rgba(46, 160, 67, 0.4); }
        .badge.rejected { background: rgba(248, 81, 73, 0.15); color: #ff7b72; border: 1px solid rgba(248, 81, 73, 0.4); }
        
        .action-forms { display: flex; gap: 5px; }
        .btn { padding: 6px 12px; border-radius: 6px; font-size: 12px; cursor: pointer; border: none; font-weight: 500;}
        .btn-approve { background-color: #238636; color: white; }
        .btn-approve:hover { background-color: #2ea043; }
        .btn-reject { background-color: transparent; color: #ff7b72; border: 1px solid #ff7b72; }
        .btn-reject:hover { background-color: #ff7b72; color: white; }

        .availability-section { flex: 1; background: #161b22; border-radius: 15px; border: 1px solid #30363d; padding: 20px; position: sticky; top: 20px;}
        .availability-section h3 { color: #fff; font-size: 18px; margin-bottom: 15px; display: flex; align-items: center; gap: 10px;}
        .availability-section h3::before { content: '📅'; }
        
        .booked-slot { background: #21262d; border-left: 4px solid #3fb950; padding: 12px; border-radius: 6px; margin-bottom: 10px; }
        .booked-slot .room-title { font-weight: 600; color: #c9d1d9; font-size: 15px; }
        .booked-slot .time-info { font-size: 13px; color: #8b949e; margin-top: 5px; }
        .booked-slot .club-info { font-size: 12px; color: #58a6ff; margin-top: 3px; font-weight: 500;}
    </style>
</head>
<body>

<div class="container">
    <div class="top-bar">
        <a href="admin_dashboard.php" class="back-btn">&#8592; Back to Dashboard</a>
        <h2>Room Booking Management</h2>
    </div>

    <div class="stats-container">
        <div class="stat-card pending"><h3>Pending Requests</h3><div class="number"><?php echo $pending_count; ?></div></div>
        <div class="stat-card approved"><h3>Approved Rooms</h3><div class="number"><?php echo $approved_count; ?></div></div>
        <div class="stat-card total"><h3>Total Applications</h3><div class="number"><?php echo $total_count; ?></div></div>
    </div>

    <div class="main-layout">
        <div class="table-section">
            <div class="filter-tabs">
                <button class="filter-btn active" onclick="filterTable('all')">All</button>
                <button class="filter-btn" onclick="filterTable('pending')">Pending</button>
                <button class="filter-btn" onclick="filterTable('approved')">Approved</button>
                <button class="filter-btn" onclick="filterTable('rejected')">Rejected</button>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Club Name</th>
                        <th>Room</th>
                        <th>Date & Time</th>
                        <th>Duration</th>
                        <th>Purpose</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="bookingTableBody">
                    <?php
                    if ($result && $result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            $status = $row['status'] ?? 'Pending';
                            $badgeClass = strtolower($status);
                            
                            // এখানেও booking_id ঠিক করা হয়েছে
                            $bookingID = $row['booking_id'] ?? 0; 
                            
                            $clubName = htmlspecialchars($row['club_name'] ?? $row['club'] ?? 'N/A');
                            $roomName = htmlspecialchars($row['room_name'] ?? $row['room_no'] ?? $row['room'] ?? $row['select_room'] ?? 'N/A'); 
                            $date = htmlspecialchars($row['booking_date'] ?? $row['date'] ?? 'N/A');
                            $time = htmlspecialchars($row['start_time'] ?? $row['time'] ?? 'N/A');
                            $duration = htmlspecialchars($row['duration'] ?? $row['time_duration'] ?? $row['booking_duration'] ?? 'N/A');
                            $purpose = htmlspecialchars($row['purpose'] ?? 'N/A');
                    ?>
                    <tr class="table-row" data-status="<?php echo strtolower($status); ?>">
                        <td class="club-name"><?php echo $clubName; ?></td>
                        <td><?php echo $roomName; ?></td>
                        <td>
                            <div><?php echo ($date !== 'N/A') ? date('d M, Y', strtotime($date)) : 'N/A'; ?></div>
                            <div style="font-size: 12px; color: #8b949e;"><?php echo ($time !== 'N/A') ? date('h:i A', strtotime($time)) : ''; ?></div>
                        </td>
                        <td><?php echo $duration; ?></td>
                        <td><?php echo $purpose; ?></td>
                        <td><span class="badge <?php echo $badgeClass; ?>"><?php echo ucfirst($status); ?></span></td>
                        <td>
                            <?php if(strtolower($status) == 'pending'): ?>
                                <div class="action-forms">
                                    <form method="POST" style="margin:0;">
                                        <input type="hidden" name="booking_id" value="<?php echo $bookingID; ?>">
                                        <button type="submit" name="action" value="approve" class="btn btn-approve">Approve</button>
                                    </form>
                                    <form method="POST" style="margin:0;">
                                        <input type="hidden" name="booking_id" value="<?php echo $bookingID; ?>">
                                        <button type="submit" name="action" value="reject" class="btn btn-reject">Reject</button>
                                    </form>
                                </div>
                            <?php else: ?>
                                <span style="font-size: 12px; color: #8b949e;">Reviewed</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php 
                        }
                    } else {
                        echo "<tr><td colspan='7' style='text-align: center; padding: 30px;'>No bookings found.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <div class="availability-section">
            <h3>Approved Schedule</h3>
            <p style="font-size: 13px; color: #8b949e; margin-bottom: 15px;">Check booked slots before approving new requests.</p>
            
            <div class="availability-list">
                <?php
                if ($availability_result && $availability_result->num_rows > 0) {
                    while ($slot = $availability_result->fetch_assoc()) {
                        $bookedRoom = htmlspecialchars($slot['room_name'] ?? $slot['room_no'] ?? $slot['room'] ?? 'Unknown Room');
                        $bookedClub = htmlspecialchars($slot['club_name'] ?? $slot['club'] ?? 'Unknown Club');
                        $bDate = htmlspecialchars($slot['booking_date'] ?? $slot['date'] ?? '');
                        $bTime = htmlspecialchars($slot['start_time'] ?? $slot['time'] ?? '');
                        $bDur = htmlspecialchars($slot['duration'] ?? $slot['time_duration'] ?? $slot['booking_duration'] ?? '');
                        
                        $formattedDate = ($bDate) ? date('d M Y', strtotime($bDate)) : '';
                        $formattedTime = ($bTime) ? date('h:i A', strtotime($bTime)) : '';
                ?>
                <div class="booked-slot">
                    <div class="room-title"><?php echo $bookedRoom; ?></div>
                    <div class="time-info">📅 <?php echo $formattedDate; ?> | ⏰ <?php echo $formattedTime; ?> (<?php echo $bDur; ?>)</div>
                    <div class="club-info">Booked by: <?php echo $bookedClub; ?></div>
                </div>
                <?php 
                    }
                } else {
                    echo "<div style='color: #8b949e; font-size: 13px; text-align: center; padding: 20px 0;'>All rooms are currently available!</div>";
                }
                ?>
            </div>
        </div>
    </div>
</div>

<script>
    function filterTable(status) {
        const buttons = document.querySelectorAll('.filter-btn');
        buttons.forEach(btn => btn.classList.remove('active'));
        event.target.classList.add('active');

        const rows = document.querySelectorAll('.table-row');
        rows.forEach(row => {
            if (status === 'all' || row.getAttribute('data-status') === status) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }
</script>
</body>
</html>