<?php
// Set the HTTP response code
http_response_code(404);

// Include necessary files
require_once 'includes/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page Not Found - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>css/style.css">
</head>
<body>
    <!-- Navigation Bar -->
    <?php include 'templates/navbar.php'; ?>
    
    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-md-8 text-center">
                <div class="card shadow-sm">
                    <div class="card-body py-5">
                        <i class="fas fa-exclamation-circle text-danger fa-5x mb-4"></i>
                        <h1 class="display-4">404</h1>
                        <h2 class="mb-4">Page Not Found</h2>
                        <p class="lead">Sorry, the item or page you're looking for doesn't exist or has been removed.</p>
                        <div class="mt-5">
                            <a href="index.php" class="btn btn-primary me-2">
                                <i class="fas fa-home me-2"></i>Return to Home
                            </a>
                            <a href="javascript:history.back()" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Go Back
                            </a>
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