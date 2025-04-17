<?php
require_once 'database.php';

class Item {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    // Add a new item (lost or found)
    public function addItem($userId, $title, $description, $categoryId, $type, $dateItem, $location, $image = null) {
        // Check for banned keywords
        if ($this->containsBannedKeywords($title) || $this->containsBannedKeywords($description)) {
            return ['success' => false, 'message' => 'Your post contains prohibited content.'];
        }
        
        // Require image for found items
        if ($type === 'found' && empty($image)) {
            return ['success' => false, 'message' => 'Found items must include an image.'];
        }
        
        // Insert the item into database
        $sql = "INSERT INTO items (user_id, title, description, category_id, type, date_item, location, image) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->query($sql, [$userId, $title, $description, $categoryId, $type, $dateItem, $location, $image]);
        
        if (!$stmt) {
            return ['success' => false, 'message' => 'Failed to add item. Please try again.'];
        }
        
        $itemId = $this->db->lastInsertId();
        
        // Extract and save keywords for matching
        $this->extractKeywords($itemId, $title . ' ' . $description);
        
        // If this is a lost item, find potential matches with found items
        if ($type === 'lost') {
            $this->findPotentialMatches($itemId, 'lost');
        } 
        // If this is a found item, find potential matches with lost items
        else if ($type === 'found') {
            $this->findPotentialMatches($itemId, 'found');
        }
        
        return ['success' => true, 'message' => 'Item added successfully.', 'item_id' => $itemId];
    }
    
    // Update an existing item
    public function updateItem($itemId, $userId, $title, $description, $categoryId, $dateItem, $location, $image = null) {
        // Check ownership
        if (!$this->isOwner($itemId, $userId) && !$this->isAdmin($userId)) {
            return ['success' => false, 'message' => 'You do not have permission to update this item.'];
        }
        
        // Check for banned keywords
        if ($this->containsBannedKeywords($title) || $this->containsBannedKeywords($description)) {
            return ['success' => false, 'message' => 'Your post contains prohibited content.'];
        }
        
        // Create the base query
        $sql = "UPDATE items SET title = ?, description = ?, category_id = ?, date_item = ?, location = ?";
        $params = [$title, $description, $categoryId, $dateItem, $location];
        
        // Add image to update if provided
        if (!empty($image)) {
            $sql .= ", image = ?";
            $params[] = $image;
        }
        
        // Complete the query with WHERE clause
        $sql .= " WHERE item_id = ?";
        $params[] = $itemId;
        
        // Execute the update
        $stmt = $this->db->query($sql, $params);
        
        if (!$stmt) {
            return ['success' => false, 'message' => 'Failed to update item. Please try again.'];
        }
        
        // Update keywords
        $this->deleteKeywords($itemId);
        $this->extractKeywords($itemId, $title . ' ' . $description);
        
        // Update potential matches
        $item = $this->getItemById($itemId);
        if ($item) {
            $this->deleteMatches($itemId);
            $this->findPotentialMatches($itemId, $item['type']);
        }
        
        return ['success' => true, 'message' => 'Item updated successfully.'];
    }
    
    // Delete an item
    public function deleteItem($itemId, $userId) {
        // Check ownership
        if (!$this->isOwner($itemId, $userId) && !$this->isAdmin($userId)) {
            return ['success' => false, 'message' => 'You do not have permission to delete this item.'];
        }
        
        // Delete the item
        $sql = "DELETE FROM items WHERE item_id = ?";
        $stmt = $this->db->query($sql, [$itemId]);
        
        // Keywords and matches will be deleted by cascading constraints
        
        return $stmt ? 
            ['success' => true, 'message' => 'Item deleted successfully.'] : 
            ['success' => false, 'message' => 'Failed to delete item. Please try again.'];
    }
    
