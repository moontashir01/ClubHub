<?php
session_start();
include 'connection.php';

$email = isset($_GET['email']) ? $_GET['email'] : '';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $otp = $_POST['otp'];
    
    if (isset($con)) {
        // Query the DB to check if the code matches AND otp_expiry is greater than NOW()
        $stmt = $con->prepare("SELECT otp_code, otp_expiry, is_verified FROM user WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $row = $result->fetch_assoc();
            
            $is_reset_mode = (isset($_SESSION['auth_mode']) && $_SESSION['auth_mode'] === 'reset_password');
            
            if (!$is_reset_mode && $row['is_verified'] == 1) {
                $error = "Account is already verified. You can log in.";
            } elseif ($row['otp_code'] === $otp) {
                // Check if expired
                $expiry_time = strtotime($row['otp_expiry']);
                $current_time = time();
                
                if ($current_time <= $expiry_time) {
                    // Valid and not expired
                    if (isset($_SESSION['auth_mode']) && $_SESSION['auth_mode'] === 'reset_password') {
                        // For reset password, clear OTP and grant reset access
                        $update_stmt = $con->prepare("UPDATE user SET otp_code = NULL, otp_expiry = NULL WHERE email = ?");
                        $update_stmt->bind_param("s", $email);
                        if ($update_stmt->execute()) {
                            $_SESSION['can_reset'] = true;
                            $_SESSION['reset_email'] = $email;
                            header("Location: reset_password.php");
                            exit();
                        } else {
                            $error = "Error updating verification status.";
                        }
                    } else {
                        // Registration case: Set is_verified = 1 and clear the otp_code
                        $update_stmt = $con->prepare("UPDATE user SET is_verified = 1, otp_code = NULL, otp_expiry = NULL WHERE email = ?");
                        $update_stmt->bind_param("s", $email);
                        if ($update_stmt->execute()) {
                            $success = "Verification successful! You can now log in.";
                            // You can automatically log them in here if desired or redirect to homepage
                            $_SESSION['msg'] = "Account verified successfully! Please log in.";
                            $_SESSION['msg_type'] = "success";
                            header("Location: homepage.php");
                            exit();
                        } else {
                            $error = "Error updating verification status.";
                        }
                    }
                } else {
                    $error = "The OTP has expired. Please request a new one.";
                }
            } else {
                $error = "Invalid OTP code.";
            }
        } else {
            $error = "User not found.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Account | ClubHub</title>
    <style>
        body { background: #0b0b13; color: white; font-family: 'Segoe UI', sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .verify-box { background: #161621; padding: 40px; border-radius: 24px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); text-align: center; width: 100%; max-width: 400px; }
        h2 { color: #ff4d8d; margin-bottom: 20px; }
        .input-group { margin-bottom: 20px; text-align: left; }
        .input-group label { display: block; margin-bottom: 8px; font-size: 0.8rem; color: #888; }
        .input-group input { width: 100%; padding: 12px; background: #0b0b13; border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white; outline: none; box-sizing: border-box; font-size: 1.2rem; letter-spacing: 5px; text-align: center; }
        .input-group input:focus { border-color: #ff4d8d; }
        .btn { width: 100%; padding: 14px; background: #ff4d8d; color: white; border: none; border-radius: 12px; font-weight: bold; cursor: pointer; transition: 0.3s; font-size: 1rem; }
        .btn:hover { background: #ff3377; box-shadow: 0 5px 15px rgba(255, 77, 141, 0.4); }
        .alert { padding: 10px; border-radius: 8px; margin-bottom: 20px; font-size: 0.9rem; }
        .alert-error { background: rgba(255, 77, 77, 0.1); border: 1px solid rgba(255, 77, 77, 0.3); color: #ff4d4d; }
        .alert-success { background: rgba(77, 255, 141, 0.1); border: 1px solid rgba(77, 255, 141, 0.3); color: #4dff8d; }
    </style>
</head>
<body>
    <div class="verify-box">
        <?php if (isset($_SESSION['auth_mode']) && $_SESSION['auth_mode'] === 'reset_password'): ?>
            <h2>Password Reset</h2>
            <p style="color: #bbb; margin-bottom: 30px; font-size: 0.9rem;">We've sent a 6-digit OTP to your email. Please enter it below to reset your password.</p>
        <?php else: ?>
            <h2>Account Verification</h2>
            <p style="color: #bbb; margin-bottom: 30px; font-size: 0.9rem;">We've sent a 6-digit OTP to your email. Please enter it below to verify your account.</p>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <form method="POST" action="verify.php">
            <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
            <div class="input-group">
                <label>6-Digit OTP</label>
                <input type="text" name="otp" required minlength="6" maxlength="6" pattern="\d{6}" autocomplete="off">
            </div>
            <button type="submit" class="btn"><?php echo (isset($_SESSION['auth_mode']) && $_SESSION['auth_mode'] === 'reset_password') ? 'Verify OTP' : 'Verify Account'; ?></button>
        </form>
        
        <p style="margin-top: 20px; font-size: 0.85rem; color: #888;">
            Didn't receive the code? <br>
            <span style="color: #ff4d8d;">Wait 60 seconds to resend.</span>
        </p>
    </div>
</body>
</html>
