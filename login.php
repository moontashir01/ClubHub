<?php 
  session_start();
  include 'connection.php'; 

  class User {
      private $db;
      public function __construct($db_connection) { $this->db = $db_connection; }

      public function authenticate($email, $password) {
          $email = trim($email);
          $stmt = $this->db->prepare("SELECT ID, Name, Password FROM user WHERE email = ?");
          $stmt->bind_param("s", $email);
          $stmt->execute();
          $result = $stmt->get_result();

          if ($result->num_rows === 1) {
              $row = $result->fetch_assoc();
              if (password_verify($password, $row['Password'])) {
                  $_SESSION['ID'] = $row['ID'];
                  $_SESSION['Name'] = $row['Name'];
                  $_SESSION['Email'] = $row['email'];

                  return true;
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
          if ($user->authenticate($email, $password)) {
              header("Location: User_dashboard.php");
              exit();
          } else {
              // Store error in session and go back to homepage (clean URL)
              $_SESSION['login_error'] = "Invalid email or password!";
              header("Location: homepage.php");
              exit();
          }
      }
  }
?>