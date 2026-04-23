<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Require PHPMailer files
require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

function sendVerificationEmail($email, $otp) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; // Set your SMTP server
        $mail->SMTPAuth   = true;
        $mail->Username   = 'moontashir.azim@gmail.com'; // SMTP username
        $mail->Password   = 'kfwj gdhb xlct jhtc'; // SMTP password (use App Passwords for Gmail)
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Recipients
        $mail->setFrom('moontashir.azim@gmail.com', 'ClubHub');
        $mail->addAddress($email);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'ClubHub - Verify Your Account';
        
        // Clean HTML Email Template
        $mail->Body    = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px; background-color: #f9f9f9;'>
                <h2 style='text-align: center; color: #ff4d8d;'>ClubHub Verification</h2>
                <p style='font-size: 16px; color: #333;'>Hello,</p>
                <p style='font-size: 16px; color: #333;'>Thank you for registering at ClubHub. Please use the following One-Time Password (OTP) to verify your account.</p>
                <div style='text-align: center; margin: 30px 0;'>
                    <span style='font-size: 32px; font-weight: bold; background: #fff; padding: 10px 20px; border: 2px dashed #ff4d8d; color: #ff4d8d; border-radius: 5px;'>$otp</span>
                </div>
                <p style='font-size: 14px; color: #666;'>This code is valid for 5 minutes.</p>
                <p style='font-size: 14px; color: #666;'><em>Please wait at least 60 seconds before requesting a new code.</em></p>
                <br>
                <p style='font-size: 14px; color: #333;'>Best Regards,<br><strong>ClubHub Team</strong></p>
            </div>
        ";
        
        $mail->AltBody = "Your ClubHub verification OTP is: $otp. It is valid for 5 minutes.";

        $mail->send();
        return true;
    } catch (Exception $e) {
        // You can log the error: $mail->ErrorInfo
        return false;
    }
}
?>
