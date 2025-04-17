<?php
// Include necessary files
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/user.php';
require_once 'includes/item.php';

// Initialize User class
$userObj = new User();

// Initialize Item class
$itemObj = new Item();

// Get item ID from URL
$itemId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// If no item ID provided, redirect to home page
if ($itemId <= 0) {
    header('Location: index.php');
    exit;
}

// Debug output
error_log("Attempting to fetch item ID: " . $itemId);

// Get item details
$item = $itemObj->getItemById($itemId);

// Debug output
if (!$item) {
    error_log("Item ID " . $itemId . " not found in database");
} else {
    error_log("Item ID " . $itemId . " found successfully");
}

// If item not found, show 404 page
if (!$item) {
    header('HTTP/1.0 404 Not Found');
    include __DIR__ . '/404.php';
    exit;
}

// Check if user is logged in
$isLoggedIn = $userObj->isLoggedIn();
$isAdmin = $isLoggedIn && $userObj->isAdmin();
$isOwner = $isLoggedIn && $_SESSION['user_id'] == $item['user_id'];

// Get potential matches
$matches = $itemObj->getMatches($itemId);

// Sort matches by score (highest first)
usort($matches, function($a, $b) {
    return $b['match_score'] <=> $a['match_score'];
});

// Limit to top matches
$topMatches = array_slice($matches, 0, 5);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($item['title']); ?> - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>css/style.css">
</head>
<body>
    <!-- Navigation Bar -->
    <?php include 'templates/navbar.php'; ?>
    
    <div class="container my-5">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item">
                    <a href="index.php?type=<?php echo $item['type']; ?>">
                        <?php echo ucfirst($item['type']); ?> Items
                    </a>
                </li>
                <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($item['title']); ?></li>
            </ol>
        </nav>
        
        <!-- Actions Bar -->
        <?php if ($isLoggedIn && ($isOwner || $isAdmin)): ?>
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="btn-toolbar">
                        <?php if ($isOwner || $isAdmin): ?>
                            <a href="edit-item.php?id=<?php echo $item['item_id']; ?>" class="btn btn-primary me-2">
                                <i class="fas fa-edit me-1"></i> Edit Item
                            </a>
                            
                            <?php if ($item['status'] === 'open'): ?>
                                <a href="close-item.php?id=<?php echo $item['item_id']; ?>" class="btn btn-success me-2">
                                    <i class="fas fa-check-circle me-1"></i> 
                                    <?php echo $item['type'] === 'lost' ? 'Mark as Found' : 'Mark as Returned'; ?>
                                </a>
                            <?php endif; ?>
                            
                            <a href="delete-item.php?id=<?php echo $item['item_id']; ?>" class="btn btn-danger me-2" onclick="return confirm('Are you sure you want to delete this item?')">
                                <i class="fas fa-trash me-1"></i> Delete Item
                            </a>
                        <?php endif; ?>
                        
                        <?php if (!$isOwner && !$isAdmin): ?>
                            <a href="#" class="btn btn-warning me-2" data-bs-toggle="modal" data-bs-target="#flagModal">
                                <i class="fas fa-flag me-1"></i> Flag Item
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <!-- Item Details -->
            <div class="col-md-8">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-<?php echo $item['type'] === 'lost' ? 'danger' : 'success'; ?> bg-opacity-75 text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h2 class="mb-0">
                                <?php if ($item['type'] === 'lost'): ?>
                                    <i class="fas fa-search me-2"></i> Lost Item
                                <?php else: ?>
                                    <i class="fas fa-box-open me-2"></i> Found Item
                                <?php endif; ?>
                            </h2>
                            <span class="badge bg-light text-dark">
                                <?php echo htmlspecialchars($item['category_name']); ?>
                            </span>
                        </div>
                    </div>
                    
                    <?php if (!empty($item['image'])): ?>
                        <img src="<?php echo BASE_URL . 'uploads/' . $item['image']; ?>" class="card-img-top item-image" alt="<?php echo htmlspecialchars($item['title']); ?>">
                    <?php endif; ?>
                    
                    <div class="card-body">
                        <h3 class="card-title"><?php echo htmlspecialchars($item['title']); ?></h3>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <p class="mb-1">
                                    <i class="fas fa-calendar-alt me-2 text-muted"></i>
                                    <strong>Date <?php echo $item['type'] === 'lost' ? 'Lost' : 'Found'; ?>:</strong> 
                                    <?php echo date('F j, Y', strtotime($item['date_item'])); ?>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1">
                                    <i class="fas fa-map-marker-alt me-2 text-muted"></i>
                                    <strong>Location:</strong> 
                                    <?php echo htmlspecialchars($item['location']); ?>
                                </p>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <h5>Description</h5>
                            <p class="card-text"><?php echo nl2br(htmlspecialchars($item['description'])); ?></p>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="badge bg-<?php 
                                if ($item['status'] === 'open') echo 'info';
                                elseif ($item['status'] === 'closed') echo 'success';
                                else echo 'warning';
                            ?>">
                                <?php 
                                    if ($item['status'] === 'open') echo 'Open';
                                    elseif ($item['status'] === 'closed') {
                                        echo $item['type'] === 'lost' ? 'Found' : 'Returned';
                                    }
                                    else echo 'Under Review';
                                ?>
                            </span>
                            <small class="text-muted">
                                Posted on <?php echo date('M d, Y', strtotime($item['created_at'])); ?>
                            </small>
                        </div>
                    </div>
                </div>
                
                <!-- Potential Matches -->
                <?php if (!empty($topMatches) && ($isOwner || $isAdmin)): ?>
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-warning bg-opacity-10">
                            <h4 class="mb-0"><i class="fas fa-exchange-alt me-2 text-warning"></i>Potential Matches</h4>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <?php foreach ($topMatches as $match): ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="card h-100">
                                            <?php if (!empty($match['image'])): ?>
                                                <img src="<?php echo BASE_URL . 'uploads/' . $match['image']; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($match['title']); ?>" style="height: 150px; object-fit: cover;">
                                            <?php endif; ?>
                                            <div class="card-body position-relative">
                                                <div class="position-absolute top-0 end-0 mt-2 me-2">
                                                    <div class="match-score <?php
                                                        if ($match['match_score'] >= 70) echo 'high';
                                                        else if ($match['match_score'] >= 40) echo 'medium';
                                                        else echo 'low';
                                                    ?>">
                                                        <?php echo $match['match_score']; ?>%
                                                    </div>
                                                </div>
                                                <h5 class="card-title"><?php echo htmlspecialchars($match['title']); ?></h5>
                                                <p class="card-text small text-truncate"><?php echo htmlspecialchars($match['description']); ?></p>
                                                <p class="card-text mb-0 small">
                                                    <i class="fas fa-map-marker-alt me-1 text-muted"></i>
                                                    <?php echo htmlspecialchars($match['location']); ?>
                                                </p>
                                                <p class="card-text small">
                                                    <i class="fas fa-calendar-alt me-1 text-muted"></i>
                                                    <?php echo date('M d, Y', strtotime($match['date_item'])); ?>
                                                </p>
                                                <a href="item.php?id=<?php echo $match['item_id']; ?>" class="btn btn-sm btn-outline-primary">View Details</a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <?php if (count($matches) > 5): ?>
                                <div class="text-center mt-3">
                                    <a href="matches.php?id=<?php echo $item['item_id']; ?>" class="btn btn-outline-primary">View All Matches</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Sidebar -->
            <div class="col-md-4">
                <!-- Contact Information -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-user me-2"></i>Contact Information</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!$isLoggedIn): ?>
                            <div class="alert alert-info mb-0">
                                <i class="fas fa-info-circle me-2"></i>
                                Please <a href="login.php" class="alert-link">log in</a> to see contact information.
                            </div>
                        <?php else: ?>
                            <div class="d-flex align-items-center mb-3">
                                <div class="bg-light rounded-circle p-3 me-3">
                                    <i class="fas fa-user-circle fa-2x text-primary"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0"><?php echo htmlspecialchars($item['full_name']); ?></h6>
                                    <p class="text-muted mb-0 small">@<?php echo htmlspecialchars($item['username']); ?></p>
                                </div>
                            </div>
                            
                            <?php if ($isOwner || $isAdmin): ?>
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                        <span><i class="fas fa-envelope me-2 text-muted"></i> Email</span>
                                        <span><?php echo htmlspecialchars($item['email']); ?></span>
                                    </li>
                                    <?php if (!empty($item['phone'])): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                            <span><i class="fas fa-phone me-2 text-muted"></i> Phone</span>
                                            <span><?php echo htmlspecialchars($item['phone']); ?></span>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            <?php else: ?>
                                <p class="mb-3">
                                    To contact the <?php echo $item['type'] === 'lost' ? 'owner' : 'finder'; ?> of this item, please use the button below:
                                </p>
                                <div class="d-grid">
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#contactModal">
                                        <i class="fas fa-envelope me-2"></i>Contact User
                                    </button>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Similar Items -->
                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">
                            <i class="fas fa-search me-2"></i>
                            Similar <?php echo $item['type'] === 'lost' ? 'Found' : 'Lost'; ?> Items
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            <?php
                            $similarType = $item['type'] === 'lost' ? 'found' : 'lost';
                            $similarItems = $itemObj->getItems($similarType, $item['category_id'], null, 'open', 5);
                            
                            if (empty($similarItems)):
                            ?>
                                <li class="list-group-item text-center py-4">
                                    <i class="fas fa-info-circle text-muted mb-2 d-block fa-2x"></i>
                                    <p class="mb-0">No similar items found.</p>
                                </li>
                            <?php else: ?>
                                <?php foreach ($similarItems as $similarItem): ?>
                                    <li class="list-group-item">
                                        <div class="d-flex align-items-center">
                                            <?php if (!empty($similarItem['image'])): ?>
                                                <img src="<?php echo BASE_URL . 'uploads/' . $similarItem['image']; ?>" alt="<?php echo htmlspecialchars($similarItem['title']); ?>" class="me-3" style="width: 50px; height: 50px; object-fit: cover;">
                                            <?php else: ?>
                                                <div class="me-3 bg-light d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                                    <i class="fas fa-image text-muted"></i>
                                                </div>
                                            <?php endif; ?>
                                            <div>
                                                <h6 class="mb-0"><a href="item.php?id=<?php echo $similarItem['item_id']; ?>" class="text-decoration-none"><?php echo htmlspecialchars($similarItem['title']); ?></a></h6>
                                                <small class="text-muted">
                                                    <i class="fas fa-map-marker-alt me-1"></i> <?php echo htmlspecialchars($similarItem['location']); ?>
                                                </small>
                                            </div>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Contact Modal -->
    <div class="modal fade" id="contactModal" tabindex="-1" aria-labelledby="contactModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="contactModalLabel">Contact User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="send-message.php" method="POST">
                        <input type="hidden" name="item_id" value="<?php echo $item['item_id']; ?>">
                        <input type="hidden" name="receiver_id" value="<?php echo $item['user_id']; ?>">
                        
                        <div class="mb-3">
                            <label for="message" class="form-label">Your Message</label>
                            <textarea class="form-control" id="message" name="message" rows="5" required placeholder="Describe why you believe this might be your item or any information that might help the owner..."></textarea>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Send Message</button>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Flag Item Modal -->
    <div class="modal fade" id="flagModal" tabindex="-1" aria-labelledby="flagModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="flagModalLabel">Flag This Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to flag this item as inappropriate or suspicious?</p>
                    <p class="text-muted small">Flagged items will be reviewed by administrators and may be removed if they violate our terms of service.</p>
                    
                    <form action="flag-item.php" method="POST">
                        <input type="hidden" name="item_id" value="<?php echo $item['item_id']; ?>">
                        
                        <div class="mb-3">
                            <label for="reason" class="form-label">Reason for flagging</label>
                            <select class="form-select" id="reason" name="reason" required>
                                <option value="">Select a reason</option>
                                <option value="inappropriate">Inappropriate content</option>
                                <option value="spam">Spam or advertisement</option>
                                <option value="duplicate">Duplicate post</option>
                                <option value="other">Other reason</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="comments" class="form-label">Additional comments (optional)</label>
                            <textarea class="form-control" id="comments" name="comments" rows="3"></textarea>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-warning">Flag Item</button>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
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