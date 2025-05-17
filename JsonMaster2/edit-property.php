<?php
require_once 'includes/functions.php';

$error = null;
$property = null;

// Check if we have an ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $error = "No property ID specified";
} else {
    try {
        $property = getPropertyById($_GET['id']);
        if (!$property) {
            $error = "Property not found";
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<?php include 'includes/header.php'; ?>

<div class="mb-4">
    <a href="index.php" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-2"></i>Back to Listing
    </a>
    <?php if ($property): ?>
    <a href="property-details.php?id=<?php echo $property['id']; ?>" class="btn btn-outline-info ms-2">
        <i class="fas fa-eye me-2"></i>View Details
    </a>
    <?php endif; ?>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
    </div>
<?php elseif ($property): ?>
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h2 class="card-title mb-0">
                <i class="fas fa-edit me-2"></i>
                Edit Property #<?php echo htmlspecialchars($property['reference']); ?>
            </h2>
        </div>
        <div class="card-body">
            <form id="propertyForm" action="save-property.php" method="post" class="needs-validation" novalidate>
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($property['id']); ?>">
                
                <!-- Basic Information Card -->
                <div class="card mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Basic Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="form-group">
                                    <label for="agency" class="form-label">Agency</label>
                                    <input type="number" class="form-control" id="agency" name="agency" 
                                           value="<?php echo htmlspecialchars($property['agency']); ?>" required>
                                    <div class="invalid-feedback">
                                        Please provide an agency number.
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <div class="form-group">
                                    <label for="quality" class="form-label">Quality</label>
                                    <input type="number" class="form-control" id="quality" name="quality" 
                                           value="<?php echo htmlspecialchars($property['quality']); ?>" required>
                                    <div class="invalid-feedback">
                                        Please provide a quality score.
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="form-group">
                                    <label for="agency_co_id" class="form-label">Agency Co-ID</label>
                                    <input type="text" class="form-control" id="agency_co_id" name="agency_co_id" 
                                           value="<?php echo htmlspecialchars($property['agency_co_id'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <div class="form-group">
                                    <label for="brand" class="form-label">Brand</label>
                                    <input type="text" class="form-control" id="brand" name="brand" 
                                           value="<?php echo htmlspecialchars($property['brand'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="sector" class="form-label">Sector</label>
                            <input type="text" class="form-control" id="sector" name="sector" 
                                   value="<?php echo htmlspecialchars($property['sector'] ?? ''); ?>">
                        </div>
                    </div>
                </div>
                
                <!-- Type and Subtype Card -->
                <?php if (isset($property['type']) || isset($property['subtype'])): ?>
                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-home me-2"></i>Property Classification</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php if (isset($property['type'])): ?>
                            <div class="col-md-6 mb-3">
                                <div class="form-group">
                                    <label for="type" class="form-label">Type</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="type" name="type" 
                                               value="<?php echo htmlspecialchars($property['type']); ?>">
                                        <span class="input-group-text bg-success text-white">
                                            <?php echo htmlspecialchars(getPropertyTypeLabel($property['type'])); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (isset($property['subtype'])): ?>
                            <div class="col-md-6 mb-3">
                                <div class="form-group">
                                    <label for="subtype" class="form-label">Subtype</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="subtype" name="subtype" 
                                               value="<?php echo htmlspecialchars($property['subtype']); ?>">
                                        <span class="input-group-text bg-success text-white">
                                            <?php echo htmlspecialchars(getPropertySubtypeLabel($property['subtype'])); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- User Information Section -->
                <?php if (isset($property['user'])): ?>
                <h4 class="mt-4 mb-3">User Information</h4>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <div class="form-group">
                            <label for="user_firstname" class="form-label">First Name</label>
                            <input type="text" class="form-control" id="user_firstname" name="user[firstname]" 
                                   value="<?php echo htmlspecialchars($property['user']['firstname']); ?>" required>
                            <div class="invalid-feedback">
                                Please provide a first name.
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <div class="form-group">
                            <label for="user_lastname" class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="user_lastname" name="user[lastname]" 
                                   value="<?php echo htmlspecialchars($property['user']['lastname']); ?>" required>
                            <div class="invalid-feedback">
                                Please provide a last name.
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <div class="form-group">
                            <label for="user_email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="user_email" name="user[email]" 
                                   value="<?php echo htmlspecialchars($property['user']['email']); ?>" required>
                            <div class="invalid-feedback">
                                Please provide a valid email.
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <div class="form-group">
                            <label for="user_phone" class="form-label">Phone</label>
                            <input type="text" class="form-control" id="user_phone" name="user[phone]" 
                                   value="<?php echo htmlspecialchars($property['user']['phone']); ?>">
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <div class="form-group">
                            <label for="user_mobile" class="form-label">Mobile</label>
                            <input type="text" class="form-control" id="user_mobile" name="user[mobile]" 
                                   value="<?php echo htmlspecialchars($property['user']['mobile']); ?>">
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <div class="form-group">
                            <label for="user_language" class="form-label">Language</label>
                            <input type="text" class="form-control" id="user_language" name="user[language]" 
                                   value="<?php echo htmlspecialchars($property['user']['language']); ?>">
                        </div>
                    </div>
                </div>
                
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="user_active" name="user[active]" 
                           <?php echo $property['user']['active'] ? 'checked' : ''; ?> value="1">
                    <label class="form-check-label" for="user_active">
                        User is active
                    </label>
                </div>
                <?php endif; ?>
                
                <!-- Advanced section button -->
                <div class="d-grid gap-2 mb-4 mt-4">
                    <button type="button" class="btn btn-outline-secondary" id="toggleAdvanced" onclick="toggleAdvancedFields()">
                        Show Advanced Fields <i class="fas fa-chevron-down"></i>
                    </button>
                </div>
                
                <!-- Advanced Fields Section (initially hidden) -->
                <div id="advancedSection" class="d-none">
                    <h4 class="mt-4 mb-3">Advanced Properties</h4>
                    
                    <?php foreach ($property as $key => $value): ?>
                        <?php if (!in_array($key, ['id', 'reference', 'agency', 'quality', 'agency_co_id', 'brand', 'sector', 'user'])): ?>
                            <div class="form-group mb-3">
                                <label for="<?php echo htmlspecialchars($key); ?>" class="form-label">
                                    <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $key))); ?>
                                </label>
                                
                                <?php if (is_array($value)): ?>
                                    <textarea class="form-control" id="<?php echo htmlspecialchars($key); ?>" 
                                              name="<?php echo htmlspecialchars($key); ?>" rows="5"><?php 
                                        echo htmlspecialchars(json_encode($value, JSON_PRETTY_PRINT)); 
                                    ?></textarea>
                                    <div class="form-text">Enter as JSON format</div>
                                <?php elseif (is_bool($value)): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" 
                                               id="<?php echo htmlspecialchars($key); ?>" 
                                               name="<?php echo htmlspecialchars($key); ?>" 
                                               <?php echo $value ? 'checked' : ''; ?> value="1">
                                        <label class="form-check-label" for="<?php echo htmlspecialchars($key); ?>">
                                            Enabled
                                        </label>
                                    </div>
                                <?php else: ?>
                                    <input type="text" class="form-control" id="<?php echo htmlspecialchars($key); ?>" 
                                           name="<?php echo htmlspecialchars($key); ?>" 
                                           value="<?php echo htmlspecialchars($value); ?>">
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                
                <div class="d-grid gap-2 mt-4">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-save me-2"></i>Save Changes
                    </button>
                    <a href="property-details.php?id=<?php echo $property['id']; ?>" class="btn btn-secondary">
                        <i class="fas fa-times me-2"></i>Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>