<?php
/**
 * Helper functions for property management
 */

/**
 * Load property data from JSON file
 * 
 * @return array Property data
 */
function loadPropertyData() {
    $jsonFile = 'attached_assets/properties.json';
    
    if (!file_exists($jsonFile)) {
        throw new Exception("Property data file not found");
    }
    
    $jsonContent = file_get_contents($jsonFile);
    
    if (!$jsonContent) {
        throw new Exception("Unable to read property data file");
    }
    
    $data = json_decode($jsonContent, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Invalid JSON format: " . json_last_error_msg());
    }
    
    return $data;
}

/**
 * Save property data to JSON file
 * 
 * @param array $data Property data to save
 * @return bool True if save was successful
 */
function savePropertyData($data) {
    $jsonFile = 'attached_assets/properties.json';
    
    if (!is_writable(dirname($jsonFile))) {
        throw new Exception("Directory is not writable");
    }
    
    $jsonContent = json_encode($data, JSON_PRETTY_PRINT);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Failed to encode JSON: " . json_last_error_msg());
    }
    
    $result = file_put_contents($jsonFile, $jsonContent);
    
    if ($result === false) {
        throw new Exception("Failed to write to file");
    }
    
    return true;
}

/**
 * Get a specific property by ID
 * 
 * @param int $id Property ID
 * @return array|null Property data or null if not found
 */
function getPropertyById($id) {
    try {
        $data = loadPropertyData();
        
        foreach ($data['properties'] as $property) {
            if ($property['id'] == $id) {
                return $property;
            }
        }
        
        return null;
    } catch (Exception $e) {
        throw new Exception("Error retrieving property: " . $e->getMessage());
    }
}

/**
 * Update a property by ID
 * 
 * @param int $id Property ID
 * @param array $propertyData Updated property data
 * @return bool True if update was successful
 */
function updateProperty($id, $propertyData) {
    try {
        $data = loadPropertyData();
        
        foreach ($data['properties'] as $key => $property) {
            if ($property['id'] == $id) {
                // Preserve original ID and reference
                $propertyData['id'] = $property['id'];
                $propertyData['reference'] = $property['reference'];
                
                // Update the property
                $data['properties'][$key] = $propertyData;
                
                // Update timestamp
                $data['timestamp'] = time();
                
                return savePropertyData($data);
            }
        }
        
        throw new Exception("Property not found");
    } catch (Exception $e) {
        throw new Exception("Error updating property: " . $e->getMessage());
    }
}

/**
 * Format a property value for display
 * 
 * @param mixed $value The value to format
 * @return string Formatted value
 */
function formatPropertyValue($value) {
    if (is_array($value)) {
        return '<pre>' . htmlspecialchars(json_encode($value, JSON_PRETTY_PRINT)) . '</pre>';
    } elseif (is_bool($value)) {
        return $value ? 'Yes' : 'No';
    } elseif ($value === null) {
        return '<em>null</em>';
    } else {
        return htmlspecialchars((string)$value);
    }
}

/**
 * Get Property Type Label
 * 
 * @param int $typeId The property type ID
 * @return string The property type label
 */
function getPropertyTypeLabel($typeId) {
    $types = [
        1 => 'Apartment',
        2 => 'House',
        3 => 'Land',
        4 => 'Commerce',
        5 => 'Parking',
        6 => 'Building',
    ];
    
    return isset($types[$typeId]) ? $types[$typeId] : "Type $typeId";
}

/**
 * Get Property Subtype Label
 * 
 * @param int $subtypeId The property subtype ID
 * @return string The property subtype label
 */
