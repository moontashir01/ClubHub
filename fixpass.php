<?php
include 'connection.php';

<<<<<<< HEAD
$plain_password = 'password123'; // This is what you will type in the login box
$hashed_password = password_hash($plain_password, PASSWORD_DEFAULT);
$email = 'testuser@example.com';
=======
$plain_password = '123'; 
$hashed_password = password_hash($plain_password, PASSWORD_DEFAULT);
$email = 'test@northsouth.edu';
>>>>>>> c710484ed38b0567a040793ee5fc50ff3017bc52

$sql = "UPDATE user SET Password = ? WHERE email = ?";
$stmt = $con->prepare($sql);
$stmt->bind_param("ss", $hashed_password, $email);

if ($stmt->execute()) {
    echo "Password updated and hashed successfully! Now try logging in with: password123";
} else {
    echo "Error updating database: " . $con->error;
}
?>