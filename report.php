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

// Get type parameter (lost or found)
$type = isset($_GET['type']) && in_array($_GET['type'], ['lost', 'found']) ? $_GET['type'] : 'lost';

// Get categories for the form
$categories = $itemObj->getCategories();

// Initialize variables
$title = '';
$description = '';
$categoryId = '';
$date = '';
$location = '';
$errors = [];
$success = false;

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $categoryId = $_POST['category'] ?? '';
    $date = $_POST['date'] ?? '';
    $location = trim($_POST['location'] ?? '');
    
    // Validate form data
    if (empty($title)) {
        $errors[] = 'Item title is required.';
    }
    
    if (empty($description)) {
        $errors[] = 'Item description is required.';
    }
    
    if (empty($categoryId)) {
        $errors[] = 'Please select a category.';
    }
    
    if (empty($date)) {
        $errors[] = 'Date is required.';
    } else {
        // Validate date format and range
        $dateObj = DateTime::createFromFormat('Y-m-d', $date);
        if (!$dateObj || $dateObj->format('Y-m-d') !== $date) {
            $errors[] = 'Invalid date format.';
        }
        
        // Check if date is not in the future
        $today = new DateTime();
        if ($dateObj > $today) {
            $errors[] = 'Date cannot be in the future.';
        }
    }
    
    if (empty($location)) {
        $errors[] = 'Location is required.';
    }
    
    // Handle image upload for found items
    $imageName = null;
    if ($type === 'found' || !empty($_FILES['image']['name'])) {
        if (empty($_FILES['image']['name']) && $type === 'found') {
            $errors[] = 'Image is required for found items.';
        } else if (!empty($_FILES['image']['name'])) {
            $uploadResult = $itemObj->uploadImage($_FILES['image']);
            
            if (!$uploadResult['success']) {
                $errors[] = $uploadResult['message'];
            } else {
                $imageName = $uploadResult['filename'];
            }
        }
    }
    
    // If no errors, add the item
    if (empty($errors)) {
        $result = $itemObj->addItem(
            $_SESSION['user_id'],
            $title,
            $description,
            $categoryId,
            $type,
            $date,
            $location,
            $imageName
        );
        
        if ($result['success']) {
            $success = true;
            $itemId = $result['item_id'];
            
            // Clear form data
            $title = '';
            $description = '';
            $categoryId = '';
            $date = '';
            $location = '';
        } else {
            $errors[] = $result['message'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report <?php echo ucfirst($type); ?> Item - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>css/style.css">
</head>
<body>
    <!-- Navigation Bar -->
    <?php include 'templates/navbar.php'; ?>
    
    <div class="container my-5">
        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="card shadow-sm">
                    <div class="card-header bg-<?php echo $type === 'lost' ? 'danger' : 'success'; ?> text-white">
                        <h4 class="mb-0">
                            <?php if ($type === 'lost'): ?>
                                <i class="fas fa-search me-2"></i> Report Lost Item
                            <?php else: ?>
                                <i class="fas fa-box-open me-2"></i> Report Found Item
                            <?php endif; ?>
                        </h4>
                    </div>
                    <div class="card-body">
                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i>
                                Your <?php echo $type; ?> item has been reported successfully!
                                <div class="mt-2">
                                    <a href="item.php?id=<?php echo $itemId; ?>" class="btn btn-sm btn-primary me-2">View Item</a>
                                    <a href="dashboard.php" class="btn btn-sm btn-secondary">Go to Dashboard</a>
                                </div>
                            </div>
                        <?php else: ?>
                            <?php if (!empty($errors)): ?>
                                <div class="alert alert-danger">
                                    <ul class="mb-0">
                                        <?php foreach ($errors as $error): ?>
                                            <li><?php echo $error; ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Item Report Type Selector -->
                            <div class="btn-group w-100 mb-4">
                                <a href="?type=lost" class="btn btn-lg <?php echo $type === 'lost' ? 'btn-danger' : 'btn-outline-danger'; ?>">
                                    <i class="fas fa-search me-2"></i> Report Lost Item
                                </a>
                                <a href="?type=found" class="btn btn-lg <?php echo $type === 'found' ? 'btn-success' : 'btn-outline-success'; ?>">
                                    <i class="fas fa-box-open me-2"></i> Report Found Item
                                </a>
                            </div>
                            
                            <!-- Report Form -->
                            <form action="report.php?type=<?php echo $type; ?>" method="POST" enctype="multipart/form-data" novalidate>
                                <div class="mb-3">
                                    <label for="title" class="form-label">Item Title</label>
                                    <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($title); ?>" required>
                                    <div class="form-text">Be specific and concise (e.g., "Blue Nike Backpack", "iPhone 12 Pro with Black Case")</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="category" class="form-label">Category</label>
                                    <select class="form-select" id="category" name="category" required>
                                        <option value="">-- Select Category --</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo $category['category_id']; ?>" <?php echo $categoryId == $category['category_id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($category['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control" id="description" name="description" rows="4" required><?php echo htmlspecialchars($description); ?></textarea>
                                    <div class="form-text">Provide as many details as possible: color, brand, identifying marks, contents, etc.</div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="date" class="form-label">
                                            <?php echo $type === 'lost' ? 'When did you lose it?' : 'When did you find it?'; ?>
                                        </label>
                                        <input type="date" class="form-control" id="date" name="date" value="<?php echo $date; ?>" max="<?php echo date('Y-m-d'); ?>" required>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="location" class="form-label">
                                            <?php echo $type === 'lost' ? 'Where did you lose it?' : 'Where did you find it?'; ?>
                                        </label>
                                        <input type="text" class="form-control" id="location" name="location" value="<?php echo htmlspecialchars($location); ?>" placeholder="Be as specific as possible" required>
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="image" class="form-label">
                                        Upload Image
                                        <?php if ($type === 'found'): ?>
                                            <span class="text-danger">*</span>
                                            <small class="text-danger">(required for found items)</small>
                                        <?php else: ?>
                                            <small class="text-muted">(optional for lost items)</small>
                                        <?php endif; ?>
                                    </label>
                                    <input type="file" class="form-control" id="image" name="image" accept="image/*" <?php echo $type === 'found' ? 'required' : ''; ?>>
                                    <div class="form-text">Accepted formats: JPG, JPEG, PNG, GIF. Max size: 5MB.</div>
                                </div>
                                
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-<?php echo $type === 'lost' ? 'danger' : 'success'; ?> btn-lg">
                                        Submit Report
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="card mt-4 bg-light">
                    <div class="card-body">
                        <h5><i class="fas fa-info-circle me-2"></i>Tips for a Successful <?php echo ucfirst($type); ?> Item Report</h5>
                        <ul class="mb-0">
                            <li>Be as specific as possible in your description.</li>
                            <li>Include unique identifiers or marks that only the owner would know.</li>
                            <li>For electronics, include serial numbers if available.</li>
                            <li>Select accurate location and date information to improve matching.</li>
                            <li>
                                <?php if ($type === 'lost'): ?>
                                    Consider adding a photo even though it's optional - it improves matching chances.
                                <?php else: ?>
                                    Take a clear photo of the item, but avoid showing uniquely identifying features in the image.
                                <?php endif; ?>
                            </li>
                            <li>Check your dashboard regularly for potential matches.</li>
                        </ul>
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