<?php
/**
 * fetch_notifications.php
 * 
 * AJAX API endpoint for the ClubHub notification system.
 * 
 * GET: Returns unread count + latest notifications as JSON.
 * POST: Marks notifications as read (action=mark_read).
 */
session_start();
include 'connection.php';

// Match PHP timezone to system/DB timezone to fix time-ago calculations
date_default_timezone_set('Asia/Dhaka');

header('Content-Type: application/json');

// Ensure the notifications table exists
$tableCheck = @mysqli_query($con, "SHOW TABLES LIKE 'notifications'");
if (!$tableCheck || mysqli_num_rows($tableCheck) === 0) {
    echo json_encode(['unread_count' => 0, 'notifications' => []]);
    exit;
}

// Determine caller type and their recipient_id
$recipientType = '';
$recipientId = '';

if (isset($_SESSION['AdminRole']) && $_SESSION['AdminRole'] !== '') {
    // Admin user
    $recipientType = 'admin';
    $recipientId = 'all';
} elseif (isset($_SESSION['club_id']) && intval($_SESSION['club_id']) > 0) {
    // Club user
    $recipientType = 'club';
    $recipientId = strval(intval($_SESSION['club_id']));
} elseif (isset($_SESSION['Email'])) {
    // Try to resolve club_id from session email
    $email = $_SESSION['Email'];
    $clubQuery = mysqli_query($con, "
        SELECT clubs.club_id
        FROM `user`
        INNER JOIN students ON `user`.email = students.student_email
        INNER JOIN club_members ON club_members.student_id = students.student_id
        INNER JOIN clubs ON club_members.club_id = clubs.club_id
        WHERE `user`.email = '" . mysqli_real_escape_string($con, $email) . "'
        AND club_members.active = 1
        LIMIT 1
    ");
    if ($clubQuery && $row = mysqli_fetch_assoc($clubQuery)) {
        $recipientType = 'club';
        $recipientId = strval(intval($row['club_id']));
        $_SESSION['club_id'] = intval($row['club_id']);
    }
}

if ($recipientType === '' || $recipientId === '') {
    echo json_encode(['unread_count' => 0, 'notifications' => []]);
    exit;
}

// Handle POST: mark notifications as read
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'mark_read') {
        $stmt = $con->prepare("
            UPDATE notifications 
            SET is_read = 1 
            WHERE recipient_type = ? AND recipient_id = ? AND is_read = 0
        ");
        if ($stmt) {
            $stmt->bind_param("ss", $recipientType, $recipientId);
            $stmt->execute();
            $stmt->close();
        }
        echo json_encode(['success' => true]);
        exit;
    }
    
    if ($action === 'mark_single_read') {
        $notifId = intval($_POST['notification_id'] ?? 0);
        if ($notifId > 0) {
            $stmt = $con->prepare("
                UPDATE notifications 
                SET is_read = 1 
                WHERE notification_id = ? AND recipient_type = ? AND recipient_id = ?
            ");
            if ($stmt) {
                $stmt->bind_param("iss", $notifId, $recipientType, $recipientId);
                $stmt->execute();
                $stmt->close();
            }
        }
        echo json_encode(['success' => true]);
        exit;
    }
}

// Handle GET: fetch notifications
// Get unread count
$unreadCount = 0;
$countStmt = $con->prepare("
    SELECT COUNT(*) AS cnt 
    FROM notifications 
    WHERE recipient_type = ? AND recipient_id = ? AND is_read = 0
");
if ($countStmt) {
    $countStmt->bind_param("ss", $recipientType, $recipientId);
    $countStmt->execute();
    $countRow = $countStmt->get_result()->fetch_assoc();
    $unreadCount = intval($countRow['cnt'] ?? 0);
    $countStmt->close();
}

// Get latest 20 notifications (both read and unread)
$notifications = [];
$listStmt = $con->prepare("
    SELECT notification_id, message, link, is_read, created_at
    FROM notifications 
    WHERE recipient_type = ? AND recipient_id = ?
    ORDER BY created_at DESC 
    LIMIT 20
");
if ($listStmt) {
    $listStmt->bind_param("ss", $recipientType, $recipientId);
    $listStmt->execute();
    $result = $listStmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $notifications[] = [
            'id'         => intval($row['notification_id']),
            'message'    => $row['message'],
            'is_read'    => intval($row['is_read']),
            'created_at' => $row['created_at'],
            'time_ago'   => timeAgo($row['created_at'])
        ];
    }
    $listStmt->close();
}

echo json_encode([
    'unread_count'  => $unreadCount,
    'notifications' => $notifications
]);

/**
 * Convert a timestamp to a human-readable "time ago" string.
 */
function timeAgo(string $datetime): string {
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);
    
    if ($diff->y > 0) return $diff->y . 'y ago';
    if ($diff->m > 0) return $diff->m . 'mo ago';
    if ($diff->d > 0) return $diff->d . 'd ago';
    if ($diff->h > 0) return $diff->h . 'h ago';
    if ($diff->i > 0) return $diff->i . 'm ago';
    return 'Just now';
}
