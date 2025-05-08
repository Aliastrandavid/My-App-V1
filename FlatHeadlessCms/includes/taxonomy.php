<?php
/**
 * Taxonomy Functions
 * 
 * Functions to manage taxonomies and terms in the CMS
 */

/**
 * Get all taxonomies
 * 
 * @return array
 */
function get_taxonomies() {
    $file = __DIR__ . '/../storage/taxonomies.json';
    $taxonomies = read_json_file($file);
    
    return is_array($taxonomies) ? $taxonomies : [];
}

/**
 * Get taxonomies for a specific post type
 * 
 * @param string $post_type
 * @return array
 */
function get_taxonomies_for_post_type($post_type) {
    $post_types = get_post_types();
    $all_taxonomies = get_taxonomies();
    $result = [];
    
    // Check if post type exists and has taxonomies defined
    if (isset($post_types[$post_type]) && isset($post_types[$post_type]['taxonomies'])) {
        $tax_slugs = $post_types[$post_type]['taxonomies'];
        
        // Add each taxonomy assigned to this post type
        foreach ($tax_slugs as $slug) {
            if (isset($all_taxonomies[$slug])) {
                $result[$slug] = $all_taxonomies[$slug];
            }
        }
    }
    
    return $result;
}

/**
 * Get a single taxonomy by slug
 * 
 * @param string $slug
 * @return array|null
 */
function get_taxonomy($slug) {
    $taxonomies = get_taxonomies();
    
    return isset($taxonomies[$slug]) ? $taxonomies[$slug] : null;
}

/**
 * Get all terms
 * 
 * @return array
 */
function get_terms_all() {
    $file = __DIR__ . '/../storage/terms.json';
    $terms = read_json_file($file);
    
    return is_array($terms) ? $terms : [];
}

/**
 * Get terms for a specific taxonomy
 * 
 * @param string $taxonomy
 * @return array
 */
function get_terms($taxonomy) {
    $all_terms = get_terms_all();
    
    if (isset($all_terms[$taxonomy])) {
        return $all_terms[$taxonomy];
    }
    
    return [];
}

/**
 * Get a single term by ID
 * 
 * @param string $taxonomy
 * @param int $term_id
 * @return array|null
 */
function get_term($taxonomy, $term_id) {
    $terms = get_terms($taxonomy);
    
    foreach ($terms as $term) {
        if ($term['id'] == $term_id) {
            return $term;
        }
    }
    
    return null;
}

/**
 * Get term names for a list of term IDs
 * 
 * @param string $taxonomy
 * @param array $term_ids
 * @param string $language
 * @return array
 */
function get_term_names($taxonomy, $term_ids, $language = null) {
    $terms = get_terms($taxonomy);
    $names = [];
    
    // Use current language if not specified
    if ($language === null) {
        $language = defined('CURRENT_LANG') ? CURRENT_LANG : 'en';
    }
    
    foreach ($terms as $term) {
        if (in_array($term['id'], $term_ids)) {
            // Get name in specified language or fall back to default
            $name_key = 'name_' . $language;
            if (isset($term[$name_key])) {
                $names[] = $term[$name_key];
            } elseif (isset($term['name_en'])) {
                $names[] = $term['name_en'];
            } else {
                $names[] = 'Unnamed Term';
            }
        }
    }
    
    return $names;
}

/**
 * Create a new taxonomy
 * 
 * @param string $slug
 * @param array $data
 * @return bool
 */
function create_taxonomy($slug, $data) {
    $taxonomies = get_taxonomies();
    
    // Check if taxonomy already exists
    if (isset($taxonomies[$slug])) {
        return false;
    }
    
    // Add new taxonomy
    $taxonomies[$slug] = $data;
    
    // Save to file
    $file = __DIR__ . '/../storage/taxonomies.json';
    return write_json_file($file, $taxonomies);
}

/**
 * Update a taxonomy
 * 
 * @param string $slug
 * @param array $data
 * @return bool
 */
function update_taxonomy($slug, $data) {
    $taxonomies = get_taxonomies();
    
    // Check if taxonomy exists
    if (!isset($taxonomies[$slug])) {
        return false;
    }
    
    // Update taxonomy
    $taxonomies[$slug] = $data;
    
    // Save to file
    $file = __DIR__ . '/../storage/taxonomies.json';
    return write_json_file($file, $taxonomies);
}

/**
 * Delete a taxonomy
 * 
 * @param string $slug
 * @return bool
 */
function delete_taxonomy($slug) {
    $taxonomies = get_taxonomies();
    $all_terms = get_terms_all();
    
    // Check if taxonomy exists
    if (!isset($taxonomies[$slug])) {
        return false;
    }
    
    // Remove taxonomy
    unset($taxonomies[$slug]);
    
    // Remove associated terms
    if (isset($all_terms[$slug])) {
        unset($all_terms[$slug]);
        // Save terms file
        $terms_file = __DIR__ . '/../storage/terms.json';
        write_json_file($terms_file, $all_terms);
    }
    
    // Save taxonomies file
    $tax_file = __DIR__ . '/../storage/taxonomies.json';
    return write_json_file($tax_file, $taxonomies);
}

