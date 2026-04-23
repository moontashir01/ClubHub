<?php
session_start();
include 'connection.php';

// Check if user has permission to reset password
if (!isset($_SESSION['can_reset']) || $_SESSION['can_reset'] !== true || !isset($_SESSION['reset_email'])) {
    header("Location: homepage.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        $email = $_SESSION['reset_email'];
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        if (isset($con)) {
            $stmt = $con->prepare("UPDATE user SET password = ? WHERE email = ?");
            $stmt->bind_param("ss", $hashed_password, $email);

            if ($stmt->execute()) {
                // Clear reset sessions
                unset($_SESSION['can_reset']);
                unset($_SESSION['reset_email']);
                unset($_SESSION['auth_mode']);

                $_SESSION['msg'] = "Password reset successfully! Please log in.";
                $_SESSION['msg_type'] = "success";
                header("Location: homepage.php");
                exit();
            } else {
                $error = "Failed to update password.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password | ClubHub</title>
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
        <h2>Reset Password</h2>
        <p style="color: #bbb; margin-bottom: 30px; font-size: 0.9rem;">Enter your new password below.</p>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="reset_password.php">
            <div class="input-group">
                <label>New Password</label>
                <input type="password" name="new_password" required>
            </div>
            <div class="input-group">
                <label>Confirm New Password</label>
                <input type="password" name="confirm_password" required>
            </div>
            <button type="submit" class="btn">Update Password</button>
        </form>
    </div>
</body>
</html>