    // Get item by ID
    public function getItemById($itemId) {
        error_log("Starting getItemById with ID: " . $itemId);
        
        $sql = "SELECT i.*, u.username, u.email, u.full_name, u.phone, c.name as category_name 
                FROM items i 
                LEFT JOIN users u ON i.user_id = u.user_id 
                LEFT JOIN categories c ON i.category_id = c.category_id 
                WHERE i.item_id = ?";
        
        error_log("SQL query: " . $sql);
        
        $stmt = $this->db->query($sql, [$itemId]);
        
        if (!$stmt) {
            error_log("Query failed for item ID: " . $itemId);
            return null;
        }
        
        if ($this->db->numRows($stmt) == 0) {
            error_log("No rows found for item ID: " . $itemId);
            $this->db->closeStatement($stmt);
            return null;
        }
        
        $item = $this->db->fetch($stmt);
        error_log("Item fetched successfully: " . json_encode($item));
        
        $this->db->closeStatement($stmt);
        return $item;
    }
    
    // Get all items with optional filters
    public function getItems($type = null, $categoryId = null, $userId = null, $status = 'open', $limit = 20, $offset = 0) {
        $sql = "SELECT i.*, u.username, c.name as category_name 
                FROM items i 
                LEFT JOIN users u ON i.user_id = u.user_id 
                LEFT JOIN categories c ON i.category_id = c.category_id 
                WHERE 1=1";
        $params = [];
        
        if ($type) {
            $sql .= " AND i.type = ?";
            $params[] = $type;
        }
        
        if ($categoryId) {
            $sql .= " AND i.category_id = ?";
            $params[] = $categoryId;
        }
        
        if ($userId) {
            $sql .= " AND i.user_id = ?";
            $params[] = $userId;
        }
        
        if ($status) {
            $sql .= " AND i.status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY i.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $this->db->query($sql, $params);
        
        if (!$stmt) {
            return [];
        }
        
        return $this->db->fetchAll($stmt);
    }
    
    // Search for items by keywords
    public function searchItems($query, $type = null, $categoryId = null, $limit = 20, $offset = 0) {
        $sql = "SELECT i.*, u.username, c.name as category_name 
                FROM items i 
                LEFT JOIN users u ON i.user_id = u.user_id 
                LEFT JOIN categories c ON i.category_id = c.category_id 
                WHERE (i.title LIKE ? OR i.description LIKE ? OR i.location LIKE ?)";
        $likeParam = "%" . $query . "%";
        $params = [$likeParam, $likeParam, $likeParam];
        
        if ($type) {
            $sql .= " AND i.type = ?";
            $params[] = $type;
        }
        
        if ($categoryId) {
            $sql .= " AND i.category_id = ?";
            $params[] = $categoryId;
        }
        
        $sql .= " AND i.status = 'open' ORDER BY i.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $this->db->query($sql, $params);
        
        if (!$stmt) {
            return [];
        }
        
        return $this->db->fetchAll($stmt);
    }
    
    // Mark an item as claimed/found (close item)
    public function closeItem($itemId, $userId) {
        // Check ownership
        if (!$this->isOwner($itemId, $userId) && !$this->isAdmin($userId)) {
            return ['success' => false, 'message' => 'You do not have permission to update this item.'];
        }
        
        $sql = "UPDATE items SET status = 'closed' WHERE item_id = ?";
        $stmt = $this->db->query($sql, [$itemId]);
        
        return $stmt ? 
            ['success' => true, 'message' => 'Item marked as resolved.'] : 
            ['success' => false, 'message' => 'Failed to update item status. Please try again.'];
    }
    
    // Flag an item as inappropriate
    public function flagItem($itemId) {
        $sql = "UPDATE items SET status = 'flagged' WHERE item_id = ?";
        $stmt = $this->db->query($sql, [$itemId]);
        
        return $stmt ? 
            ['success' => true, 'message' => 'Item has been flagged for review.'] : 
            ['success' => false, 'message' => 'Failed to flag item. Please try again.'];
    }
    
    // Get all categories
    public function getCategories() {
        $sql = "SELECT * FROM categories ORDER BY name";
        $stmt = $this->db->query($sql);
        
        if (!$stmt) {
            return [];
        }
        
        return $this->db->fetchAll($stmt);
    }
    
    // Upload an image and return the filename
    public function uploadImage($file) {
        $uploadDir = UPLOAD_PATH;
        
        // Create the uploads directory if it doesn't exist
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        // Check file size
        if ($file['size'] > MAX_FILE_SIZE) {
            return ['success' => false, 'message' => 'File is too large. Maximum file size is ' . (MAX_FILE_SIZE / 1024 / 1024) . 'MB.'];
        }
        
        // Check file type
        $fileType = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($fileType, ALLOWED_EXTENSIONS)) {
            return ['success' => false, 'message' => 'Only ' . implode(', ', ALLOWED_EXTENSIONS) . ' files are allowed.'];
        }
        
        // Generate a unique filename
        $newFilename = uniqid() . '.' . $fileType;
        $targetFile = $uploadDir . $newFilename;
        
        // Move the uploaded file
        if (move_uploaded_file($file['tmp_name'], $targetFile)) {
            return ['success' => true, 'filename' => $newFilename];
        } else {
            return ['success' => false, 'message' => 'Failed to upload file. Please try again.'];
        }
    }
    
