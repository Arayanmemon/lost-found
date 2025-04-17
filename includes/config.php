<?php
// Database connection parameters
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'lost_and_found');

// Application settings
define('SITE_NAME', 'Smart Lost & Found Portal');
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif']);
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB

// Email configuration
define('ADMIN_EMAIL', 'admin@example.com');

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Session configuration and start
// Check if a session is already active before configuring and starting
if (session_status() == PHP_SESSION_NONE) {
    // Session configuration (must be set before session_start)
    ini_set('session.gc_maxlifetime', 3600); // 1 hour
    // Start session
    session_start();
}

// URL paths
$base_url = 'http://' . $_SERVER['HTTP_HOST'] . '/lost&found/';
define('BASE_URL', $base_url);
define('ASSETS_URL', $base_url . 'assets/');