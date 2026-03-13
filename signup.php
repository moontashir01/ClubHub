<?php 
session_start();
include 'connection.php';

class User {
    private $con;

    public function __construct($db_connection) {
        $this->con = $db_connection;
    }

    public function createAccount($ID, $name, $email, $password, $phone, $dob) {
        $student_id = mysqli_real_escape_string($this->con, $ID);
        $name       = mysqli_real_escape_string($this->con, $name);
        $email      = mysqli_real_escape_string($this->con, $email);
        $phone      = mysqli_real_escape_string($this->con, $phone);
        $dob        = mysqli_real_escape_string($this->con, $dob);

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Check if Email or ID already exists
        $verify_query = mysqli_query($this->con, "SELECT email FROM user WHERE email='$email'");
        $verify_query2 = mysqli_query($this->con, "SELECT student_id FROM students WHERE student_id='$student_id'");
        
        if(mysqli_num_rows($verify_query) != 0 || mysqli_num_rows($verify_query2) != 0) {
            $_SESSION['msg'] = "This Email or ID is already registered.";
            $_SESSION['msg_type'] = "error"; 
            header("Location: homepage.php");
            exit();
        }

        // START TRANSACTION - Since student_email depends on user.email
        mysqli_begin_transaction($this->con);

        try {
            // 1. Insert into 'user' first (Portal will use its DB default value)
            $sql_user = "INSERT INTO `user` (email, Name, password) VALUES ('$email', '$name', '$hashed_password')";
            if (!mysqli_query($this->con, $sql_user)) {
                throw new Exception("User table error: " . mysqli_error($this->con));
            }

            // 2. Insert into 'students' (Foreign Key student_email checks for $email above)
            $sql_student = "INSERT INTO `students` (student_id, full_name, student_email, DOB, contact) 
                            VALUES ('$student_id', '$name', '$email', '$dob', '$phone')";
            if (!mysqli_query($this->con, $sql_student)) {
                throw new Exception("Student table error: " . mysqli_error($this->con));
            }

            // If both queries are successful, commit to database
            mysqli_commit($this->con);

            $_SESSION['msg'] = "Account created successfully!";
            $_SESSION['msg_type'] = "success"; 
            header("Location: homepage.php");
            exit();

        } catch (Exception $e) {
            // If either query fails, rollback to prevent orphan records
            mysqli_rollback($this->con);
            $_SESSION['msg'] = "Registration failed: " . $e->getMessage();
            $_SESSION['msg_type'] = "error";
            header("Location: homepage.php");
            exit();
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