function getPropertySubtypeLabel($subtypeId) {
    $subtypes = [
        1 => 'Apartment',
        2 => 'House',
        3 => 'Building',
        4 => 'Parking',
        5 => 'Penthouse',
        6 => 'Ground floor',
        7 => 'Studio',
        8 => 'Duplex',
        9 => 'HMO',
        10 => 'Loft',
        11 => 'Boat',
        12 => 'Castle',
        13 => 'Demountable',
        14 => 'Mansion',
        15 => 'Property',
        16 => 'Floor',
        17 => 'Triplex',
        18 => 'Private mansion',
        19 => 'Bastide',
        20 => 'Mas',
        21 => 'Mill',
        22 => 'Tower',
        23 => 'Farmhouse',
        24 => 'Chalet',
        25 => 'Villa',
        26 => 'Cave dwelling',
        27 => 'Barge',
        28 => 'Houseboat',
        29 => 'Mobile home',
        30 => 'Caravan',
        31 => 'Country house',
        32 => 'Hotel',
        33 => 'Offices',
        34 => 'Shop',
        35 => 'Shopping center',
        36 => 'Business goodwill and commercial lease',
        37 => 'Commercial premises',
        38 => 'Building plot',
        39 => 'Industrial premises',
        40 => 'Warehouse',
        41 => 'Restaurant',
        42 => 'Bar',
        43 => 'Industrial land',
        44 => 'Factory',
        45 => 'Castle outbuilding',
        46 => 'Field',
        47 => 'Plot',
        48 => 'Wood',
        49 => 'Meadow',
        50 => 'Wasteland',
        51 => 'Private clinic',
        52 => 'Sheepfold',
        53 => 'Island',
        54 => 'Vineyard',
        55 => 'Box',
        56 => 'Private garage'
    ];
    
    return isset($subtypes[$subtypeId]) ? $subtypes[$subtypeId] : "Subtype $subtypeId";
}

/**
 * Get Property Orientation Label
 * 
 * @param int $orientationId The property orientation ID
 * @return string The property orientation label
 */
function getPropertyOrientationLabel($orientationId) {
    $orientations = [
        1 => 'North',
        2 => 'North-East',
        3 => 'East',
        4 => 'South-East',
        5 => 'South',
        6 => 'South-West',
        7 => 'West',
        8 => 'North-West'
    ];
    
    return isset($orientations[$orientationId]) ? $orientations[$orientationId] : "Orientation $orientationId";
}

/**
 * Get Property Category Label
 * 
 * @param int $categoryId The property category ID
 * @return string The property category label
 */
function getPropertyCategoryLabel($categoryId) {
    $categories = [
        1 => 'Sale',
        2 => 'Rental',
        3 => 'Seasonal rental',
        4 => 'Investment',
        5 => 'Viager',
        6 => 'Life lease',
        7 => 'Prestige',
        8 => 'New development',
        9 => 'New property',
        10 => 'Auction',
        11 => 'Rental management',
        12 => 'Property management',
        13 => 'Bare ownership',
        14 => 'Division of property',
        15 => 'Vente aux enchères amiable',
        16 => 'Exchanging apartments',
        17 => 'Off-plan property',
        18 => 'Vacation rental'
    ];
    
    return isset($categories[$categoryId]) ? $categories[$categoryId] : "Category $categoryId";
}

/**
 * Get Property Status Label
 * 
 * @param int $statusId The property status ID
 * @return string The property status label
 */
function getPropertyStatusLabel($statusId) {
    $statuses = [
        1 => 'Active',
        2 => 'Under offer',
        3 => 'Sold',
        4 => 'Rented',
        5 => 'Suspended'
    ];
    
    return isset($statuses[$statusId]) ? $statuses[$statusId] : "Status $statusId";
}

/**
 * Get all unique property types from the data
 * 
 * @return array Unique property types with ID and label
 */
