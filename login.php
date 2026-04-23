<?php 
  session_start();
  include 'connection.php'; 

  class User {
      private $db;
      public function __construct($db_connection) { $this->db = $db_connection; }

      public function authenticate($email, $password) {
          $email = trim($email);
          
          // 1. Updated query to use 'portal' instead of 'Role' and check 'is_verified'
          $stmt = $this->db->prepare("SELECT Name, Password, portal, is_verified FROM user WHERE email = ?");
          $stmt->bind_param("s", $email);
          $stmt->execute();
          $result = $stmt->get_result();

          if ($result->num_rows === 1) {
              $row = $result->fetch_assoc();
              
              if (password_verify($password, $row['Password'])) {
                  $_SESSION['Name'] = $row['Name'];
                  $_SESSION['Email'] = $email; 
                  $_SESSION['Portal'] = $row['portal']; // Store new column value in session
                  $_SESSION['is_verified'] = $row['is_verified'];

                  if ($row['is_verified'] == 0) {
                      return 'unverified';
                  }

                  // Priority 1: Check if global portal is 'admin'
                  if ($row['portal'] === 'admin') {
                      return 'admin';
                  }

                  // Priority 2: If student, check club_members table for EB status
                  if ($row['portal'] === 'student') {
                      // Note: The club_members table still uses the column 'Role' as per your previous schema
                      $eb_stmt = $this->db->prepare("
                          SELECT cm.Role,cm.club_id 
                          FROM students s
                          INNER JOIN club_members cm ON s.student_id = cm.student_id 
                          WHERE s.student_email = ? AND cm.active = 1
                      ");
                      $eb_stmt->bind_param("s", $email);
                      $eb_stmt->execute();
                      $eb_result = $eb_stmt->get_result();

                      while ($member_row = $eb_result->fetch_assoc()) {
                          // Check if the Role in club_members starts with 'EB'
                          if (strtoupper(substr($member_row['Role'], 0, 2)) === 'EB') {
                            $_SESSION['club_id'] = $member_row['club_id']; 
                              return 'Executive Member'; 


                          }
                      }
                      
                      return 'student'; 
                  }
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
          $auth_result = $user->authenticate($email, $password);

          if ($auth_result) {
              if ($auth_result === 'admin') {
                  header("Location: admin_dashboard.php");
              } 
              elseif ($auth_result === 'Executive Member') {
                  header("Location: Club_dashboard.php");
              } 
              elseif ($auth_result === 'student'){
                  header("Location: User_dashboard.php"); 
              }
              elseif ($auth_result === 'unverified') {
                  header("Location: verify.php?email=" . urlencode($email));
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