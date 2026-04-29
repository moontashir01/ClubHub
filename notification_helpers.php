<?php
/**
 * notification_helpers.php
 * 
 * Centralized helper functions for the ClubHub notification system.
 * Include this file wherever you need to create notifications.
 */

require_once __DIR__ . '/connection.php';
require_once __DIR__ . '/mailer.php';

/**
 * Ensure the notifications table exists (auto-create on first use).
 */
function ensureNotificationsTable(mysqli $con): void {
    static $checked = false;
    if ($checked) return;
    
    $tableCheck = @mysqli_query($con, "SHOW TABLES LIKE 'notifications'");
    if (!$tableCheck || mysqli_num_rows($tableCheck) === 0) {
        $sql = "CREATE TABLE IF NOT EXISTS `notifications` (
            `notification_id` INT(11) NOT NULL AUTO_INCREMENT,
            `recipient_type` ENUM('admin','club') NOT NULL DEFAULT 'admin',
            `recipient_id` VARCHAR(50) NOT NULL,
            `message` TEXT NOT NULL,
            `link` VARCHAR(255) DEFAULT NULL,
            `is_read` TINYINT(1) NOT NULL DEFAULT 0,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`notification_id`),
            KEY `idx_recipient` (`recipient_type`, `recipient_id`, `is_read`),
            KEY `idx_created` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
        @mysqli_query($con, $sql);
    }
    $checked = true;
}

/**
 * Insert a notification for the admin side.
 * 
 * @param mysqli $con       Database connection
 * @param string $message   Notification text
 * @param string $link      Optional link (e.g. 'securityapproval.php')
 */
function notifyAdmin(mysqli $con, string $message, string $link = ''): void {
    ensureNotificationsTable($con);
    
    $stmt = $con->prepare("INSERT INTO notifications (recipient_type, recipient_id, message, link) VALUES ('admin', 'all', ?, ?)");
    if ($stmt) {
        $linkVal = $link ?: null;
        $stmt->bind_param("ss", $message, $linkVal);
        $stmt->execute();
        $stmt->close();
    }
}

/**
 * Insert a notification for a specific club AND email EB members.
 * 
 * @param mysqli $con       Database connection
 * @param int    $clubId    Target club ID
 * @param string $message   Notification text
 * @param string $link      Optional link (e.g. 'eventlogs.php')
 */
function notifyClub(mysqli $con, int $clubId, string $message, string $link = ''): void {
    ensureNotificationsTable($con);
    
    $recipientId = strval($clubId);
    $stmt = $con->prepare("INSERT INTO notifications (recipient_type, recipient_id, message, link) VALUES ('club', ?, ?, ?)");
    if ($stmt) {
        $linkVal = $link ?: null;
        $stmt->bind_param("sss", $recipientId, $message, $linkVal);
        $stmt->execute();
        $stmt->close();
    }
    
    // Send email to EB members of this club
    sendNotificationEmail($con, $clubId, $message);
}

/**
 * Send notification email to all Executive Body (EB) members of a club.
 * Uses the existing PHPMailer setup from mailer.php.
 * 
 * @param mysqli $con       Database connection
 * @param int    $clubId    Club ID
 * @param string $message   The notification message to email
 */
function sendNotificationEmail(mysqli $con, int $clubId, string $message): void {
    // Find all EB members for this club
    $stmt = $con->prepare("
        SELECT s.student_email, s.full_name
        FROM club_members cm
        INNER JOIN students s ON cm.student_id = s.student_id
        WHERE cm.club_id = ?
          AND cm.Role LIKE 'EB-%'
          AND cm.active = 1
    ");
    
    if (!$stmt) return;
    
    $stmt->bind_param("i", $clubId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $recipients = [];
    while ($row = $result->fetch_assoc()) {
        $recipients[] = $row;
    }
    $stmt->close();
    
    if (empty($recipients)) return;
    
    // Get club name for the email subject
    $clubName = 'Your Club';
    $nameStmt = $con->prepare("SELECT club_name FROM clubs WHERE club_id = ? LIMIT 1");
    if ($nameStmt) {
        $nameStmt->bind_param("i", $clubId);
        $nameStmt->execute();
        $nameRow = $nameStmt->get_result()->fetch_assoc();
        if ($nameRow) {
            $clubName = $nameRow['club_name'];
        }
        $nameStmt->close();
    }
    
    // Use PHPMailer to send to each EB member
    foreach ($recipients as $recipient) {
        try {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'moontashir.azim@gmail.com';
            $mail->Password   = 'kfwj gdhb xlct jhtc';
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            
            $mail->setFrom('moontashir.azim@gmail.com', 'ClubHub Notifications');
            $mail->addAddress($recipient['student_email'], $recipient['full_name']);
            
            $mail->isHTML(true);
            $mail->Subject = "ClubHub - Notification for $clubName";
            
            $escapedMessage = htmlspecialchars($message);
            $escapedClub = htmlspecialchars($clubName);
            $escapedName = htmlspecialchars($recipient['full_name']);
            
            $mail->Body = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px; background-color: #f9f9f9;'>
                    <h2 style='text-align: center; color: #ff4d8d;'>ClubHub Notification</h2>
                    <p style='font-size: 16px; color: #333;'>Hello {$escapedName},</p>
                    <p style='font-size: 16px; color: #333;'>You have a new notification for <strong>{$escapedClub}</strong>:</p>
                    <div style='text-align: center; margin: 30px 0; padding: 20px; background: #fff; border: 1px solid #eee; border-radius: 8px;'>
                        <p style='font-size: 18px; font-weight: bold; color: #333; margin: 0;'>{$escapedMessage}</p>
                    </div>
                    <p style='font-size: 14px; color: #666;'>Log in to ClubHub to take action.</p>
                    <br>
                    <p style='font-size: 14px; color: #333;'>Best Regards,<br><strong>ClubHub Team</strong></p>
                </div>
            ";
            
            $mail->AltBody = "ClubHub Notification for $clubName: $message";
            
            $mail->send();
        } catch (\Exception $e) {
            // Silently fail for email - notification is still saved in DB
            // error_log("ClubHub email error: " . $e->getMessage());
        }
    }
}
