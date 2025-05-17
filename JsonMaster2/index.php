<?php
require_once 'includes/functions.php';

$error = null;
$properties = [];
$filteredCount = 0;

// Get filter parameters
$filterType = isset($_GET['type']) ? (int)$_GET['type'] : null;
$filterSubtype = isset($_GET['subtype']) ? (int)$_GET['subtype'] : null;
$filterCategory = isset($_GET['category']) ? (int)$_GET['category'] : null;
$filterCity = isset($_GET['city']) ? (int)$_GET['city'] : null;
$filterPriceMin = isset($_GET['price_min']) ? (int)$_GET['price_min'] : null;
$filterPriceMax = isset($_GET['price_max']) ? (int)$_GET['price_max'] : null;
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : null;

// Pagination/lazy loading parameters
// Remove pagination - show all properties

try {
    $data = loadPropertyData();
    $allProperties = $data['properties'] ?? [];
    $totalItems = $data['total_items'] ?? count($allProperties);
    
    // Get all unique values for filters
    $propertyTypes = getAllPropertyTypes();
    $propertySubtypes = getAllPropertySubtypes();
    $propertyCategories = getAllPropertyCategories();
    $cities = getAllCities();
    $priceRanges = getPriceRanges();
    
    // Apply filters
    $filteredProperties = $allProperties;
    
    // Filter by search query (across multiple fields)
    if ($searchQuery) {
        $searchQuery = strtolower($searchQuery);
        $filteredProperties = array_filter($filteredProperties, function($property) use ($searchQuery) {
            // Search in type label
            if (isset($property['type']) && 
                stripos(strtolower(getPropertyTypeLabel($property['type'])), $searchQuery) !== false) {
                return true;
            }
            
            // Search in subtype label
            if (isset($property['subtype']) && 
                stripos(strtolower(getPropertySubtypeLabel($property['subtype'])), $searchQuery) !== false) {
                return true;
            }
            
            // Search in city name
            if (isset($property['city']) && isset($property['city']['name']) && 
                stripos(strtolower($property['city']['name']), $searchQuery) !== false) {
                return true;
            }
            
            // Search in region name
            if (isset($property['region']) && isset($property['region']['name']) && 
                stripos(strtolower($property['region']['name']), $searchQuery) !== false) {
                return true;
            }
            
            // Search in price
            if (isset($property['price']) && isset($property['price']['value'])) {
                $priceStr = (string)$property['price']['value'];
                if (stripos($priceStr, $searchQuery) !== false) {
                    return true;
                }
            }
            
            // Search in comments
            if (isset($property['comments']) && !empty($property['comments'])) {
                foreach ($property['comments'] as $comment) {
                    if ((isset($comment['title']) && 
                         stripos(strtolower($comment['title']), $searchQuery) !== false) ||
                        (isset($comment['text']) && 
                         stripos(strtolower($comment['text']), $searchQuery) !== false)) {
                        return true;
                    }
                }
            }
            
            return false;
        });
    }
    
    // Filter by type
    if ($filterType) {
        $filteredProperties = array_filter($filteredProperties, function($property) use ($filterType) {
            return isset($property['type']) && $property['type'] == $filterType;
        });
    }
    
    // Filter by subtype
    if ($filterSubtype) {
        $filteredProperties = array_filter($filteredProperties, function($property) use ($filterSubtype) {
            return isset($property['subtype']) && $property['subtype'] == $filterSubtype;
        });
    }
    
    // Filter by category
    if ($filterCategory) {
        $filteredProperties = array_filter($filteredProperties, function($property) use ($filterCategory) {
            return isset($property['category']) && $property['category'] == $filterCategory;
        });
    }
    
    // Filter by city
    if ($filterCity) {
        $filteredProperties = array_filter($filteredProperties, function($property) use ($filterCity) {
            return isset($property['city']) && isset($property['city']['id']) && $property['city']['id'] == $filterCity;
        });
    }
    
    // Filter by price range
    if ($filterPriceMin !== null && $filterPriceMax !== null && $filterPriceMin > 0 && $filterPriceMax > 0) {
        $filteredProperties = array_filter($filteredProperties, function($property) use ($filterPriceMin, $filterPriceMax) {
            return isset($property['price']) && 
                   isset($property['price']['value']) && 
                   $property['price']['value'] >= $filterPriceMin && 
                   $property['price']['value'] <= $filterPriceMax;
        });
    }
    
    // Count filtered properties before pagination
    $filteredCount = count($filteredProperties);
    
    // Sort properties (optional)
    // For now, we'll keep the original order
    
    // Show all filtered properties, no pagination
    $properties = array_values($filteredProperties);
    
    // Simplified - remove AJAX infinite scroll handling
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>

<?php include 'includes/header.php'; ?>

<div class="row mb-4">
    <div class="col-md-6">
        <h1><i class="fas fa-building me-2"></i>Property Listing</h1>
    </div>
    <div class="col-md-6">
        <div class="input-group mb-2">
            <input type="text" id="searchInput" class="form-control" placeholder="Search properties..." 
                   onkeyup="filterProperties()" aria-label="Search properties">
            <span class="input-group-text">
                <i class="fas fa-search"></i>
            </span>
        </div>
        <div class="btn-group float-end" role="group" aria-label="Layout Toggle">
            <button type="button" class="btn btn-outline-primary active" id="gridLayoutBtn" onclick="toggleLayout('grid')">
                <i class="fas fa-th-large"></i> Grid View
            </button>
            <button type="button" class="btn btn-outline-primary" id="listLayoutBtn" onclick="toggleLayout('list')">
                <i class="fas fa-list"></i> List View
            </button>
        </div>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
    </div>
<?php elseif (empty($properties)): ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i>
        No properties found in the data file.
    </div>
<?php else: ?>
    <div class="card mb-4">
        <div class="card-header bg-light">
            <h4 class="mb-0"><i class="fas fa-filter me-2"></i>Filter Properties</h4>
        </div>
        <div class="card-body">
            <form action="index.php" method="get" id="filterForm">
                <!-- Search input -->
                <div class="mb-3">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" class="form-control" id="searchInput" name="search" 
                               placeholder="Search in type, subtype, city, region, price, comments..." 
                               value="<?php echo htmlspecialchars($searchQuery ?? ''); ?>">
                        <?php if ($searchQuery): ?>
                        <button type="button" class="btn btn-outline-secondary" onclick="clearSearch()">
                            <i class="fas fa-times"></i>
                        </button>
                        <?php endif; ?>
                    </div>
                    <div class="form-text">Search across multiple property fields</div>
                </div>
                <div class="row">
                    <!-- Property Type Filter -->
                    <div class="col-md-6 col-lg-3 mb-3">
                        <label for="typeFilter" class="form-label">Property Type</label>
                        <select class="form-select" id="typeFilter" name="type">
                            <option value="">All Types</option>
                            <?php foreach ($propertyTypes as $type): ?>
                                <option value="<?php echo $type['id']; ?>" <?php echo $filterType == $type['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($type['label']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Property Subtype Filter -->
                    <div class="col-md-6 col-lg-3 mb-3">
                        <label for="subtypeFilter" class="form-label">Property Subtype</label>
                        <select class="form-select" id="subtypeFilter" name="subtype">
                            <option value="">All Subtypes</option>
                            <?php foreach ($propertySubtypes as $subtype): ?>
                                <option value="<?php echo $subtype['id']; ?>" <?php echo $filterSubtype == $subtype['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($subtype['label']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Property Category Filter -->
                    <div class="col-md-6 col-lg-3 mb-3">
                        <label for="categoryFilter" class="form-label">Property Category</label>
                        <select class="form-select" id="categoryFilter" name="category">
                            <option value="">All Categories</option>
                            <?php foreach ($propertyCategories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" <?php echo $filterCategory == $category['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['label']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- City Filter -->
                    <div class="col-md-6 col-lg-3 mb-3">
                        <label for="cityFilter" class="form-label">City</label>
                        <select class="form-select" id="cityFilter" name="city">
                            <option value="">All Cities</option>
                            <?php foreach ($cities as $city): ?>
                                <option value="<?php echo $city['id']; ?>" <?php echo $filterCity == $city['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($city['name'] . ((!empty($city['zipcode'])) ? ' (' . $city['zipcode'] . ')' : '')); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Price Range Filter -->
                    <div class="col-md-6 mb-3">
                        <label for="priceRangeFilter" class="form-label">Price Range</label>
                        <select class="form-select" id="priceRangeFilter" onchange="updatePriceRange(this)">
                            <option value="">All Price Ranges</option>
                            <?php foreach ($priceRanges as $index => $range): ?>
                                <option value="<?php echo $index; ?>" 
                                        data-min="<?php echo $range['min']; ?>"
                                        data-max="<?php echo $range['max']; ?>"
                                        <?php echo ($filterPriceMin == $range['min'] && $filterPriceMax == $range['max']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($range['label']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="hidden" name="price_min" id="price_min" value="<?php echo $filterPriceMin; ?>">
                        <input type="hidden" name="price_max" id="price_max" value="<?php echo $filterPriceMax; ?>">
                    </div>
                    
                    <!-- Filter Buttons -->
                    <div class="col-md-6 mb-3 d-flex align-items-end">
                        <div class="d-grid gap-2 w-100">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter me-2"></i>Apply Filters
                            </button>
                            <a href="index.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-2"></i>Clear All Filters
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <div class="alert alert-info mb-4">
        <i class="fas fa-info-circle me-2"></i>
        Displaying <?php echo $filteredCount; ?> 
        <?php echo ($filterType || $filterSubtype || $filterCategory || $filterCity || ($filterPriceMin !== null && $filterPriceMax !== null)) ? 'filtered' : ''; ?> properties
        <?php if (($filterType || $filterSubtype || $filterCategory || $filterCity || ($filterPriceMin !== null && $filterPriceMax !== null)) && $filteredCount < $totalItems): ?>
            (total: <?php echo $totalItems; ?> properties)
        <?php endif; ?>
    </div>

    <!-- Grid View (default) -->
    <div id="gridView" class="row">
        <?php foreach ($properties as $property): ?>
            <div class="col-md-6 col-lg-4 property-card">
                <div class="card mb-4 h-100">
                    <?php 
                    // Find the first property image or image with rank 1
                    $propertyImage = null;
                    if (isset($property['pictures']) && !empty($property['pictures'])) {
                        foreach ($property['pictures'] as $picture) {
                            if ($picture['rank'] == 1) {
                                $propertyImage = $picture['url'];
                                break;
                            }
                        }
                        // If no rank 1 image found, use the first image
                        if (!$propertyImage && !empty($property['pictures'][0]['url'])) {
                            $propertyImage = $property['pictures'][0]['url'];
                        }
                    }
                    ?>
                    <?php if ($propertyImage): ?>
                    <div class="property-image-container">
                        <img src="<?php echo htmlspecialchars($propertyImage); ?>" class="card-img-top property-thumbnail" alt="Property Image">
                    </div>
                    <?php endif; ?>
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-home me-2"></i>
                            Property #<?php echo htmlspecialchars($property['reference']); ?>
                        </h5>
                        <?php if (isset($property['status'])): ?>
                        <span class="badge bg-<?php echo $property['status'] == 1 ? 'success' : ($property['status'] == 3 ? 'danger' : 'warning'); ?>">
                            <?php echo htmlspecialchars(getPropertyStatusLabel($property['status'])); ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <!-- Category -->
                        <?php if (isset($property['category'])): ?>
                        <div class="mb-2">
                            <span class="badge bg-warning text-dark">
                                <?php echo htmlspecialchars(getPropertyCategoryLabel($property['category'])); ?>
                            </span>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Location -->
                        <?php if (isset($property['city']) && isset($property['city']['name'])): ?>
                        <p class="mb-2">
                            <i class="fas fa-map-marker-alt me-2 text-danger"></i>
                            <strong>
                                <?php echo htmlspecialchars($property['city']['name']); ?>
                                <?php if (isset($property['city']['zipcode'])): ?>
                                    (<?php echo htmlspecialchars($property['city']['zipcode']); ?>)
                                <?php endif; ?>
                            </strong>
                            <?php if (isset($property['region']) && isset($property['region']['name'])): ?>
                                <br><small class="text-muted ms-4"><?php echo htmlspecialchars($property['region']['name']); ?></small>
                            <?php endif; ?>
                        </p>
                        <?php endif; ?>
                        
                        <!-- Price Information -->
                        <?php if (isset($property['price']) && isset($property['price']['value'])): ?>
                        <div class="alert alert-primary mb-3 py-2">
                            <h5 class="mb-0">
                                <i class="fas fa-euro-sign me-2"></i>
                                <?php echo number_format($property['price']['value'], 0, ',', ' '); ?> €
                                <?php if (isset($property['price']['period'])): ?>
                                    <small class="text-muted">/<?php echo htmlspecialchars($property['price']['period']); ?></small>
                                <?php endif; ?>
                            </h5>
                            <?php if (isset($property['price']['fees']) && $property['price']['fees'] > 0): ?>
                            <small class="d-block mt-1">
                                <i class="fas fa-receipt me-1"></i>Fees: 
                                <?php echo number_format($property['price']['fees'], 0, ',', ' '); ?> €
                            </small>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                        <ul class="list-group list-group-flush mb-3">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-fingerprint me-2"></i>ID:</span>
                                <span class="badge bg-secondary"><?php echo htmlspecialchars($property['id']); ?></span>
                            </li>
                            
                            <?php if (isset($property['type'])): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-home me-2"></i>Type:</span>
                                <span class="badge bg-success"><?php echo htmlspecialchars(getPropertyTypeLabel($property['type'])); ?></span>
                            </li>
                            <?php endif; ?>
                            
                            <?php if (isset($property['subtype'])): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-th-large me-2"></i>Subtype:</span>
                                <span class="badge bg-success"><?php echo htmlspecialchars(getPropertySubtypeLabel($property['subtype'])); ?></span>
                            </li>
                            <?php endif; ?>
                            
                            <?php if (isset($property['agency'])): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-building me-2"></i>Agency:</span>
                                <span class="badge bg-primary"><?php echo htmlspecialchars($property['agency']); ?></span>
                            </li>
                            <?php endif; ?>
                            
                            <?php if (isset($property['quality'])): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-star me-2"></i>Quality:</span>
                                <span class="badge bg-info"><?php echo htmlspecialchars($property['quality']); ?></span>
                            </li>
                            <?php endif; ?>
                            
                            <?php if (isset($property['user']) && isset($property['user']['firstname']) && isset($property['user']['lastname'])): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-user me-2"></i>Contact:</span>
                                <span><?php echo htmlspecialchars($property['user']['firstname'] . ' ' . $property['user']['lastname']); ?></span>
                            </li>
                            <?php endif; ?>
                        </ul>
                        
                        <div class="d-grid gap-2">
                            <a href="property-details.php?id=<?php echo $property['id']; ?>" class="btn btn-info">
                                <i class="fas fa-eye me-2"></i>View Details
                            </a>
                            <div class="btn-group">
                                <a href="edit-property.php?id=<?php echo $property['id']; ?>" class="btn btn-warning flex-grow-1">
                                    <i class="fas fa-edit me-2"></i>Edit
                                </a>
                                <button type="button" class="btn btn-danger" 
                                        onclick="confirmDelete(<?php echo $property['id']; ?>, '<?php echo htmlspecialchars($property['reference']); ?>')">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <!-- List View (initially hidden) -->
    <div id="listView" class="row d-none">
        <div class="col-12">
            <div class="list-group">
                <?php foreach ($properties as $property): ?>
                    <?php 
                    // Find the first property image or image with rank 1
                    $propertyImage = null;
                    if (isset($property['pictures']) && !empty($property['pictures'])) {
                        foreach ($property['pictures'] as $picture) {
                            if ($picture['rank'] == 1) {
                                $propertyImage = $picture['url'];
                                break;
                            }
                        }
                        // If no rank 1 image found, use the first image
                        if (!$propertyImage && !empty($property['pictures'][0]['url'])) {
                            $propertyImage = $property['pictures'][0]['url'];
                        }
                    }
                    ?>
                    <div class="list-group-item list-group-item-action property-card mb-3">
                        <div class="row">
                            <div class="col-md-2">
                                <?php if ($propertyImage): ?>
                                <img src="<?php echo htmlspecialchars($propertyImage); ?>" class="img-fluid rounded property-list-thumbnail" alt="Property Image">
                                <?php else: ?>
                                <div class="no-image-placeholder d-flex align-items-center justify-content-center">
                                    <i class="fas fa-home fa-3x text-muted"></i>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-7">
                                <div class="d-flex w-100 justify-content-between">
                                    <h5 class="mb-1">
                                        Property #<?php echo htmlspecialchars($property['reference']); ?>
                                        <?php if (isset($property['status'])): ?>
                                        <span class="badge bg-<?php echo $property['status'] == 1 ? 'success' : ($property['status'] == 3 ? 'danger' : 'warning'); ?> ms-2">
                                            <?php echo htmlspecialchars(getPropertyStatusLabel($property['status'])); ?>
                                        </span>
                                        <?php endif; ?>
                                    </h5>
                                    <div>
                                        <?php if (isset($property['category'])): ?>
                                        <span class="badge bg-warning text-dark me-1">
                                            <?php echo htmlspecialchars(getPropertyCategoryLabel($property['category'])); ?>
                                        </span>
                                        <?php endif; ?>
                                        <?php if (isset($property['type'])): ?>
                                        <span class="badge bg-success me-1"><?php echo htmlspecialchars(getPropertyTypeLabel($property['type'])); ?></span>
                                        <?php endif; ?>
                                        <?php if (isset($property['subtype'])): ?>
                                        <span class="badge bg-success me-1"><?php echo htmlspecialchars(getPropertySubtypeLabel($property['subtype'])); ?></span>
                                        <?php endif; ?>
                                        <span class="badge bg-info me-1">Quality: <?php echo htmlspecialchars($property['quality']); ?></span>
                                        <span class="badge bg-primary">Agency: <?php echo htmlspecialchars($property['agency']); ?></span>
                                    </div>
                                </div>
                                
                                <!-- Location -->
                                <?php if (isset($property['city']) && isset($property['city']['name'])): ?>
                                <p class="mb-1 mt-2">
                                    <i class="fas fa-map-marker-alt me-2 text-danger"></i>
                                    <strong>
                                        <?php echo htmlspecialchars($property['city']['name']); ?>
                                        <?php if (isset($property['city']['zipcode'])): ?>
                                            (<?php echo htmlspecialchars($property['city']['zipcode']); ?>)
                                        <?php endif; ?>
                                    </strong>
                                    <?php if (isset($property['region']) && isset($property['region']['name'])): ?>
                                        <small class="text-muted ms-2"><?php echo htmlspecialchars($property['region']['name']); ?></small>
                                    <?php endif; ?>
                                </p>
                                <?php endif; ?>
                                
                                <!-- Price Information -->
                                <?php if (isset($property['price']) && isset($property['price']['value'])): ?>
                                <p class="mb-1">
                                    <strong class="text-primary">
                                        <i class="fas fa-euro-sign me-2"></i>
                                        <?php echo number_format($property['price']['value'], 0, ',', ' '); ?> €
                                        <?php if (isset($property['price']['period'])): ?>
                                            <small class="text-muted">/<?php echo htmlspecialchars($property['price']['period']); ?></small>
                                        <?php endif; ?>
                                    </strong>
                                    <?php if (isset($property['price']['fees']) && $property['price']['fees'] > 0): ?>
                                    <small class="text-muted ms-2">
                                        <i class="fas fa-receipt me-1"></i>Fees: 
                                        <?php echo number_format($property['price']['fees'], 0, ',', ' '); ?> €
                                    </small>
                                    <?php endif; ?>
                                </p>
                                <?php endif; ?>
                                
                                <?php if (isset($property['comments']) && !empty($property['comments'])): ?>
                                    <?php foreach ($property['comments'] as $comment): ?>
                                        <?php if (isset($comment['title']) && !empty($comment['title'])): ?>
                                            <p class="mb-1 text-truncate"><i class="fas fa-comment me-2 text-secondary"></i><?php echo htmlspecialchars($comment['title']); ?></p>
                                            <?php break; ?>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                
                                <?php if (isset($property['user']) && isset($property['user']['firstname']) && isset($property['user']['lastname'])): ?>
                                <small><i class="fas fa-user me-1"></i>Contact: <?php echo htmlspecialchars($property['user']['firstname'] . ' ' . $property['user']['lastname']); ?></small>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-3 d-flex align-items-center">
                                <div class="d-grid gap-2 w-100">
                                    <a href="property-details.php?id=<?php echo $property['id']; ?>" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye me-2"></i>View Details
                                    </a>
                                    <div class="btn-group">
                                        <a href="edit-property.php?id=<?php echo $property['id']; ?>" class="btn btn-sm btn-warning flex-grow-1">
                                            <i class="fas fa-edit me-2"></i>Edit
                                        </a>
                                        <button type="button" class="btn btn-sm btn-danger" 
                                                onclick="confirmDelete(<?php echo $property['id']; ?>, '<?php echo htmlspecialchars($property['reference']); ?>')">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Hidden data for JavaScript -->
<script type="application/json" id="priceRangesData">
<?php echo json_encode($priceRanges); ?>
</script>

<?php include 'includes/footer.php'; ?>