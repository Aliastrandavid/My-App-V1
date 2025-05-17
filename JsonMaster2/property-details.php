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
                <i class="fas fa-building me-2"></i>
                Property #<?php echo htmlspecialchars($property['reference']); ?> Details
            </h2>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-6">
                    <div class="btn-group">
                        <a href="edit-property.php?id=<?php echo $property['id']; ?>" class="btn btn-warning">
                            <i class="fas fa-edit me-2"></i>Edit This Property
                        </a>
                        <button type="button" class="btn btn-danger" 
                                onclick="confirmDelete(<?php echo $property['id']; ?>, '<?php echo htmlspecialchars($property['reference']); ?>')">
                            <i class="fas fa-trash-alt me-2"></i>Delete Property
                        </button>
                    </div>
                </div>
            </div>
            
            <?php if (isset($property['pictures']) && !empty($property['pictures'])): ?>
            <div class="property-gallery mb-4">
                <h4 class="mb-3"><i class="fas fa-images me-2"></i>Property Gallery</h4>
                <div class="row">
                    <?php foreach ($property['pictures'] as $index => $picture): ?>
                        <div class="col-md-3 col-sm-6 mb-4">
                            <a href="#" data-bs-toggle="modal" data-bs-target="#imageModal" data-bs-image="<?php echo htmlspecialchars($picture['url']); ?>">
                                <img src="<?php echo htmlspecialchars($picture['url']); ?>" class="gallery-thumbnail" alt="Property image <?php echo $index + 1; ?>">
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Image Modal -->
            <div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="imageModalLabel">Property Image</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body text-center">
                            <img src="" id="modalImage" class="modal-image" alt="Property Image">
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (isset($property['status']) || isset($property['category'])): ?>
            <div class="row mb-4">
                <?php if (isset($property['category'])): ?>
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header bg-warning">
                            <h5 class="mb-0 text-dark"><i class="fas fa-tag me-2"></i>Category</h5>
                        </div>
                        <div class="card-body">
                            <span class="badge bg-warning text-dark fs-5">
                                <?php echo htmlspecialchars(getPropertyCategoryLabel($property['category'])); ?>
                            </span>
                            <small class="text-muted d-block mt-1">(ID: <?php echo htmlspecialchars($property['category']); ?>)</small>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (isset($property['status'])): ?>
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header bg-<?php echo $property['status'] == 1 ? 'success' : ($property['status'] == 3 ? 'danger' : 'warning'); ?> text-white">
                            <h5 class="mb-0"><i class="fas fa-check-circle me-2"></i>Status</h5>
                        </div>
                        <div class="card-body">
                            <span class="badge bg-<?php echo $property['status'] == 1 ? 'success' : ($property['status'] == 3 ? 'danger' : 'warning'); ?> fs-5">
                                <?php echo htmlspecialchars(getPropertyStatusLabel($property['status'])); ?>
                            </span>
                            <small class="text-muted d-block mt-1">(ID: <?php echo htmlspecialchars($property['status']); ?>)</small>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <!-- Price Information -->
            <?php if (isset($property['price']) && isset($property['price']['value'])): ?>
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-euro-sign me-2"></i>Price Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-striped">
                                <tbody>
                                    <tr>
                                        <th scope="row" width="40%">Value</th>
                                        <td class="fw-bold text-primary"><?php echo number_format($property['price']['value'], 0, ',', ' '); ?> €</td>
                                    </tr>
                                    <?php if (isset($property['price']['fees']) && $property['price']['fees'] > 0): ?>
                                    <tr>
                                        <th scope="row">Fees</th>
                                        <td><?php echo number_format($property['price']['fees'], 0, ',', ' '); ?> €</td>
                                    </tr>
                                    <?php endif; ?>
                                    <?php if (isset($property['price']['period'])): ?>
                                    <tr>
                                        <th scope="row">Period</th>
                                        <td><?php echo htmlspecialchars($property['price']['period']); ?></td>
                                    </tr>
                                    <?php endif; ?>
                                    <?php if (isset($property['price']['currency'])): ?>
                                    <tr>
                                        <th scope="row">Currency</th>
                                        <td><?php echo htmlspecialchars($property['price']['currency']); ?></td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Location Information -->
            <?php if (isset($property['city']) || isset($property['region'])): ?>
            <div class="card mb-4">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="fas fa-map-marker-alt me-2"></i>Location</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-striped">
                                <tbody>
                                    <?php if (isset($property['city']) && isset($property['city']['name'])): ?>
                                    <tr>
                                        <th scope="row" width="40%">City</th>
                                        <td>
                                            <strong><?php echo htmlspecialchars($property['city']['name']); ?></strong>
                                            <?php if (isset($property['city']['zipcode'])): ?>
                                                (<?php echo htmlspecialchars($property['city']['zipcode']); ?>)
                                            <?php endif; ?>
                                            <br>
                                            <small class="text-muted">(ID: <?php echo htmlspecialchars($property['city']['id']); ?>)</small>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                    <?php if (isset($property['region']) && isset($property['region']['name'])): ?>
                                    <tr>
                                        <th scope="row">Region</th>
                                        <td>
                                            <strong><?php echo htmlspecialchars($property['region']['name']); ?></strong>
                                            <br>
                                            <small class="text-muted">(ID: <?php echo htmlspecialchars($property['region']['id']); ?>)</small>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                    <?php if (isset($property['sector'])): ?>
                                    <tr>
                                        <th scope="row">Sector</th>
                                        <td><?php echo formatPropertyValue($property['sector']); ?></td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Comments Information -->
            <?php if (isset($property['comments']) && !empty($property['comments'])): ?>
            <div class="card mb-4">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="fas fa-comments me-2"></i>Comments</h5>
                </div>
                <div class="card-body">
                    <?php foreach ($property['comments'] as $index => $comment): ?>
                        <div class="comment-card mb-3">
                            <?php if (isset($comment['title']) && !empty($comment['title'])): ?>
                                <h5 class="comment-title"><?php echo htmlspecialchars($comment['title']); ?></h5>
                            <?php endif; ?>
                            
                            <?php if (isset($comment['text']) && !empty($comment['text'])): ?>
                                <div class="comment-text">
                                    <?php echo nl2br(htmlspecialchars($comment['text'])); ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (isset($comment['date']) && !empty($comment['date'])): ?>
                                <div class="comment-date text-muted small mt-2">
                                    <i class="fas fa-calendar-alt me-1"></i>
                                    <?php echo date('F j, Y', strtotime($comment['date'])); ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!$index == count($property['comments']) - 1): ?>
                                <hr class="my-3">
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Basic Information</h5>
                        </div>
                        <div class="card-body">
                            <table class="table table-striped">
                                <tbody>
                                    <tr>
                                        <th scope="row" width="40%">ID</th>
                                        <td><?php echo htmlspecialchars($property['id']); ?></td>
                                    </tr>
                                    <tr>
                                        <th scope="row">Reference</th>
                                        <td><?php echo htmlspecialchars($property['reference']); ?></td>
                                    </tr>
                                    <tr>
                                        <th scope="row">Agency</th>
                                        <td><?php echo htmlspecialchars($property['agency']); ?></td>
                                    </tr>
                                    <tr>
                                        <th scope="row">Quality</th>
                                        <td><?php echo htmlspecialchars($property['quality']); ?></td>
                                    </tr>
                                    <?php if (isset($property['agency_co_id'])): ?>
                                    <tr>
                                        <th scope="row">Agency Co-ID</th>
                                        <td><?php echo formatPropertyValue($property['agency_co_id']); ?></td>
                                    </tr>
                                    <?php endif; ?>
                                    <?php if (isset($property['brand'])): ?>
                                    <tr>
                                        <th scope="row">Brand</th>
                                        <td><?php echo formatPropertyValue($property['brand']); ?></td>
                                    </tr>
                                    <?php endif; ?>
                                    <?php if (isset($property['sector'])): ?>
                                    <tr>
                                        <th scope="row">Sector</th>
                                        <td><?php echo formatPropertyValue($property['sector']); ?></td>
                                    </tr>
                                    <?php endif; ?>
                                    
                                    <?php if (isset($property['type'])): ?>
                                    <tr>
                                        <th scope="row">Type</th>
                                        <td>
                                            <span class="badge bg-success"><?php echo htmlspecialchars(getPropertyTypeLabel($property['type'])); ?></span>
                                            <span class="text-muted">(ID: <?php echo htmlspecialchars($property['type']); ?>)</span>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                    
                                    <?php if (isset($property['subtype'])): ?>
                                    <tr>
                                        <th scope="row">Subtype</th>
                                        <td>
                                            <span class="badge bg-success"><?php echo htmlspecialchars(getPropertySubtypeLabel($property['subtype'])); ?></span>
                                            <span class="text-muted">(ID: <?php echo htmlspecialchars($property['subtype']); ?>)</span>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <?php if (isset($property['user'])): ?>
                    <div class="card mb-4">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i class="fas fa-user me-2"></i>User Information</h5>
                        </div>
                        <div class="card-body">
                            <?php if (isset($property['user']['picture']) && !empty($property['user']['picture'])): ?>
                            <div class="text-center mb-3">
                                <img src="<?php echo htmlspecialchars($property['user']['picture']); ?>" alt="Contact Picture" class="contact-image">
                            </div>
                            <?php endif; ?>
                            <table class="table table-striped">
                                <tbody>
                                    <tr>
                                        <th scope="row" width="40%">Name</th>
                                        <td>
                                            <?php 
                                            echo htmlspecialchars($property['user']['firstname'] . ' ' . $property['user']['lastname']); 
                                            ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">Email</th>
                                        <td><?php echo htmlspecialchars($property['user']['email']); ?></td>
                                    </tr>
                                    <tr>
                                        <th scope="row">Phone</th>
                                        <td><?php echo htmlspecialchars($property['user']['phone']); ?></td>
                                    </tr>
                                    <tr>
                                        <th scope="row">Mobile</th>
                                        <td><?php echo htmlspecialchars($property['user']['mobile']); ?></td>
                                    </tr>
                                    <tr>
                                        <th scope="row">Language</th>
                                        <td><?php echo htmlspecialchars($property['user']['language']); ?></td>
                                    </tr>
                                    <tr>
                                        <th scope="row">Active</th>
                                        <td><?php echo $property['user']['active'] ? 'Yes' : 'No'; ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if (isset($property['orientations']) && !empty($property['orientations'])): ?>
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-compass me-2"></i>Property Orientations</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach ($property['orientations'] as $orientation): ?>
                            <div class="col-md-3 col-sm-6 mb-2">
                                <span class="badge bg-info">
                                    <?php echo htmlspecialchars(getPropertyOrientationLabel($orientation)); ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (isset($property['regulations']) && !empty($property['regulations'])): ?>
            <div class="card mb-4">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="fas fa-certificate me-2"></i>Energy Regulations</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach ($property['regulations'] as $regulation): ?>
                            <?php if (in_array($regulation['type'], [1, 2]) && isset($regulation['graph'])): ?>
                                <div class="col-md-6 mb-3">
                                    <div class="card">
                                        <div class="card-header">
                                            <h6 class="mb-0">
                                                <?php if ($regulation['type'] == 1): ?>
                                                    <i class="fas fa-bolt me-2"></i>Energy Performance
                                                <?php elseif ($regulation['type'] == 2): ?>
                                                    <i class="fas fa-cloud me-2"></i>Greenhouse Gas Emissions
                                                <?php endif; ?>
                                                - Grade <?php echo htmlspecialchars($regulation['label']); ?>
                                            </h6>
                                        </div>
                                        <div class="card-body text-center">
                                            <img src="<?php echo htmlspecialchars($regulation['graph']); ?>" 
                                                 alt="Energy Regulation Graph" 
                                                 class="img-fluid">
                                            <p class="mt-2">
                                                <strong>Value:</strong> <?php echo htmlspecialchars($regulation['value']); ?>
                                                <br>
                                                <strong>Date:</strong> <?php echo date('F j, Y', strtotime($regulation['date'])); ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="accordion mt-4" id="propertyAccordion">
                <?php foreach ($property as $key => $value): ?>
                    <?php if (!in_array($key, ['id', 'reference', 'agency', 'quality', 'user', 'pictures', 'regulations', 'orientations'])): ?>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="heading<?php echo htmlspecialchars($key); ?>">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                                    data-bs-target="#collapse<?php echo htmlspecialchars($key); ?>" 
                                    aria-expanded="false" aria-controls="collapse<?php echo htmlspecialchars($key); ?>">
                                <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $key))); ?>
                            </button>
                        </h2>
                        <div id="collapse<?php echo htmlspecialchars($key); ?>" class="accordion-collapse collapse" 
                             aria-labelledby="heading<?php echo htmlspecialchars($key); ?>" data-bs-parent="#propertyAccordion">
                            <div class="accordion-body">
                                <?php echo formatPropertyValue($value); ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>