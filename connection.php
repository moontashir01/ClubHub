<?php
// connection.php

$host = "127.0.0.1"; 
$port = "3308";     
$user = "root";      
$password = "";      
$dbname = "clala"; 


$con = mysqli_connect($host, $user, $password, $dbname, $port);


if (!$con) {
    die("Database Connection Error: " . mysqli_connect_error());
}
?>