<?php
session_start();
include 'connection.php';
include 'mailer.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);

    if (isset($con)) {
        // Check if email exists
        $stmt = $con->prepare("SELECT email FROM user WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            // Generate OTP
            $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
            $otp_expiry = date('Y-m-d H:i:s', strtotime('+5 minutes'));

            // Update user with OTP
            $update_stmt = $con->prepare("UPDATE user SET otp_code = ?, otp_expiry = ? WHERE email = ?");
            $update_stmt->bind_param("sss", $otp, $otp_expiry, $email);

            if ($update_stmt->execute()) {
                // Send email
                if (sendVerificationEmail($email, $otp, 'reset')) {
                    $_SESSION['auth_mode'] = 'reset_password';
                    header("Location: verify.php?email=" . urlencode($email));
                    exit();
                } else {
                    $error = "Failed to send OTP email.";
                }
            } else {
                $error = "Database error while generating OTP.";
            }
        } else {
            // For security, don't explicitly say email doesn't exist, but here we can be helpful
            $error = "No account found with that email address.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | ClubHub</title>
    <style>
        body { background: #0b0b13; color: white; font-family: 'Segoe UI', sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .verify-box { background: #161621; padding: 40px; border-radius: 24px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); text-align: center; width: 100%; max-width: 400px; }
        h2 { color: #ff4d8d; margin-bottom: 20px; }
        .input-group { margin-bottom: 20px; text-align: left; }
        .input-group label { display: block; margin-bottom: 8px; font-size: 0.8rem; color: #888; }
        .input-group input { width: 100%; padding: 12px; background: #0b0b13; border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white; outline: none; box-sizing: border-box; font-size: 1rem; }
        .input-group input:focus { border-color: #ff4d8d; }
        .btn { width: 100%; padding: 14px; background: #ff4d8d; color: white; border: none; border-radius: 12px; font-weight: bold; cursor: pointer; transition: 0.3s; font-size: 1rem; }
        .btn:hover { background: #ff3377; box-shadow: 0 5px 15px rgba(255, 77, 141, 0.4); }
        .alert { padding: 10px; border-radius: 8px; margin-bottom: 20px; font-size: 0.9rem; }
        .alert-error { background: rgba(255, 77, 77, 0.1); border: 1px solid rgba(255, 77, 77, 0.3); color: #ff4d4d; }
    </style>
</head>
<body>
    <div class="verify-box">
        <h2>Forgot Password</h2>
        <p style="color: #bbb; margin-bottom: 30px; font-size: 0.9rem;">Enter your email to receive a password reset OTP.</p>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="forgot_password.php">
            <div class="input-group">
                <label>Email Address</label>
                <input type="email" name="email" required autocomplete="email">
            </div>
            <button type="submit" class="btn">Send OTP</button>
        </form>
        
        <p style="margin-top: 20px; font-size: 0.85rem; color: #888;">
            Remembered your password? <a href="homepage.php" style="color: #ff4d8d; text-decoration: none;">Log In</a>
        </p>
    </div>
</body>
</html>