    // Check if user owns an item
    private function isOwner($itemId, $userId) {
        $sql = "SELECT user_id FROM items WHERE item_id = ?";
        $stmt = $this->db->query($sql, [$itemId]);
        
        if (!$stmt || $this->db->numRows($stmt) == 0) {
            return false;
        }
        
        $item = $this->db->fetch($stmt);
        return $item['user_id'] == $userId;
    }
    
    // Check if user is an admin
    private function isAdmin($userId) {
        $sql = "SELECT user_type FROM users WHERE user_id = ?";
        $stmt = $this->db->query($sql, [$userId]);
        
        if (!$stmt || $this->db->numRows($stmt) == 0) {
            return false;
        }
        
        $user = $this->db->fetch($stmt);
        return $user['user_type'] == 'admin';
    }
    
    // Check if text contains banned keywords
    private function containsBannedKeywords($text) {
        $sql = "SELECT keyword FROM banned_keywords";
        $stmt = $this->db->query($sql);
        
        if (!$stmt) {
            return false;
        }
        
        $keywords = $this->db->fetchAll($stmt);
        $text = strtolower($text);
        
        foreach ($keywords as $keyword) {
            if (strpos($text, strtolower($keyword['keyword'])) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    // Extract and save keywords from text
    private function extractKeywords($itemId, $text) {
        // Split text into words
        $words = preg_split('/\s+/', $text);
        $uniqueWords = [];
        
        // Process each word
        foreach ($words as $word) {
            // Clean the word
            $word = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $word));
            
            // Skip short words, numbers, and common words
            if (strlen($word) < 3 || is_numeric($word) || in_array($word, $this->getCommonWords())) {
                continue;
            }
            
            // Add to unique words
            if (!in_array($word, $uniqueWords)) {
                $uniqueWords[] = $word;
            }
        }
        
        // Insert keywords into database
        foreach ($uniqueWords as $word) {
            $sql = "INSERT INTO keywords (item_id, keyword) VALUES (?, ?)";
            $this->db->query($sql, [$itemId, $word]);
        }
    }
    
    // Delete all keywords for an item
    private function deleteKeywords($itemId) {
        $sql = "DELETE FROM keywords WHERE item_id = ?";
        $this->db->query($sql, [$itemId]);
    }
    
    // Delete all matches for an item
    private function deleteMatches($itemId) {
        $sql = "DELETE FROM matches WHERE lost_item_id = ? OR found_item_id = ?";
        $this->db->query($sql, [$itemId, $itemId]);
    }
    
    // Find potential matches for an item
    private function findPotentialMatches($itemId, $itemType) {
        // Get the item details
        $item = $this->getItemById($itemId);
        
        if (!$item) {
            return;
        }
        
        // Determine which type of items to match with
        $matchType = ($itemType === 'lost') ? 'found' : 'lost';
        
        // Get keywords for the current item
        $sql = "SELECT keyword FROM keywords WHERE item_id = ?";
        $stmt = $this->db->query($sql, [$itemId]);
        $keywords = [];
        
        if ($stmt) {
            $rows = $this->db->fetchAll($stmt);
            foreach ($rows as $row) {
                $keywords[] = $row['keyword'];
            }
            $this->db->closeStatement($stmt);
        }
        
        // Find items of the opposite type in the same category
        $sql = "SELECT * FROM items WHERE type = ? AND category_id = ? AND status = 'open'";
        $stmt = $this->db->query($sql, [$matchType, $item['category_id']]);
        
        if (!$stmt) {
            return;
        }
        
        $potentialMatches = $this->db->fetchAll($stmt);
        $this->db->closeStatement($stmt);
        
        // Calculate match score for each potential match
        foreach ($potentialMatches as $match) {
            $score = 0;
            
            // Location match (20%)
            if (strcasecmp($item['location'], $match['location']) === 0) {
                $score += 20;
            } else if (stripos($match['location'], $item['location']) !== false || 
                       stripos($item['location'], $match['location']) !== false) {
                $score += 10;
            }
            
            // Date match (20%)
            $itemDate = strtotime($item['date_item']);
            $matchDate = strtotime($match['date_item']);
            $daysDiff = abs(($itemDate - $matchDate) / (60 * 60 * 24));
            
            if ($daysDiff <= 1) {
                $score += 20;
            } else if ($daysDiff <= 3) {
                $score += 15;
            } else if ($daysDiff <= 7) {
                $score += 10;
            } else if ($daysDiff <= 14) {
                $score += 5;
            }
            
            // Keyword match (60%)
            $matchKeywords = [];
            $keywordSql = "SELECT keyword FROM keywords WHERE item_id = ?";
            $keywordStmt = $this->db->query($keywordSql, [$match['item_id']]);
            
            if ($keywordStmt) {
                $keywordRows = $this->db->fetchAll($keywordStmt);
                foreach ($keywordRows as $row) {
                    $matchKeywords[] = $row['keyword'];
                }
                $this->db->closeStatement($keywordStmt);
            }
            
            $matchingKeywords = array_intersect($keywords, $matchKeywords);
            $keywordScore = min(60, count($matchingKeywords) * 10);
            $score += $keywordScore;
            
            // Only store matches with a reasonable score
            if ($score >= 30) {
                $lostId = ($itemType === 'lost') ? $itemId : $match['item_id'];
                $foundId = ($itemType === 'found') ? $itemId : $match['item_id'];
                
                $insertSql = "INSERT INTO matches (lost_item_id, found_item_id, match_score) VALUES (?, ?, ?)";
                $insertStmt = $this->db->query($insertSql, [$lostId, $foundId, $score]);
                if ($insertStmt) {
                    $this->db->closeStatement($insertStmt);
                }
            }
        }
    }
    
    // Get potential matches for an item
    public function getMatches($itemId) {
        $item = $this->getItemById($itemId);
        
        if (!$item) {
            return [];
        }
        
        if ($item['type'] === 'lost') {
            $sql = "SELECT m.*, i.* 
                    FROM matches m 
                    JOIN items i ON m.found_item_id = i.item_id 
                    WHERE m.lost_item_id = ? 
                    ORDER BY m.match_score DESC";
            $stmt = $this->db->query($sql, [$itemId]);
        } else {
            $sql = "SELECT m.*, i.* 
                    FROM matches m 
                    JOIN items i ON m.lost_item_id = i.item_id 
                    WHERE m.found_item_id = ? 
                    ORDER BY m.match_score DESC";
            $stmt = $this->db->query($sql, [$itemId]);
        }
        
        if (!$stmt) {
            return [];
        }
        
        return $this->db->fetchAll($stmt);
    }
    
    // List of common words to exclude from keywords
    private function getCommonWords() {
        return ['the', 'and', 'for', 'are', 'but', 'not', 'you', 'all', 'any', 'can', 'had', 'her', 'was', 'one', 'our', 'out', 'day', 'get', 'has', 'him', 'his', 'how', 'man', 'new', 'now', 'old', 'see', 'two', 'way', 'who', 'boy', 'did', 'its', 'let', 'put', 'say', 'she', 'too', 'use'];
    }
}