/**
 * Create a new term
 * 
 * @param string $taxonomy
 * @param array $data
 * @return bool|int Term ID if successful, false otherwise
 */
function create_term($taxonomy, $data) {
    $all_terms = get_terms_all();
    
    // Check if taxonomy exists in terms
    if (!isset($all_terms[$taxonomy])) {
        $all_terms[$taxonomy] = [];
    }
    
    // Find the next ID
    $max_id = 0;
    foreach ($all_terms[$taxonomy] as $term) {
        if ($term['id'] > $max_id) {
            $max_id = $term['id'];
        }
    }
    
    // Set the new ID
    $data['id'] = $max_id + 1;
    
    // Add the term
    $all_terms[$taxonomy][] = $data;
    
    // Save to file
    $file = __DIR__ . '/../storage/terms.json';
    $success = write_json_file($file, $all_terms);
    
    return $success ? $data['id'] : false;
}

/**
 * Update a term
 * 
 * @param string $taxonomy
 * @param int $term_id
 * @param array $data
 * @return bool
 */
function update_term($taxonomy, $term_id, $data) {
    $all_terms = get_terms_all();
    
    // Check if taxonomy exists
    if (!isset($all_terms[$taxonomy])) {
        return false;
    }
    
    $updated = false;
    
    // Find and update the term
    foreach ($all_terms[$taxonomy] as $key => $term) {
        if ($term['id'] == $term_id) {
            // Preserve the ID
            $data['id'] = $term_id;
            $all_terms[$taxonomy][$key] = $data;
            $updated = true;
            break;
        }
    }
    
    if (!$updated) {
        return false;
    }
    
    // Save to file
    $file = __DIR__ . '/../storage/terms.json';
    return write_json_file($file, $all_terms);
}

/**
 * Save a term
 * 
 * @param string $taxonomy
 * @param array $term
 * @return bool
 */
function save_term($taxonomy, $term) {
    $all_terms = get_terms_all();
    
    // Check if taxonomy exists
    if (!isset($all_terms[$taxonomy])) {
        // Create a new array for this taxonomy
        $all_terms[$taxonomy] = [];
    }
    
    $found = false;
    
    // Check if term exists (for update)
    foreach ($all_terms[$taxonomy] as $key => $existing_term) {
        if ($existing_term['id'] == $term['id']) {
            // Update existing term
            $all_terms[$taxonomy][$key] = $term;
            $found = true;
            break;
        }
    }
    
    // If term doesn't exist, add it
    if (!$found) {
        $all_terms[$taxonomy][] = $term;
    }
    
    // Save to file
    $file = __DIR__ . '/../storage/terms.json';
    return write_json_file($file, $all_terms);
}

/**
 * Delete a term
 * 
 * @param string $taxonomy
 * @param int $term_id
 * @return bool
 */
function delete_term($taxonomy, $term_id) {
    $all_terms = get_terms_all();
    
    // Check if taxonomy exists
    if (!isset($all_terms[$taxonomy])) {
        return false;
    }
    
    $index = null;
    
    // Find the term index
    foreach ($all_terms[$taxonomy] as $key => $term) {
        if ($term['id'] == $term_id) {
            $index = $key;
            break;
        }
    }
    
    if ($index === null) {
        return false;
    }
    
    // Remove the term
    array_splice($all_terms[$taxonomy], $index, 1);
    
    // Save to file
    $file = __DIR__ . '/../storage/terms.json';
    return write_json_file($file, $all_terms);
}

/**
 * Get parent terms for a taxonomy
 * 
 * @param string $taxonomy
 * @return array
 */
function get_parent_terms($taxonomy) {
    $terms = get_terms($taxonomy);
    $parents = [];
    
    foreach ($terms as $term) {
        if ($term['parent'] == 0) {
            $parents[] = $term;
        }
    }
    
    return $parents;
}

/**
 * Get child terms for a parent term
 * 
 * @param string $taxonomy
 * @param int $parent_id
 * @return array
 */
function get_child_terms($taxonomy, $parent_id) {
    $terms = get_terms($taxonomy);
    $children = [];
    
    foreach ($terms as $term) {
        if ($term['parent'] == $parent_id) {
            $children[] = $term;
        }
    }
    
    return $children;
}

/**
 * Save a taxonomy (create or update)
 * 
 * @param string $slug
 * @param array $data
 * @return bool
 */
function save_taxonomy($slug, $data) {
    $taxonomies = get_taxonomies();
    
    if (isset($taxonomies[$slug])) {
        // Update existing taxonomy
        return update_taxonomy($slug, $data);
    } else {
        // Create new taxonomy
        return create_taxonomy($slug, $data);
    }
}