<?php
    $db_server = 'localhost';
    $db_user = 'root';
    $db_pass = '';
<<<<<<< HEAD
    $db_name = 'club_hub';
=======
    $db_name = 'joyclab';
>>>>>>> accd2d80a69ff2ab1a3469709dd24a23ba5a953e
    
    try {
        
        $con = mysqli_connect($db_server, $db_user, $db_pass, $db_name);
    } catch(mysqli_sql_exception) {
        echo "Not Connected";
        exit(); 
    }
?>