<?php
// Include necessary files
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/user.php';
require_once 'includes/item.php';

// Initialize Item class
$itemObj = new Item();

// Get categories for filter
$categories = $itemObj->getCategories();

// Set default filters
$type = isset($_GET['type']) ? $_GET['type'] : null;
$categoryId = isset($_GET['category']) ? (int)$_GET['category'] : null;
$searchQuery = isset($_GET['search']) ? $_GET['search'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Get items based on filters
$items = [];
if (!empty($searchQuery)) {
    $items = $itemObj->searchItems($searchQuery, $type, $categoryId, $limit, $offset);
} else {
    $items = $itemObj->getItems($type, $categoryId, null, 'open', $limit, $offset);
}

// Check if user is logged in
$userObj = new User();
$isLoggedIn = $userObj->isLoggedIn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>css/style.css">
</head>
<body>
    <!-- Navigation Bar -->
    <?php include 'templates/navbar.php'; ?>
    
    <!-- Hero Section -->
    <div class="container-fluid bg-primary text-white py-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h1>Smart Lost & Found</h1>
                    <p class="lead">A secure platform to report and track lost or found items in your community.</p>
                    <?php if (!$isLoggedIn): ?>
                        <a href="register.php" class="btn btn-light me-2">Register</a>
                        <a href="login.php" class="btn btn-outline-light">Login</a>
                    <?php else: ?>
                        <a href="report.php?type=lost" class="btn btn-danger me-2">Report Lost Item</a>
                        <a href="report.php?type=found" class="btn btn-success">Report Found Item</a>
                    <?php endif; ?>
                </div>
                <div class="col-md-6 text-center">
                    <img src="<?php echo ASSETS_URL; ?>images/lost-found.svg" alt="Lost and Found" class="img-fluid" style="max-height: 300px;">
                </div>
            </div>
        </div>
    </div>
    
    <!-- Search and Filter Section -->
    <div class="container mt-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <form action="index.php" method="GET" class="row g-3">
                    <div class="col-md-4">
                        <div class="input-group">
                            <input type="text" class="form-control" placeholder="Search items..." name="search" value="<?php echo htmlspecialchars($searchQuery); ?>">
                            <button class="btn btn-outline-secondary" type="submit"><i class="fas fa-search"></i></button>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" name="type" onchange="this.form.submit()">
                            <option value="">All Types</option>
                            <option value="lost" <?php echo $type === 'lost' ? 'selected' : ''; ?>>Lost Items</option>
                            <option value="found" <?php echo $type === 'found' ? 'selected' : ''; ?>>Found Items</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" name="category" onchange="this.form.submit()">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['category_id']; ?>" <?php echo $categoryId === (int)$category['category_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <a href="index.php" class="btn btn-outline-primary w-100">Reset Filters</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Items List Section -->
    <div class="container my-4">
        <h2><?php echo $type === 'lost' ? 'Lost' : ($type === 'found' ? 'Found' : 'Lost & Found'); ?> Items</h2>
        
        <?php if (empty($items)): ?>
            <div class="alert alert-info">
                No items found matching your criteria. Try adjusting your filters or search query.
            </div>
        <?php else: ?>
            <div class="row row-cols-1 row-cols-md-3 g-4">
                <?php foreach ($items as $item): ?>
                    <div class="col">
                        <div class="card h-100 shadow-sm">
                            <?php if (!empty($item['image'])): ?>
                                <img src="<?php echo BASE_URL . 'uploads/' . $item['image']; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($item['title']); ?>" style="height: 200px; object-fit: cover;">
                            <?php else: ?>
                                <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                                    <i class="fas fa-image fa-3x text-muted"></i>
                                </div>
                            <?php endif; ?>
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="badge bg-<?php echo $item['type'] === 'lost' ? 'danger' : 'success'; ?>">
                                        <?php echo ucfirst($item['type']); ?>
                                    </span>
                                    <small class="text-muted"><?php echo date('M d, Y', strtotime($item['date_item'])); ?></small>
                                </div>
                                <h5 class="card-title"><?php echo htmlspecialchars($item['title']); ?></h5>
                                <p class="card-text text-truncate"><?php echo htmlspecialchars($item['description']); ?></p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($item['location']); ?>
                                    </small>
                                    <small class="text-muted">
                                        <i class="fas fa-tag"></i> <?php echo htmlspecialchars($item['category_name']); ?>
                                    </small>
                                </div>
                            </div>
                            <div class="card-footer bg-transparent">
                                <a href="item.php?id=<?php echo $item['item_id']; ?>" class="btn btn-sm btn-primary w-100">View Details</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Pagination -->
            <nav class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php
                    // Simple pagination - in a real app, you'd calculate total pages
                    $prevPage = max(1, $page - 1);
                    $nextPage = $page + 1;
                    ?>
                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $prevPage; ?>&type=<?php echo $type; ?>&category=<?php echo $categoryId; ?>&search=<?php echo urlencode($searchQuery); ?>">Previous</a>
                    </li>
                    <li class="page-item active">
                        <span class="page-link"><?php echo $page; ?></span>
                    </li>
                    <li class="page-item <?php echo count($items) < $limit ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $nextPage; ?>&type=<?php echo $type; ?>&category=<?php echo $categoryId; ?>&search=<?php echo urlencode($searchQuery); ?>">Next</a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
    
    <!-- How It Works Section -->
    <div class="container-fluid bg-light py-5">
        <div class="container">
            <h2 class="text-center mb-4">How It Works</h2>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center">
                            <div class="display-4 text-primary mb-3">
                                <i class="fas fa-user-plus"></i>
                            </div>
                            <h4>1. Create an Account</h4>
                            <p>Register with your email to get started. This helps us maintain security and allows you to track your lost/found items.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center">
                            <div class="display-4 text-primary mb-3">
                                <i class="fas fa-file-alt"></i>
                            </div>
                            <h4>2. Post Your Item</h4>
                            <p>Report a lost or found item with details like category, description, date, location, and photos to help with identification.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center">
                            <div class="display-4 text-primary mb-3">
                                <i class="fas fa-exchange-alt"></i>
                            </div>
                            <h4>3. Get Matched</h4>
                            <p>Our system will automatically match lost and found items based on details. You'll receive notifications of potential matches.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <?php include 'templates/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo ASSETS_URL; ?>js/script.js"></script>
</body>
</html>