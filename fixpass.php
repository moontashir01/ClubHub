<?php
include 'connection.php';

$plain_password = '123'; 
$hashed_password = password_hash($plain_password, PASSWORD_DEFAULT);
$email = 'ridwanul.hoque01@northsouth.edu';

$sql = "UPDATE user SET Password = ? WHERE email = ?";
$stmt = $con->prepare($sql);
$stmt->bind_param("ss", $hashed_password, $email);

if ($stmt->execute()) {
    echo "Password updated and hashed successfully! Now try logging in with: password123";
} else {
    echo "Error updating database: " . $con->error;
}
?>