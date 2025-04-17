<?php
require_once 'database.php';

class Admin {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    // Get flagged items for review
    public function getFlaggedItems($limit = 20, $offset = 0) {
        $sql = "SELECT i.*, u.username, c.name as category_name 
                FROM items i 
                JOIN users u ON i.user_id = u.user_id 
                JOIN categories c ON i.category_id = c.category_id 
                WHERE i.status = 'flagged' 
                ORDER BY i.created_at DESC 
                LIMIT ? OFFSET ?";
        $stmt = $this->db->query($sql, [$limit, $offset]);
        
        if (!$stmt) {
            return [];
        }
        
        return $this->db->fetchAll($stmt);
    }
    
    // Approve a flagged item
    public function approveItem($itemId) {
        $sql = "UPDATE items SET status = 'open' WHERE item_id = ? AND status = 'flagged'";
        $stmt = $this->db->query($sql, [$itemId]);
        
        return $stmt ? true : false;
    }
    
    // Remove a flagged item
    public function removeItem($itemId) {
        $sql = "DELETE FROM items WHERE item_id = ? AND status = 'flagged'";
        $stmt = $this->db->query($sql, [$itemId]);
        
        return $stmt ? true : false;
    }
    
    // Get banned keywords
    public function getBannedKeywords() {
        $sql = "SELECT * FROM banned_keywords ORDER BY keyword";
        $stmt = $this->db->query($sql);
        
        if (!$stmt) {
            return [];
        }
        
        return $this->db->fetchAll($stmt);
    }
    
    // Add a banned keyword
    public function addBannedKeyword($keyword) {
        $keyword = trim(strtolower($keyword));
        
        if (empty($keyword)) {
            return false;
        }
        
        // Check if keyword already exists
        $sql = "SELECT id FROM banned_keywords WHERE keyword = ?";
        $stmt = $this->db->query($sql, [$keyword]);
        
        if ($this->db->numRows($stmt) > 0) {
            return false;
        }
        
        $sql = "INSERT INTO banned_keywords (keyword) VALUES (?)";
        $stmt = $this->db->query($sql, [$keyword]);
        
        return $stmt ? true : false;
    }
    
    // Remove a banned keyword
    public function removeBannedKeyword($id) {
        $sql = "DELETE FROM banned_keywords WHERE id = ?";
        $stmt = $this->db->query($sql, [$id]);
        
        return $stmt ? true : false;
    }
    
    // Get all users
    public function getUsers($limit = 20, $offset = 0) {
        $sql = "SELECT * FROM users ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $stmt = $this->db->query($sql, [$limit, $offset]);
        
        if (!$stmt) {
            return [];
        }
        
        return $this->db->fetchAll($stmt);
    }
    
    // Ban a user
    public function banUser($userId) {
        $sql = "UPDATE users SET status = 'banned' WHERE user_id = ?";
        $stmt = $this->db->query($sql, [$userId]);
        
        return $stmt ? true : false;
    }
    
    // Unban a user
    public function unbanUser($userId) {
        $sql = "UPDATE users SET status = 'active' WHERE user_id = ?";
        $stmt = $this->db->query($sql, [$userId]);
        
        return $stmt ? true : false;
    }
    
    // Get system statistics
    public function getStatistics() {
        $stats = [];
        
        // Total users
        $sql = "SELECT COUNT(*) as total FROM users";
        $stmt = $this->db->query($sql);
        $result = $this->db->fetch($stmt);
        $stats['total_users'] = $result['total'];
        
        // Total lost items
        $sql = "SELECT COUNT(*) as total FROM items WHERE type = 'lost'";
        $stmt = $this->db->query($sql);
        $result = $this->db->fetch($stmt);
        $stats['total_lost'] = $result['total'];
        
        // Total found items
        $sql = "SELECT COUNT(*) as total FROM items WHERE type = 'found'";
        $stmt = $this->db->query($sql);
        $result = $this->db->fetch($stmt);
        $stats['total_found'] = $result['total'];
        
        // Total resolved items
        $sql = "SELECT COUNT(*) as total FROM items WHERE status = 'closed'";
        $stmt = $this->db->query($sql);
        $result = $this->db->fetch($stmt);
        $stats['total_resolved'] = $result['total'];
        
        // Items by category
        $sql = "SELECT c.name, COUNT(i.item_id) as total 
                FROM categories c 
                LEFT JOIN items i ON c.category_id = i.category_id 
                GROUP BY c.category_id 
                ORDER BY total DESC";
        $stmt = $this->db->query($sql);
        $stats['items_by_category'] = $this->db->fetchAll($stmt);
        
        // Recent activity
        $sql = "SELECT i.*, u.username, c.name as category_name 
                FROM items i 
                JOIN users u ON i.user_id = u.user_id 
                JOIN categories c ON i.category_id = c.category_id 
                ORDER BY i.created_at DESC 
                LIMIT 10";
        $stmt = $this->db->query($sql);
        $stats['recent_activity'] = $this->db->fetchAll($stmt);
        
        return $stats;
    }
    
    // Add a new category
    public function addCategory($name, $description) {
        $sql = "INSERT INTO categories (name, description) VALUES (?, ?)";
        $stmt = $this->db->query($sql, [$name, $description]);
        
        return $stmt ? $this->db->lastInsertId() : false;
    }
    
    // Update a category
    public function updateCategory($categoryId, $name, $description) {
        $sql = "UPDATE categories SET name = ?, description = ? WHERE category_id = ?";
        $stmt = $this->db->query($sql, [$name, $description, $categoryId]);
        
        return $stmt ? true : false;
    }
    
    // Delete a category (only if no items are associated)
    public function deleteCategory($categoryId) {
        // Check if category has items
        $sql = "SELECT COUNT(*) as total FROM items WHERE category_id = ?";
        $stmt = $this->db->query($sql, [$categoryId]);
        $result = $this->db->fetch($stmt);
        
        if ($result['total'] > 0) {
            return false;
        }
        
        $sql = "DELETE FROM categories WHERE category_id = ?";
        $stmt = $this->db->query($sql, [$categoryId]);
        
        return $stmt ? true : false;
    }
}