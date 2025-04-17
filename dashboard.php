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

// Get user data
$userId = $_SESSION['user_id'];
$user = $userObj->getUserById($userId);

// Initialize Item class
$itemObj = new Item();

// Get user's lost items
$lostItems = $itemObj->getItems('lost', null, $userId);

// Get user's found items
$foundItems = $itemObj->getItems('found', null, $userId);

// Get potential matches for user's items
$matches = [];
foreach ($lostItems as $item) {
    $itemMatches = $itemObj->getMatches($item['item_id']);
    if (!empty($itemMatches)) {
        foreach ($itemMatches as $match) {
            $matches[] = [
                'match_score' => $match['match_score'],
                'lost_item' => $item,
                'found_item' => $match
            ];
        }
    }
}

foreach ($foundItems as $item) {
    $itemMatches = $itemObj->getMatches($item['item_id']);
    if (!empty($itemMatches)) {
        foreach ($itemMatches as $match) {
            $matches[] = [
                'match_score' => $match['match_score'],
                'found_item' => $item,
                'lost_item' => $match
            ];
        }
    }
}

// Sort matches by score (highest first)
usort($matches, function($a, $b) {
    return $b['match_score'] <=> $a['match_score'];
});

// Limit to top 5 matches
$topMatches = array_slice($matches, 0, 5);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>css/style.css">
</head>
<body>
    <!-- Navigation Bar -->
    <?php include 'templates/navbar.php'; ?>
    
    <div class="container my-5">
        <div class="row mb-4">
            <div class="col-md-8">
                <h1 class="mb-0">Dashboard</h1>
                <p class="text-muted">Welcome back, <?php echo htmlspecialchars($user['full_name']); ?>!</p>
            </div>
            <div class="col-md-4 text-md-end">
                <a href="report.php?type=lost" class="btn btn-danger me-2"><i class="fas fa-exclamation-circle me-1"></i> Report Lost Item</a>
                <a href="report.php?type=found" class="btn btn-success"><i class="fas fa-check-circle me-1"></i> Report Found Item</a>
            </div>
        </div>
        
        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card dashboard-card lost h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="card-title">Lost Items</h5>
                                <h2 class="mb-0"><?php echo count($lostItems); ?></h2>
                            </div>
                            <div class="bg-danger bg-opacity-10 p-3 rounded">
                                <i class="fas fa-search text-danger fa-2x"></i>
                            </div>
                        </div>
                        <a href="#lost-items" class="stretched-link text-decoration-none">View all lost items</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card dashboard-card found h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="card-title">Found Items</h5>
                                <h2 class="mb-0"><?php echo count($foundItems); ?></h2>
                            </div>
                            <div class="bg-success bg-opacity-10 p-3 rounded">
                                <i class="fas fa-box-open text-success fa-2x"></i>
                            </div>
                        </div>
                        <a href="#found-items" class="stretched-link text-decoration-none">View all found items</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card dashboard-card matches h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="card-title">Potential Matches</h5>
                                <h2 class="mb-0"><?php echo count($matches); ?></h2>
                            </div>
                            <div class="bg-warning bg-opacity-10 p-3 rounded">
                                <i class="fas fa-exchange-alt text-warning fa-2x"></i>
                            </div>
                        </div>
                        <a href="#matches" class="stretched-link text-decoration-none">View all potential matches</a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Top Matches -->
        <?php if (!empty($topMatches)): ?>
            <div class="card mb-4" id="matches">
                <div class="card-header bg-warning bg-opacity-10">
                    <h4 class="mb-0"><i class="fas fa-exchange-alt me-2 text-warning"></i>Top Potential Matches</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Match Score</th>
                                    <th>Lost Item</th>
                                    <th>Found Item</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($topMatches as $match): ?>
                                    <tr>
                                        <td>
                                            <div class="match-score <?php
                                                if ($match['match_score'] >= 70) echo 'high';
                                                else if ($match['match_score'] >= 40) echo 'medium';
                                                else echo 'low';
                                            ?>">
                                                <?php echo $match['match_score']; ?>%
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?php if (!empty($match['lost_item']['image'])): ?>
                                                    <img src="<?php echo BASE_URL . 'uploads/' . $match['lost_item']['image']; ?>" alt="Lost Item" class="me-3" style="width: 50px; height: 50px; object-fit: cover;">
                                                <?php else: ?>
                                                    <div class="me-3 bg-light d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                                        <i class="fas fa-image text-muted"></i>
                                                    </div>
                                                <?php endif; ?>
                                                <div>
                                                    <h6 class="mb-0"><?php echo htmlspecialchars($match['lost_item']['title']); ?></h6>
                                                    <small class="text-muted"><?php echo date('M d, Y', strtotime($match['lost_item']['date_item'])); ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?php if (!empty($match['found_item']['image'])): ?>
                                                    <img src="<?php echo BASE_URL . 'uploads/' . $match['found_item']['image']; ?>" alt="Found Item" class="me-3" style="width: 50px; height: 50px; object-fit: cover;">
                                                <?php else: ?>
                                                    <div class="me-3 bg-light d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                                        <i class="fas fa-image text-muted"></i>
                                                    </div>
                                                <?php endif; ?>
                                                <div>
                                                    <h6 class="mb-0"><?php echo htmlspecialchars($match['found_item']['title']); ?></h6>
                                                    <small class="text-muted"><?php echo date('M d, Y', strtotime($match['found_item']['date_item'])); ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <a href="item.php?id=<?php echo $match['lost_item']['item_id']; ?>" class="btn btn-sm btn-outline-danger me-1">
                                                <i class="fas fa-search"></i> Lost
                                            </a>
                                            <a href="item.php?id=<?php echo $match['found_item']['item_id']; ?>" class="btn btn-sm btn-outline-success">
                                                <i class="fas fa-box-open"></i> Found
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <?php if (count($matches) > 5): ?>
                        <div class="text-center mt-3">
                            <a href="matches.php" class="btn btn-outline-primary">View All Matches</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Lost Items -->
        <div class="card mb-4" id="lost-items">
            <div class="card-header bg-danger bg-opacity-10">
                <div class="d-flex justify-content-between align-items-center">
                    <h4 class="mb-0"><i class="fas fa-search me-2 text-danger"></i>Your Lost Items</h4>
                    <a href="report.php?type=lost" class="btn btn-sm btn-danger">Report Lost Item</a>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($lostItems)): ?>
                    <div class="alert alert-info mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        You haven't reported any lost items yet.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Date</th>
                                    <th>Location</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($lostItems as $item): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?php if (!empty($item['image'])): ?>
                                                    <img src="<?php echo BASE_URL . 'uploads/' . $item['image']; ?>" alt="<?php echo htmlspecialchars($item['title']); ?>" class="me-3" style="width: 50px; height: 50px; object-fit: cover;">
                                                <?php else: ?>
                                                    <div class="me-3 bg-light d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                                        <i class="fas fa-image text-muted"></i>
                                                    </div>
                                                <?php endif; ?>
                                                <div>
                                                    <h6 class="mb-0"><?php echo htmlspecialchars($item['title']); ?></h6>
                                                    <small class="text-muted"><?php echo htmlspecialchars($item['category_name']); ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($item['date_item'])); ?></td>
                                        <td><?php echo htmlspecialchars($item['location']); ?></td>
                                        <td>
                                            <?php if ($item['status'] === 'open'): ?>
                                                <span class="badge bg-info">Open</span>
                                            <?php elseif ($item['status'] === 'closed'): ?>
                                                <span class="badge bg-success">Resolved</span>
                                            <?php elseif ($item['status'] === 'flagged'): ?>
                                                <span class="badge bg-warning">Under Review</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="item.php?id=<?php echo $item['item_id']; ?>" class="btn btn-sm btn-outline-primary me-1" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="edit-item.php?id=<?php echo $item['item_id']; ?>" class="btn btn-sm btn-outline-secondary me-1" title="Edit Item">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <?php if ($item['status'] === 'open'): ?>
                                                <a href="close-item.php?id=<?php echo $item['item_id']; ?>" class="btn btn-sm btn-outline-success" title="Mark as Found">
                                                    <i class="fas fa-check"></i>
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Found Items -->
        <div class="card" id="found-items">
            <div class="card-header bg-success bg-opacity-10">
                <div class="d-flex justify-content-between align-items-center">
                    <h4 class="mb-0"><i class="fas fa-box-open me-2 text-success"></i>Your Found Items</h4>
                    <a href="report.php?type=found" class="btn btn-sm btn-success">Report Found Item</a>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($foundItems)): ?>
                    <div class="alert alert-info mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        You haven't reported any found items yet.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Date</th>
                                    <th>Location</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($foundItems as $item): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?php if (!empty($item['image'])): ?>
                                                    <img src="<?php echo BASE_URL . 'uploads/' . $item['image']; ?>" alt="<?php echo htmlspecialchars($item['title']); ?>" class="me-3" style="width: 50px; height: 50px; object-fit: cover;">
                                                <?php else: ?>
                                                    <div class="me-3 bg-light d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                                        <i class="fas fa-image text-muted"></i>
                                                    </div>
                                                <?php endif; ?>
                                                <div>
                                                    <h6 class="mb-0"><?php echo htmlspecialchars($item['title']); ?></h6>
                                                    <small class="text-muted"><?php echo htmlspecialchars($item['category_name']); ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($item['date_item'])); ?></td>
                                        <td><?php echo htmlspecialchars($item['location']); ?></td>
                                        <td>
                                            <?php if ($item['status'] === 'open'): ?>
                                                <span class="badge bg-info">Open</span>
                                            <?php elseif ($item['status'] === 'closed'): ?>
                                                <span class="badge bg-success">Returned</span>
                                            <?php elseif ($item['status'] === 'flagged'): ?>
                                                <span class="badge bg-warning">Under Review</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="item.php?id=<?php echo $item['item_id']; ?>" class="btn btn-sm btn-outline-primary me-1" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="edit-item.php?id=<?php echo $item['item_id']; ?>" class="btn btn-sm btn-outline-secondary me-1" title="Edit Item">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <?php if ($item['status'] === 'open'): ?>
                                                <a href="close-item.php?id=<?php echo $item['item_id']; ?>" class="btn btn-sm btn-outline-success" title="Mark as Returned">
                                                    <i class="fas fa-check"></i>
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <?php include 'templates/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo ASSETS_URL; ?>js/script.js"></script>
</body>
</html>