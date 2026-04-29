<?php
session_start();
include 'connection.php';
require_once 'notification_helpers.php';



$messageColumnCheck = mysqli_query($con, "SHOW COLUMNS FROM events LIKE 'security_message'");
if ($messageColumnCheck && mysqli_num_rows($messageColumnCheck) === 0) {
    @mysqli_query($con, "ALTER TABLE events ADD COLUMN security_message TEXT NULL AFTER security_clearance");
}

mysqli_query($con, "
    UPDATE events
    SET event_availablity = CASE
        WHEN COALESCE(security_clearance, 'Pending') = 'Approved' THEN 1
        ELSE 0
    END
");



$flash = '';
$flashType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $eventId = intval($_POST['event_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $rejectReason = trim($_POST['reject_reason'] ?? '');

    if ($eventId > 0 && in_array($action, ['approve', 'reject'], true)) {
        if ($action === 'approve') {
            $approveStmt = $con->prepare("
                UPDATE events
                SET
                    security_clearance = 'Approved',
                    security_message = NULL,
                    event_availablity = 1
                WHERE event_id = ?
            ");
            $approveStmt->bind_param("i", $eventId);
            $approveStmt->execute();
            $flash = "Event approved successfully.";

            // Notify the club that their event was approved
            $eventInfoStmt = $con->prepare("SELECT e.event_name, e.club_id FROM events e WHERE e.event_id = ?");
            if ($eventInfoStmt) {
                $eventInfoStmt->bind_param("i", $eventId);
                $eventInfoStmt->execute();
                $eventInfo = $eventInfoStmt->get_result()->fetch_assoc();
                if ($eventInfo && $eventInfo['club_id']) {
                    notifyClub(
                        $con,
                        intval($eventInfo['club_id']),
                        "✅ Security Clearance Approved for \"" . $eventInfo['event_name'] . "\".",
                        'eventlogs.php'
                    );
                }
                $eventInfoStmt->close();
            }
        } else {
            if ($rejectReason === '') {
                $flash = "Rejection reason is required.";
                $flashType = 'danger';
            } else {
            $rejectStmt = $con->prepare("
                UPDATE events
                SET
                    security_clearance = 'Rejected',
                    security_message = ?,
                    event_availablity = 0
                WHERE event_id = ?
            ");
            $rejectStmt->bind_param("si", $rejectReason, $eventId);
            $rejectStmt->execute();
            $flash = "Event rejected successfully.";
            $flashType = 'warning';

            // Notify the club that their event was rejected
            $eventInfoStmt = $con->prepare("SELECT e.event_name, e.club_id FROM events e WHERE e.event_id = ?");
            if ($eventInfoStmt) {
                $eventInfoStmt->bind_param("i", $eventId);
                $eventInfoStmt->execute();
                $eventInfo = $eventInfoStmt->get_result()->fetch_assoc();
                if ($eventInfo && $eventInfo['club_id']) {
                    notifyClub(
                        $con,
                        intval($eventInfo['club_id']),
                        "❌ Security Clearance Rejected for \"" . $eventInfo['event_name'] . "\". Reason: " . $rejectReason,
                        'eventlogs.php'
                    );
                }
                $eventInfoStmt->close();
            }
            }
        }
    } else {
        $flash = "Invalid request.";
        $flashType = 'danger';
    }
}

$pendingEvents = [];
$listStmt = $con->prepare("
    SELECT
        e.event_id,
        e.event_name,
        e.event_date,
        c.club_name
    FROM events e
    LEFT JOIN clubs c ON c.club_id = e.club_id
    WHERE COALESCE(e.security_clearance, 'Pending') = 'Pending'
    ORDER BY e.event_date ASC, e.event_id ASC
");

if ($listStmt && $listStmt->execute()) {
    $result = $listStmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $pendingEvents[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Approval | ClubHub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f6f8fb; }
        .wrap { max-width: 1100px; margin: 40px auto; padding: 0 16px; }
        .card-shadow { box-shadow: 0 12px 30px rgba(15, 23, 42, 0.08); border: none; }
        .table td, .table th { vertical-align: middle; }
        .action-group { display: flex; gap: 8px; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-1">Security Clearance Queue</h2>
                <p class="text-muted mb-0">Review events waiting for security approval.</p>
            </div>
            <a href="admin_dashboard.php" class="btn btn-outline-secondary">Back to Dashboard</a>
        </div>

        <?php if ($flash): ?>
            <div class="alert alert-<?php echo htmlspecialchars($flashType); ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($flash); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card card-shadow">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Event Name</th>
                                <th>Created By</th>
                                <th>Event Date</th>
                                <th style="width: 190px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($pendingEvents)): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">No pending events for security approval.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($pendingEvents as $event): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($event['event_name']); ?></td>
                                        <td><?php echo htmlspecialchars($event['club_name'] ?? 'Unknown Club'); ?></td>
                                        <td>
                                            <?php
                                                if (!empty($event['event_date'])) {
                                                    echo htmlspecialchars(date("d M Y, h:i A", strtotime($event['event_date'])));
                                                } else {
                                                    echo "TBD";
                                                }
                                            ?>
                                        </td>
                                        <td>
                                            <div class="action-group">
                                                <form method="post" class="m-0">
                                                    <input type="hidden" name="event_id" value="<?php echo intval($event['event_id']); ?>">
                                                    <input type="hidden" name="action" value="approve">
                                                    <button type="submit" class="btn btn-sm btn-success">Approve</button>
                                                </form>
                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-danger reject-btn"
                                                    data-event-id="<?php echo intval($event['event_id']); ?>"
                                                    data-event-name="<?php echo htmlspecialchars($event['event_name'], ENT_QUOTES); ?>"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#rejectModal"
                                                >
                                                    Reject
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="post">
                    <div class="modal-header">
                        <h5 class="modal-title">Reject Event</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-2">Please provide a reason for rejection:</p>
                        <div class="small text-muted mb-2" id="rejectEventName"></div>
                        <input type="hidden" name="event_id" id="rejectEventId">
                        <input type="hidden" name="action" value="reject">
                        <textarea
                            class="form-control"
                            name="reject_reason"
                            id="rejectReason"
                            rows="4"
                            maxlength="1000"
                            placeholder="Write why this event was rejected..."
                            required
                        ></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Confirm Rejection</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const rejectButtons = document.querySelectorAll('.reject-btn');
        const rejectEventIdInput = document.getElementById('rejectEventId');
        const rejectEventNameText = document.getElementById('rejectEventName');
        const rejectReasonInput = document.getElementById('rejectReason');

        rejectButtons.forEach((button) => {
            button.addEventListener('click', () => {
                rejectEventIdInput.value = button.dataset.eventId || '';
                rejectEventNameText.textContent = button.dataset.eventName ? `Event: ${button.dataset.eventName}` : '';
                rejectReasonInput.value = '';
                rejectReasonInput.focus();
            });
        });
    </script>
</body>
</html>
