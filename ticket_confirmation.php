<?php
// --- 1. PHP BACKEND LOGIC ---
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['ajax_request'])) {
    
    try {
        if (!file_exists('PHPMailer/PHPMailer.php')) {
            throw new Exception("PHPMailer files not found in 'PHPMailer/' folder.");
        }

        require 'PHPMailer/Exception.php';
        require 'PHPMailer/PHPMailer.php';
        require 'PHPMailer/SMTP.php';

        $userName  = $_POST['name'] ?? 'Guest';
        $userEmail = $_POST['email'] ?? '';
        $ticketID  = "TKT-" . strtoupper(uniqid());

        $mail = new PHPMailer(true);

        // SMTP Configuration
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'sagorsrijoy123@gmail.com'; 
        $mail->Password   = 'wfuy ibks mwjq muge'; 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Email Settings
        $mail->setFrom('sagorsrijoy123@gmail.com', 'Event Team');
        $mail->addAddress($userEmail, $userName);

        // --- ATTACHMENT SECTION (PNG/JPG/JPEG) ---
        // Change 'ticket.jpg' to whatever your image file is named!
        $imagePath = 'image/IMG_8848.jpeg'; 
        
        if (file_exists($imagePath)) {
            $mail->addAttachment($imagePath, 'Event_Ticket_Image.jpg'); 
        }

        // Email Content (SIMPLE TEXT STYLE)
        $mail->isHTML(true);
        $mail->Subject = "Your Event Registration: $ticketID";
        $mail->Body    = "
            <div style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
                <p>Hello <strong>$userName</strong>,</p>
                <p>Thank you for registering for our event. Your registration is now confirmed.</p>
                <p><strong>Your Ticket ID:</strong> $ticketID</p>
                <p>We have attached your ticket image to this email. Please keep it saved on your phone to show at the entrance.</p>
                <br>
                <p>Best regards,<br>The Event Team</p>
            </div>";

        $mail->send();
        echo json_encode(["status" => "success", "message" => "Success! Image sent to your email."]);
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => "Error: " . $e->getMessage()]);
    }
    exit; 
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Registration</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; background: #f0f2f5; margin: 0; }
        .card { background: white; padding: 2.5rem; border-radius: 12px; box-shadow: 0 8px 24px rgba(0,0,0,0.1); width: 100%; max-width: 400px; text-align: center; }
        input { width: 100%; padding: 12px; margin: 8px 0; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; }
        button { width: 100%; padding: 12px; background: #2d89ef; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; }
        button:disabled { background: #ccc; }
        #status { margin-top: 20px; font-size: 14px; }
    </style>
</head>
<body>

<div class="card">
    <h2>Registration</h2>
    <p>Enter details to receive your ticket image.</p>
    <form id="ticketForm">
        <input type="text" id="userName" placeholder="Full Name" required>
        <input type="email" id="userEmail" placeholder="Email Address" required>
        <button type="submit" id="submitBtn">Get My Ticket</button>
    </form>
    <div id="status"></div>
</div>

<script>
document.getElementById('ticketForm').onsubmit = function(e) {
    e.preventDefault();
    const btn = document.getElementById('submitBtn');
    const status = document.getElementById('status');
    btn.disabled = true;
    btn.innerText = "Sending...";

    const formData = new FormData();
    formData.append('ajax_request', '1');
    formData.append('name', document.getElementById('userName').value);
    formData.append('email', document.getElementById('userEmail').value);

    fetch(window.location.href, { method: 'POST', body: formData })
    .then(response => response.json())
    .then(data => {
        status.innerText = data.message;
        status.style.color = (data.status === "success") ? "#28a745" : "#dc3545";
        btn.disabled = false;
        btn.innerText = "Get My Ticket";
        if(data.status === "success") document.getElementById('ticketForm').reset();
    })
    .catch(error => {
        status.innerText = "Error: Check if your image file name is correct.";
        status.style.color = "#dc3545";
        btn.disabled = false;
        btn.innerText = "Get My Ticket";
    });
};
</script>
</body>
</html>