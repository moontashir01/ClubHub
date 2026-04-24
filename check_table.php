<?php
include 'connection.php';
$res = mysqli_query($con, "SHOW CREATE TABLE events");
$row = mysqli_fetch_row($res);
echo $row[1];
