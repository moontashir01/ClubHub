<?php 
  session_start();
  include 'connection.php'; 

  class User {
      private $db;
      public function __construct($db_connection) { $this->db = $db_connection; }

      public function authenticate($email, $password) {
          $email = trim($email);
          // STEP 1: Added 'Role' to the SELECT statement
          $stmt = $this->db->prepare("SELECT Name, Password, Role FROM user WHERE email = ?");
          $stmt->bind_param("s", $email);
          $stmt->execute();
          $result = $stmt->get_result();

          if ($result->num_rows === 1) {
              $row = $result->fetch_assoc();
              if (password_verify($password, $row['Password'])) {
                  $_SESSION['Name'] = $row['Name'];
                  $_SESSION['Email'] = $email; // Fixed: Use the $email variable
                  $_SESSION['Role'] = $row['Role']; // Store role in session for security

                  // STEP 2: Return the Role string instead of just 'true'
                  return $row['Role']; 
              }
          }
          return false;
      }
  }

  if (isset($_POST['submit'])) {
      $email = $_POST['Email'];
      $password = $_POST['Password'];

      if (isset($con)) {
          $user = new User($con);
          $role = $user->authenticate($email, $password); // This now holds the Role string

          if ($role) {
              // STEP 3: Redirect based on the returned $role
              if ($role === 'admin') {
                  header("Location: admin_dashboard.php");
              } 
              elseif ($role === 'Executive Member') {
                  header("Location: Club_dashboard.php");
              } 
              elseif ($role === 'student'){
                  header("Location: User_dashboard.php"); // Default for students
              }
              exit();
          } else {
              $_SESSION['login_error'] = "Invalid email or password!";
              header("Location: homepage.php");
              exit();
          }
      }
  }
?>