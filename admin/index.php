<?php
// Include configuration file first
require_once '../includes/config.php';
// No need to call session_start() here as it's already called in config.php

// Include necessary files
require_once '../includes/admin.php';
require_once '../includes/user.php';
require_once '../includes/database.php';

// Create User and Admin objects
$user = new User();
$admin = new Admin();
// Create $userObj to be used in navbar.php
$userObj = $user;

// Check if user is logged in and is admin
if (!$user->isLoggedIn() || !$user->isAdmin()) {
    // Redirect to login page
    header('Location: ../login.php?error=unauthorized');
    exit;
}

// Handle actions
$action = isset($_GET['action']) ? $_GET['action'] : 'dashboard';
$message = '';

// Handle item approval
if (isset($_GET['approve_item'])) {
    $itemId = (int)$_GET['approve_item'];
    if ($admin->approveItem($itemId)) {
        $message = 'Item approved successfully.';
    } else {
        $message = 'Failed to approve item.';
    }
}

// Handle item removal
if (isset($_GET['remove_item'])) {
    $itemId = (int)$_GET['remove_item'];
    if ($admin->removeItem($itemId)) {
        $message = 'Item removed successfully.';
    } else {
        $message = 'Failed to remove item.';
    }
}

// Handle user ban
if (isset($_GET['ban_user'])) {
    $userId = (int)$_GET['ban_user'];
    if ($admin->banUser($userId)) {
        $message = 'User banned successfully.';
    } else {
        $message = 'Failed to ban user.';
    }
}

// Handle user unban
if (isset($_GET['unban_user'])) {
    $userId = (int)$_GET['unban_user'];
    if ($admin->unbanUser($userId)) {
        $message = 'User unbanned successfully.';
    } else {
        $message = 'Failed to unban user.';
    }
}

// Handle adding banned keyword
if (isset($_POST['add_keyword'])) {
    $keyword = trim($_POST['keyword']);
    if ($admin->addBannedKeyword($keyword)) {
        $message = 'Keyword added to ban list.';
    } else {
        $message = 'Failed to add keyword or keyword already exists.';
    }
}

// Handle removing banned keyword
if (isset($_GET['remove_keyword'])) {
    $keywordId = (int)$_GET['remove_keyword'];
    if ($admin->removeBannedKeyword($keywordId)) {
        $message = 'Keyword removed from ban list.';
    } else {
        $message = 'Failed to remove keyword.';
    }
}

// Handle adding category
if (isset($_POST['add_category'])) {
    $name = trim($_POST['category_name']);
    $description = trim($_POST['category_description']);
    
    if (empty($name)) {
        $message = 'Category name cannot be empty.';
    } else {
        $result = $admin->addCategory($name, $description);
        if ($result) {
            $message = 'Category added successfully.';
        } else {
            $message = 'Failed to add category.';
        }
    }
}

// Handle updating category
if (isset($_POST['update_category'])) {
    $categoryId = (int)$_POST['category_id'];
    $name = trim($_POST['category_name']);
    $description = trim($_POST['category_description']);
    
    if (empty($name)) {
        $message = 'Category name cannot be empty.';
    } else {
        $result = $admin->updateCategory($categoryId, $name, $description);
        if ($result) {
            $message = 'Category updated successfully.';
        } else {
            $message = 'Failed to update category.';
        }
    }
}

// Handle deleting category
if (isset($_GET['delete_category'])) {
    $categoryId = (int)$_GET['delete_category'];
    $result = $admin->deleteCategory($categoryId);
    
    if ($result) {
        $message = 'Category deleted successfully.';
    } else {
        $message = 'Failed to delete category. Make sure no items are using this category.';
    }
}

// Get data for current view
$flaggedItems = [];
$users = [];
$keywords = [];
$stats = [];
$categories = [];

switch ($action) {
    case 'flagged':
        $flaggedItems = $admin->getFlaggedItems();
        break;
    case 'users':
        $users = $admin->getUsers();
        break;
    case 'keywords':
        $keywords = $admin->getBannedKeywords();
        break;
    case 'categories':
        // We would need to add a getCategories method to the Admin class
        // For now we'll leave it empty
        break;
    case 'dashboard':
    default:
        $stats = $admin->getStatistics();
        break;
}

