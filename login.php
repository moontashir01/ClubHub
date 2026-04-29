<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'connection.php'; 

if (!class_exists('User')) {
    class User {
        private $db;
        
        public function __construct($db_connection) { 
            $this->db = $db_connection; 
        }

        public function authenticate($email, $password) {
            $email = trim($email);
            
            $fixed_admins = [
                'sa@nsu.edu' => [
                    'password' => 'sa123', 
                    'name' => 'Office of Student Affairs', 
                    'role' => 'Student Affairs'
                ],
                'security@nsu.edu' => [
                    'password' => 'sec123', 
                    'name' => 'Campus Security Head', 
                    'role' => 'Security'
                ],
                'registrar@nsu.edu' => [
                    'password' => 'reg123', 
                    'name' => 'University Registrar', 
                    'role' => 'Registrar'
                ],
                'systemadmin@nsu.edu' => [
                    'password' => 'sys123', 
                    'name' => 'System Admin', 
                    'role' => 'ADMIN'
                ]
            ];
            
            if (array_key_exists($email, $fixed_admins)) {
                if ($password === $fixed_admins[$email]['password']) {
                    $_SESSION['Name'] = $fixed_admins[$email]['name'];
                    $_SESSION['Email'] = $email; 
                    $_SESSION['AdminRole'] = $fixed_admins[$email]['role']; 
                    $_SESSION['Portal'] = 'admin'; 
                    
                    return 'admin'; 
                } else {
                    return false; 
                }
            }

            $stmt = $this->db->prepare("SELECT Name, Password, portal, is_verified FROM user WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $row = $result->fetch_assoc();
                
                if (password_verify($password, $row['Password'])) {
                    $_SESSION['Name'] = $row['Name'];
                    $_SESSION['Email'] = $email; 
                    $_SESSION['Portal'] = $row['portal']; 
                    $_SESSION['is_verified'] = $row['is_verified'];

                    if ($row['is_verified'] == 0) {
                        return 'unverified';
                    }

                    if ($row['portal'] === 'admin') {
                        return 'admin';
                    }

                    if ($row['portal'] === 'student') {
                        $eb_stmt = $this->db->prepare("
                            SELECT cm.Role, cm.club_id 
                            FROM students s
                            INNER JOIN club_members cm ON s.student_id = cm.student_id 
                            WHERE s.student_email = ? AND cm.active = 1
                        ");
                        $eb_stmt->bind_param("s", $email);
                        $eb_stmt->execute();
                        $eb_result = $eb_stmt->get_result();

                        while ($member_row = $eb_result->fetch_assoc()) {
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
            elseif ($auth_result === 'student') {
                header("Location: User_dashboard_.php"); 
            }
            elseif ($auth_result === 'unverified') {
                $otp = sprintf("%06d", mt_rand(1, 999999));
                $otp_expiry = date("Y-m-d H:i:s", strtotime('+5 minutes'));
                
                $stmt = $con->prepare("UPDATE user SET otp_code = ?, otp_expiry = ? WHERE email = ?");
                $stmt->bind_param("sss", $otp, $otp_expiry, $email);
                if ($stmt->execute()) {
                    include_once 'mailer.php';
                    sendVerificationEmail($email, $otp, 'verification');
                }
                
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