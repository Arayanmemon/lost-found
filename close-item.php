<?php
// Include necessary files
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/user.php';
require_once 'includes/item.php';

// Initialize User class
$userObj = new User();

// Check if user is logged in
if (!$userObj->isLoggedIn()) {
    // Store the current page as the redirect destination after login
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header('Location: login.php');
    exit;
}

// Initialize Item class
$itemObj = new Item();

// Get item ID from URL
$itemId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
error_log("Close Item - Attempting to close item with ID: " . $itemId);

// If no item ID provided, redirect to home page
if ($itemId <= 0) {
    error_log("Close Item - Invalid item ID (<=0), redirecting to dashboard");
    header('Location: dashboard.php');
    exit;
}

// Get user ID
$userId = $_SESSION['user_id'];

// Get the database connection for direct verification
$db = Database::getInstance();

// Attempt to close the item
$result = $itemObj->closeItem($itemId, $userId);

// For troubleshooting, verify the item status directly
$verifySql = "SELECT status FROM items WHERE item_id = ?";
$verifyStmt = $db->query($verifySql, [$itemId]);
if ($verifyStmt) {
    $itemData = $db->fetch($verifyStmt);
    error_log("Close Item - Current status for item ID $itemId: " . ($itemData ? $itemData['status'] : 'Item not found'));
    $db->closeStatement($verifyStmt);
}

// Set message for the dashboard page
if ($result['success']) {
    $_SESSION['success_message'] = $result['message'];
    error_log("Close Item - Successfully closed item ID: " . $itemId);
} else {
    $_SESSION['error_message'] = $result['message'];
    error_log("Close Item - Failed to close item ID: " . $itemId . " - " . $result['message']);
}

// Redirect back to the item page
header('Location: item.php?id=' . $itemId);
exit;
?>