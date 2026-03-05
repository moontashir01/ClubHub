<?php 
session_start();
include 'connection.php';

class User {
    private $con;

    public function __construct($db_connection) {
        $this->con = $db_connection;
    }

    public function createAccount($ID, $name, $email, $password, $phone, $dob) {
        // Sanitize inputs
        $student_id = mysqli_real_escape_string($this->con, $ID);
        $name       = mysqli_real_escape_string($this->con, $name);
        $email      = mysqli_real_escape_string($this->con, $email);
        $phone      = mysqli_real_escape_string($this->con, $phone);
        $dob        = mysqli_real_escape_string($this->con, $dob);

        // --- PASSWORD HASHING ---
        // We hash the password BEFORE putting it into the SQL string
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $hashed_password = mysqli_real_escape_string($this->con, $hashed_password);

        // 1. Check if Email already exists
        $verify_query = mysqli_query($this->con, "SELECT email FROM user WHERE email='$email'");
        
        if(mysqli_num_rows($verify_query) != 0) {
            $_SESSION['msg'] = "This Email is already registered.";
            $_SESSION['msg_type'] = "error"; 
            header("Location: homepage.php");
            exit();
        } else {
            // 2. Insert into 'user' table (Using the $hashed_password)
            $sql_user = "INSERT INTO `user` (email, Name, password) VALUES ('$email', '$name', '$hashed_password')";
            
            // 3. Insert into 'students' table
            $sql_student = "INSERT INTO `students` (student_id, full_name, student_email, DOB, contact) 
                            VALUES ('$student_id', '$name', '$email', '$dob', '$phone')";

            if(mysqli_query($this->con, $sql_user) && mysqli_query($this->con, $sql_student)) {
                $_SESSION['msg'] = "Account created successfully! You can now login.";
                $_SESSION['msg_type'] = "success"; 
                header("Location: homepage.php");
                exit();
            } else {
                $_SESSION['msg'] = "Database Error: " . mysqli_error($this->con);
                $_SESSION['msg_type'] = "error";
                header("Location: homepage.php");
                exit();
            }
        }
    }
}

if (isset($_POST['submit'])) {
    $user = new User($con);
    $user->createAccount(
        $_POST['ID'], 
        $_POST['Name'], 
        $_POST['Email'], 
        $_POST['Password'], 
        $_POST['Phone'], 
        $_POST['DOB']
    );
}
?>
?>