// Page title
$pageTitle = 'Admin Dashboard';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Lost & Found System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .admin-container {
            display: flex;
            min-height: calc(100vh - 100px);
        }
        
        .admin-sidebar {
            width: 250px;
            background-color: #f8f9fa;
            padding: 20px;
            border-right: 1px solid #dee2e6;
        }
        
        .admin-content {
            flex-grow: 1;
            padding: 20px;
        }
        
        .admin-menu {
            list-style: none;
            padding: 0;
        }
        
        .admin-menu li {
            margin-bottom: 10px;
        }
        
        .admin-menu a {
            display: block;
            padding: 10px;
            border-radius: 5px;
            text-decoration: none;
            color: #333;
            transition: background-color 0.2s;
        }
        
        .admin-menu a:hover, .admin-menu a.active {
            background-color: #e9ecef;
        }
        
        .admin-card {
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
        }
        
        .stat-card {
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 20px;
            text-align: center;
        }
        
        .stat-value {
            font-size: 2em;
            font-weight: bold;
            margin: 10px 0;
        }
        
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        table th, table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        
        table th {
            background-color: #f8f9fa;
        }
        
        .btn {
            display: inline-block;
            padding: 8px 16px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
        }
        
        .btn-danger {
            background-color: #dc3545;
        }
        
        .btn-success {
            background-color: #28a745;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-control {
            display: block;
            width: 100%;
            padding: 8px 12px;
            font-size: 16px;
            border: 1px solid #ced4da;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <?php include_once '../templates/navbar.php'; ?>
    
    <div class="admin-container">
        <div class="admin-sidebar">
            <h3>Admin Panel</h3>
            <ul class="admin-menu">
                <li><a href="index.php" class="<?php echo $action == 'dashboard' ? 'active' : ''; ?>">Dashboard</a></li>
                <li><a href="index.php?action=flagged" class="<?php echo $action == 'flagged' ? 'active' : ''; ?>">Flagged Items</a></li>
                <li><a href="index.php?action=users" class="<?php echo $action == 'users' ? 'active' : ''; ?>">Users Management</a></li>
                <li><a href="index.php?action=keywords" class="<?php echo $action == 'keywords' ? 'active' : ''; ?>">Banned Keywords</a></li>
                <li><a href="index.php?action=categories" class="<?php echo $action == 'categories' ? 'active' : ''; ?>">Categories</a></li>
                <li><a href="../dashboard.php">Return to Site</a></li>
            </ul>
        </div>
        
        <div class="admin-content">
            <h1><?php echo $pageTitle; ?></h1>
            
            <?php if (!empty($message)): ?>
                <div class="alert <?php echo strpos($message, 'Failed') !== false ? 'alert-danger' : 'alert-success'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($action == 'dashboard'): ?>
                <!-- Dashboard View -->
                <div class="admin-card">
                    <h2>System Statistics</h2>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <h3>Total Users</h3>
                            <div class="stat-value"><?php echo $stats['total_users']; ?></div>
                        </div>
                        <div class="stat-card">
                            <h3>Lost Items</h3>
                            <div class="stat-value"><?php echo $stats['total_lost']; ?></div>
                        </div>
                        <div class="stat-card">
                            <h3>Found Items</h3>
                            <div class="stat-value"><?php echo $stats['total_found']; ?></div>
                        </div>
                        <div class="stat-card">
                            <h3>Resolved Items</h3>
                            <div class="stat-value"><?php echo $stats['total_resolved']; ?></div>
                        </div>
                    </div>
                </div>
                
                <div class="admin-card">
                    <h2>Items by Category</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Total Items</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stats['items_by_category'] as $category): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($category['name']); ?></td>
                                    <td><?php echo $category['total']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="admin-card">
                    <h2>Recent Activity</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Title</th>
                                <th>Category</th>
                                <th>User</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stats['recent_activity'] as $item): ?>
                                <tr>
                                    <td><?php echo ucfirst(htmlspecialchars($item['type'])); ?></td>
                                    <td><?php echo htmlspecialchars($item['title']); ?></td>
                                    <td><?php echo htmlspecialchars($item['category_name']); ?></td>
                                    <td><?php echo htmlspecialchars($item['username']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($item['created_at'])); ?></td>
                                    <td><?php echo ucfirst(htmlspecialchars($item['status'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php elseif ($action == 'flagged'): ?>
                <!-- Flagged Items View -->
                <div class="admin-card">
                    <h2>Flagged Items for Review</h2>
                    
                    <?php if (empty($flaggedItems)): ?>
                        <p>No flagged items to review.</p>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Title</th>
                                    <th>Description</th>
                                    <th>User</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($flaggedItems as $item): ?>
                                    <tr>
                                        <td><?php echo ucfirst(htmlspecialchars($item['type'])); ?></td>
                                        <td><?php echo htmlspecialchars($item['title']); ?></td>
                                        <td><?php echo htmlspecialchars(substr($item['description'], 0, 100)) . '...'; ?></td>
                                        <td><?php echo htmlspecialchars($item['username']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($item['created_at'])); ?></td>
                                        <td>
                                            <a href="index.php?action=flagged&approve_item=<?php echo $item['item_id']; ?>" class="btn btn-success">Approve</a>
                                            <a href="index.php?action=flagged&remove_item=<?php echo $item['item_id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to remove this item?')">Remove</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            <?php elseif ($action == 'users'): ?>
                <!-- Users Management View -->
                <div class="admin-card">
                    <h2>Users Management</h2>
                    
                    <?php if (empty($users)): ?>
                        <p>No users found.</p>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Full Name</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Joined</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                        <td><?php echo ucfirst(htmlspecialchars($user['user_type'])); ?></td>
                                        <td><?php echo ucfirst(htmlspecialchars($user['status'])); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                        <td>
                                            <?php if ($user['status'] == 'banned'): ?>
                                                <a href="index.php?action=users&unban_user=<?php echo $user['user_id']; ?>" class="btn btn-success">Unban User</a>
                                            <?php else: ?>
                                                <a href="index.php?action=users&ban_user=<?php echo $user['user_id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to ban this user?')">Ban User</a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            <?php elseif ($action == 'keywords'): ?>
                <!-- Banned Keywords View -->
                <div class="admin-card">
                    <h2>Banned Keywords Management</h2>
                    
                    <form method="post" action="index.php?action=keywords">
                        <div class="form-group">
                            <label for="keyword">Add New Banned Keyword:</label>
                            <div style="display: flex;">
                                <input type="text" id="keyword" name="keyword" class="form-control" style="flex: 1; margin-right: 10px;" required>
                                <button type="submit" name="add_keyword" class="btn">Add Keyword</button>
                            </div>
                        </div>
                    </form>
                    
                    <?php if (empty($keywords)): ?>
                        <p>No banned keywords found.</p>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Keyword</th>
                                    <th>Added On</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($keywords as $keyword): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($keyword['keyword']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($keyword['created_at'])); ?></td>
                                        <td>
                                            <a href="index.php?action=keywords&remove_keyword=<?php echo $keyword['id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to remove this keyword?')">Remove</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            <?php elseif ($action == 'categories'): ?>
                <!-- Categories Management View -->
                <div class="admin-card">
                    <h2>Categories Management</h2>
                    
                    <form method="post" action="index.php?action=categories">
                        <h3>Add New Category</h3>
                        <div class="form-group">
                            <label for="category_name">Category Name:</label>
                            <input type="text" id="category_name" name="category_name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="category_description">Description:</label>
                            <textarea id="category_description" name="category_description" class="form-control" rows="3"></textarea>
                        </div>
                        <button type="submit" name="add_category" class="btn">Add Category</button>
                    </form>
                    
                    <?php if (empty($categories)): ?>
                        <p>No categories found or you need to implement getCategories() method.</p>
                    <?php else: ?>
                        <h3>Existing Categories</h3>
                        <table>
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categories as $category): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($category['name']); ?></td>
                                        <td><?php echo htmlspecialchars($category['description']); ?></td>
                                        <td>
                                            <a href="#" class="btn">Edit</a>
                                            <a href="index.php?action=categories&delete_category=<?php echo $category['category_id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this category?')">Delete</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
        </div>
    </div>
    
    <?php include_once '../templates/footer.php'; ?>
    
    <script>
        // Add some interactivity
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-hide alerts after 5 seconds
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    alert.style.opacity = '0';
                    setTimeout(function() {
                        alert.style.display = 'none';
                    }, 500);
                }, 5000);
            });
        });
    </script>
</body>
</html>