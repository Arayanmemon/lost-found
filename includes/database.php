<?php
require_once 'config.php';

class Database {
    private $conn;
    private static $instance = null;
    
    private function __construct() {
        try {
            $this->conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            
            if ($this->conn->connect_error) {
                throw new Exception("Connection failed: " . $this->conn->connect_error);
            }
            
            $this->conn->set_charset("utf8mb4");
        } catch (Exception $e) {
            error_log("Database connection error: " . $e->getMessage());
            die("Database connection failed. Please try again later.");
        }
    }
    
    // Singleton pattern to ensure only one database connection
    public static function getInstance() {
        if (self::$instance == null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }
    
    // Get the database connection
    public function getConnection() {
        return $this->conn;
    }
    
    // Execute a query and return the result
    public function query($sql, $params = []) {
        try {
            error_log("DB Query - SQL: " . $sql);
            error_log("DB Query - Params: " . json_encode($params));
            
            $stmt = $this->conn->prepare($sql);
            
            if (!$stmt) {
                $error = "Query preparation failed: " . $this->conn->error . " for SQL: " . $sql;
                error_log("DB ERROR: " . $error);
                throw new Exception($error);
            }
            
            error_log("DB Query - Statement prepared successfully");
            
            if (!empty($params)) {
                $types = '';
                foreach ($params as $param) {
                    if (is_int($param)) {
                        $types .= 'i';
                    } elseif (is_float($param)) {
                        $types .= 'd';
                    } elseif (is_string($param)) {
                        $types .= 's';
                    } else {
                        $types .= 'b';
                    }
                }
                
                error_log("DB Query - Parameter types: " . $types);
                
                // Create a new array to properly handle references
                $bindParams = array($types);
                foreach ($params as $key => $value) {
                    $bindParams[] = &$params[$key];
                    error_log("DB Query - Binding param[$key]: " . (is_string($params[$key]) ? $params[$key] : gettype($params[$key])));
                }
                
                try {
                    call_user_func_array([$stmt, 'bind_param'], $bindParams);
                    error_log("DB Query - Parameters bound successfully");
                } catch (Exception $e) {
                    error_log("DB ERROR - bind_param: " . $e->getMessage());
                    throw $e;
                }
            }
            
            try {
                $executed = $stmt->execute();
                error_log("DB Query - Execute result: " . ($executed ? "Success" : "Failed"));
                
                if (!$executed) {
                    error_log("DB ERROR - execute: " . $stmt->error);
                }
                
                return $stmt;
            } catch (Exception $e) {
                error_log("DB ERROR - execute exception: " . $e->getMessage());
                throw $e;
            }
        } catch (Exception $e) {
            error_log("Query execution error: " . $e->getMessage());
            return false;
        }
    }
    
    // Fetch all records from a result
    public function fetchAll($stmt) {
        try {
            $result = $stmt->get_result();
            $rows = [];
            
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
            
            $result->free();
            // Remove statement closing from here as it might be reused
            return $rows;
        } catch (Exception $e) {
            error_log("fetchAll error: " . $e->getMessage());
            return [];
        }
    }
    
    // Fetch a single record from a result
    public function fetch($stmt) {
        try {
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $result->free();
            // Remove statement closing from here as it might be reused
            return $row;
        } catch (Exception $e) {
            error_log("fetch error: " . $e->getMessage());
            return null;
        }
    }
    
    // Get the ID of the last inserted record
    public function lastInsertId() {
        return $this->conn->insert_id;
    }
    
    // Escape a string for use in a query
    public function escape($string) {
        return $this->conn->real_escape_string($string);
    }
    
    // Count the number of rows in a result
    public function numRows($stmt) {
        try {
            $result = $stmt->get_result();
            $count = $result->num_rows;
            $result->free();
            // Remove statement closing from here as it might be reused
            return $count;
        } catch (Exception $e) {
            error_log("numRows error: " . $e->getMessage());
            return 0;
        }
    }
    
    // Begin a transaction
    public function beginTransaction() {
        $this->conn->begin_transaction();
    }
    
    // Commit a transaction
    public function commit() {
        $this->conn->commit();
    }
    
    // Rollback a transaction
    public function rollback() {
        $this->conn->rollback();
    }
    
    // Close a statement when done
    public function closeStatement($stmt) {
        if ($stmt && $stmt instanceof mysqli_stmt) {
            $stmt->close();
        }
    }
}