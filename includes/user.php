<?php
require_once 'database.php';

class User {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    // Register a new user
    public function register($username, $email, $password, $fullName, $phone = null) {
        // Check if username or email already exist
        $sql = "SELECT user_id FROM users WHERE username = ? OR email = ?";
        $stmt = $this->db->query($sql, [$username, $email]);
        
        if ($this->db->numRows($stmt) > 0) {
            return ['success' => false, 'message' => 'Username or email already exists.'];
        }
        
        // Hash the password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert the new user
        $sql = "INSERT INTO users (username, email, password, full_name, phone) VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->db->query($sql, [$username, $email, $hashedPassword, $fullName, $phone]);
        
        if ($stmt) {
            return ['success' => true, 'message' => 'Registration successful. You can now log in.', 'user_id' => $this->db->lastInsertId()];
        } else {
            return ['success' => false, 'message' => 'Registration failed. Please try again.'];
        }
    }
    
    // Login a user
    public function login($username, $password) {
        $sql = "SELECT * FROM users WHERE (username = ? OR email = ?) AND status != 'banned'";
        $stmt = $this->db->query($sql, [$username, $username]);
        
        $user = $this->db->fetch($stmt);
        
        if (!$user) {
            return ['success' => false, 'message' => 'Invalid username or password.'];
        }
        
        if (password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_type'] = $user['user_type'];
            $_SESSION['last_activity'] = time();
            
            return ['success' => true, 'message' => 'Login successful.', 'user' => $user];
        } else {
            return ['success' => false, 'message' => 'Invalid username or password.'];
        }
    }
    
    // Logout the current user
    public function logout() {
        session_unset();
        session_destroy();
        return true;
    }
    
    // Get user by ID
    public function getUserById($userId) {
        $sql = "SELECT * FROM users WHERE user_id = ?";
        $stmt = $this->db->query($sql, [$userId]);
        
        return $this->db->fetch($stmt);
    }
    
    // Update user details
    public function updateUser($userId, $fullName, $email, $phone) {
        $sql = "UPDATE users SET full_name = ?, email = ?, phone = ? WHERE user_id = ?";
        $stmt = $this->db->query($sql, [$fullName, $email, $phone, $userId]);
        
        return $stmt ? true : false;
    }
    
    // Change user password
    public function changePassword($userId, $oldPassword, $newPassword) {
        // Get the current user
        $user = $this->getUserById($userId);
        
        if (!$user) {
            return ['success' => false, 'message' => 'User not found.'];
        }
        
        // Verify the old password
        if (!password_verify($oldPassword, $user['password'])) {
            return ['success' => false, 'message' => 'Current password is incorrect.'];
        }
        
        // Hash the new password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        // Update the password
        $sql = "UPDATE users SET password = ? WHERE user_id = ?";
        $stmt = $this->db->query($sql, [$hashedPassword, $userId]);
        
        if ($stmt) {
            return ['success' => true, 'message' => 'Password updated successfully.'];
        } else {
            return ['success' => false, 'message' => 'Failed to update password. Please try again.'];
        }
    }
    
    // Check if a user is logged in
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    // Check if a user is an admin
    public function isAdmin() {
        return isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'admin';
    }
    
    // Get all users (for admin)
    public function getAllUsers() {
        $sql = "SELECT * FROM users ORDER BY created_at DESC";
        $stmt = $this->db->query($sql);
        
        return $this->db->fetchAll($stmt);
    }
    
    // Ban/unban a user (for admin)
    public function toggleBan($userId, $status = 'banned') {
        $status = ($status == 'banned') ? 'banned' : 'active';
        $sql = "UPDATE users SET status = ? WHERE user_id = ?";
        $stmt = $this->db->query($sql, [$status, $userId]);
        
        return $stmt ? true : false;
    }
}