function getAllPropertyTypes() {
    try {
        $data = loadPropertyData();
        $properties = $data['properties'] ?? [];
        
        $types = [];
        foreach ($properties as $property) {
            if (isset($property['type']) && !isset($types[$property['type']])) {
                $types[$property['type']] = [
                    'id' => $property['type'],
                    'label' => getPropertyTypeLabel($property['type'])
                ];
            }
        }
        
        // Sort types by label
        usort($types, function($a, $b) {
            return strcmp($a['label'], $b['label']);
        });
        
        return $types;
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Get all unique property subtypes from the data
 * 
 * @return array Unique property subtypes with ID and label
 */
function getAllPropertySubtypes() {
    try {
        $data = loadPropertyData();
        $properties = $data['properties'] ?? [];
        
        $subtypes = [];
        foreach ($properties as $property) {
            if (isset($property['subtype']) && !isset($subtypes[$property['subtype']])) {
                $subtypes[$property['subtype']] = [
                    'id' => $property['subtype'],
                    'label' => getPropertySubtypeLabel($property['subtype'])
                ];
            }
        }
        
        // Sort subtypes by label
        usort($subtypes, function($a, $b) {
            return strcmp($a['label'], $b['label']);
        });
        
        return $subtypes;
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Get all unique property categories from the data
 * 
 * @return array Unique property categories with ID and label
 */
function getAllPropertyCategories() {
    try {
        $data = loadPropertyData();
        $properties = $data['properties'] ?? [];
        
        $categories = [];
        foreach ($properties as $property) {
            if (isset($property['category']) && !isset($categories[$property['category']])) {
                $categories[$property['category']] = [
                    'id' => $property['category'],
                    'label' => getPropertyCategoryLabel($property['category'])
                ];
            }
        }
        
        // Sort categories by label
        usort($categories, function($a, $b) {
            return strcmp($a['label'], $b['label']);
        });
        
        return $categories;
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Get all unique cities from the data
 * 
 * @return array Unique cities with ID, name and zipcode
 */
function getAllCities() {
    try {
        $data = loadPropertyData();
        $properties = $data['properties'] ?? [];
        
        $cities = [];
        foreach ($properties as $property) {
            if (isset($property['city']) && isset($property['city']['id']) && !isset($cities[$property['city']['id']])) {
                $cities[$property['city']['id']] = [
                    'id' => $property['city']['id'],
                    'name' => $property['city']['name'],
                    'zipcode' => $property['city']['zipcode'] ?? ''
                ];
            }
        }
        
        // Sort cities by name
        usort($cities, function($a, $b) {
            return strcmp($a['name'], $b['name']);
        });
        
        return $cities;
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Get price ranges in 500k€ steps
 * 
 * @return array Price ranges
 */
function getPriceRanges() {
    try {
        $data = loadPropertyData();
        $properties = $data['properties'] ?? [];
        
        // Find min and max prices
        $minPrice = PHP_INT_MAX;
        $maxPrice = 0;
        
        foreach ($properties as $property) {
            if (isset($property['price']) && isset($property['price']['value'])) {
                $price = $property['price']['value'];
                if ($price < $minPrice) {
                    $minPrice = $price;
                }
                if ($price > $maxPrice) {
                    $maxPrice = $price;
                }
            }
        }
        
        // Create ranges in 500k steps
        $ranges = [];
        $step = 500000;
        $start = 0;
        
        // Round max price up to the next 500k
        $maxPrice = ceil($maxPrice / $step) * $step;
        
        while ($start < $maxPrice) {
            $end = $start + $step;
            $ranges[] = [
                'min' => $start,
                'max' => $end,
                'label' => '€' . number_format($start/1000, 0) . 'k - €' . number_format($end/1000, 0) . 'k'
            ];
            $start = $end;
        }
        
        return $ranges;
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Delete a property by ID
 * 
 * @param int $id Property ID
 * @return bool True if deletion was successful
 */
function deleteProperty($id) {
    try {
        $data = loadPropertyData();
        $found = false;
        
        foreach ($data['properties'] as $key => $property) {
            if ($property['id'] == $id) {
                unset($data['properties'][$key]);
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            throw new Exception("Property not found");
        }
        
        // Re-index the array
        $data['properties'] = array_values($data['properties']);
        
        // Update total items count
        $data['total_items'] = count($data['properties']);
        
        // Update timestamp
        $data['timestamp'] = time();
        
        return savePropertyData($data);
    } catch (Exception $e) {
        throw new Exception("Error deleting property: " . $e->getMessage());
    }
}