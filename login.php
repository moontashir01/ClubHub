<?php 
  session_start();
  include 'connection.php'; 

  class User {
      private $db;

      public function __construct($db_connection) {
          $this->db = $db_connection;
      }

      public function authenticate($email, $password) {
          // trim the email to remove accidental spaces
          $email = trim($email);
          
          // Using lowercase 'email' to match your table column exactly
          $stmt = $this->db->prepare("SELECT ID, Name, Password FROM user WHERE email = ?");
          $stmt->bind_param("s", $email);
          $stmt->execute();
          $result = $stmt->get_result();

          if ($result->num_rows === 1) {
              $row = $result->fetch_assoc();
              
              // This checks the hash you have in your DB
              if (password_verify($password, $row['Password'])) {
                  $_SESSION['ID'] = $row['ID'];
                  $_SESSION['Name'] = $row['Name'];
                  return true;
              }
          }
          return false;
      }
  }

  if (isset($_POST['submit'])) {
      // Get the form data
      $email = $_POST['Email'];
      $password = $_POST['Password'];

      // Ensure $con exists from connection.php
      if (isset($con)) {
          $user = new User($con);

          if ($user->authenticate($email, $password)) {
              header("Location: User_dashboard.php");
              exit();
          } else {
              // If it fails, we go to Error.php
              header("Location: Error.php");
              exit();
          }
      } else {
          die("Database connection variable is missing.");
      }
  }
?>