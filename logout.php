<?php
// Include necessary files
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/user.php';

// Initialize User class
$userObj = new User();

// Logout the user
$userObj->logout();

// Redirect to home page
header('Location: index.php');
exit;