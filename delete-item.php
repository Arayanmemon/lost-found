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
error_log("Delete Item - Attempting to delete item with ID: " . $itemId);

// If no item ID provided, redirect to home page
if ($itemId <= 0) {
    error_log("Delete Item - Invalid item ID (<=0), redirecting to dashboard");
    header('Location: dashboard.php');
    exit;
}

// Get user ID
$userId = $_SESSION['user_id'];

// Attempt to delete the item
$result = $itemObj->deleteItem($itemId, $userId);

// Set message for the dashboard page
if ($result['success']) {
    $_SESSION['success_message'] = $result['message'];
    error_log("Delete Item - Successfully deleted item ID: " . $itemId);
} else {
    $_SESSION['error_message'] = $result['message'];
    error_log("Delete Item - Failed to delete item ID: " . $itemId . " - " . $result['message']);
}

// Redirect to the dashboard page
header('Location: dashboard.php');
exit;
?>