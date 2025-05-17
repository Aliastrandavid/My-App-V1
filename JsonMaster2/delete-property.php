<?php
require_once 'includes/functions.php';

// Initialize variables
$error = null;
$success = false;
$propertyId = null;

// Check if we have an ID
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $propertyId = $_GET['id'];
    
    try {
        // Delete the property
        $success = deleteProperty($propertyId);
        
        if (!$success) {
            throw new Exception("Failed to delete property");
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
} else {
    $error = "No property ID specified";
}
?>

<?php include 'includes/header.php'; ?>

<div class="card mt-5">
    <div class="card-header <?php echo $success ? 'bg-success text-white' : 'bg-danger text-white'; ?>">
        <h2 class="card-title mb-0">
            <?php if ($success): ?>
                <i class="fas fa-check-circle me-2"></i>Success
            <?php else: ?>
                <i class="fas fa-exclamation-triangle me-2"></i>Error
            <?php endif; ?>
        </h2>
    </div>
    <div class="card-body">
        <?php if ($success): ?>
            <div class="alert alert-success">
                <p>Property #<?php echo htmlspecialchars($propertyId); ?> was successfully deleted.</p>
            </div>
            <div class="d-grid gap-2">
                <a href="index.php" class="btn btn-primary">
                    <i class="fas fa-list me-2"></i>Return to Property Listing
                </a>
            </div>
        <?php else: ?>
            <div class="alert alert-danger">
                <p><strong>Failed to delete property:</strong> <?php echo htmlspecialchars($error); ?></p>
            </div>
            <div class="d-grid gap-2">
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-list me-2"></i>Return to Property Listing
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>