<?php
session_start();
require_once '../config/database.php';

class Auth {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    public function registerUser($first_name, $last_name, $email, $password, $phone) {
        try {
            $query = "INSERT INTO users (first_name, last_name, email, password, phone) VALUES (?, ?, ?, ?, ?)";
            $stmt = $this->conn->prepare($query);
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            if($stmt->execute([$first_name, $last_name, $email, $password_hash, $phone])) {
                return ['success' => true, 'message' => 'Registration successful'];
            }
        } catch(PDOException $e) {
            if($e->getCode() == 23000) {
                return ['success' => false, 'message' => 'Email already exists'];
            }
            return ['success' => false, 'message' => 'Registration failed'];
        }
        return ['success' => false, 'message' => 'Registration failed'];
    }
    
    public function loginUser($email, $password) {
        try {
            $query = "SELECT id, first_name, last_name, email, password FROM users WHERE email = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$email]);
            
            if($stmt->rowCount() > 0) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                if(password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                    $_SESSION['user_email'] = $user['email'];
                    return ['success' => true, 'message' => 'Login successful'];
                }
            }
            return ['success' => false, 'message' => 'Invalid credentials'];
        } catch(PDOException $e) {
            return ['success' => false, 'message' => 'Login failed'];
        }
    }
    
    public function loginAdmin($username, $password) {
        try {
            $query = "SELECT id, username, email, password FROM admins WHERE username = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$username]);
            
            if($stmt->rowCount() > 0) {
                $admin = $stmt->fetch(PDO::FETCH_ASSOC);
                if(password_verify($password, $admin['password'])) {
                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['admin_name'] = $admin['username'];
                    $_SESSION['admin_email'] = $admin['email'];
                    return ['success' => true, 'message' => 'Admin login successful'];
                }
            }
            return ['success' => false, 'message' => 'Invalid admin credentials'];
        } catch(PDOException $e) {
            return ['success' => false, 'message' => 'Admin login failed'];
        }
    }
    
    public function logout() {
        session_destroy();
        return true;
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    public function isAdminLoggedIn() {
        return isset($_SESSION['admin_id']);
    }
}
?>
