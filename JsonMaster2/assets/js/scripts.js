/**
 * Property Management System JavaScript
 */

// Wait for DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Set initial theme
    const savedTheme = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-theme', savedTheme);
    updateThemeButton(savedTheme);
    
    // Initialize tooltips
    initializeTooltips();
    
    // Setup form validation
    setupFormValidation();
    
    // Setup confirmation dialogs
    setupConfirmations();
    
    // Setup image modal
    setupImageModal();
    
    // Check for saved view preference
    const savedView = localStorage.getItem('propertyViewPreference');
    if (savedView === 'list') {
        toggleLayout('list');
    }
    
    // Infinite scroll removed as requested
});

/**
 * Initialize Bootstrap tooltips
 */
function initializeTooltips() {
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
}

/**
 * Setup form validation
 */
function setupFormValidation() {
    const propertyForm = document.getElementById('propertyForm');
    
    if (propertyForm) {
        propertyForm.addEventListener('submit', function(event) {
            if (!propertyForm.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            } else {
                showLoadingSpinner();
            }
            
            propertyForm.classList.add('was-validated');
        });
    }
}

/**
 * Setup confirmation dialogs
 */
function setupConfirmations() {
    const confirmButtons = document.querySelectorAll('[data-confirm]');
    
    confirmButtons.forEach(button => {
        button.addEventListener('click', function(event) {
            const message = this.getAttribute('data-confirm');
            if (!confirm(message)) {
                event.preventDefault();
            } else {
                showLoadingSpinner();
            }
        });
    });
}

/**
 * Show loading spinner
 */
function showLoadingSpinner() {
    const spinner = document.createElement('div');
    spinner.className = 'spinner-overlay show';
    spinner.innerHTML = `
        <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
            <span class="visually-hidden">Loading...</span>
        </div>
    `;
    document.body.appendChild(spinner);
}

/**
 * Hide loading spinner
 */
function hideLoadingSpinner() {
    const spinner = document.querySelector('.spinner-overlay');
    if (spinner) {
        spinner.remove();
    }
}

/**
 * Filter properties in the listing page
 */
function filterProperties() {
    const searchInput = document.getElementById('searchInput');
    const filterValue = searchInput.value.toLowerCase();
    const propertyCards = document.querySelectorAll('.property-card');
    
    propertyCards.forEach(card => {
        const cardText = card.textContent.toLowerCase();
        if (cardText.includes(filterValue)) {
            card.style.display = '';
        } else {
            card.style.display = 'none';
        }
    });
}

/**
 * Toggle between grid and list view
 */
function toggleLayout(viewType) {
    const gridView = document.getElementById('gridView');
    const listView = document.getElementById('listView');
    const gridButton = document.getElementById('gridLayoutBtn');
    const listButton = document.getElementById('listLayoutBtn');
    
    if (viewType === 'grid') {
        gridView.classList.remove('d-none');
        listView.classList.add('d-none');
        gridButton.classList.add('active');
        listButton.classList.remove('active');
        // Save preference in localStorage
        localStorage.setItem('propertyViewPreference', 'grid');
    } else {
        gridView.classList.add('d-none');
        listView.classList.remove('d-none');
        gridButton.classList.remove('active');
        listButton.classList.add('active');
        // Save preference in localStorage
        localStorage.setItem('propertyViewPreference', 'list');
    }
}

/**
 * Setup image modal for property gallery
 */
function setupImageModal() {
    const imageModal = document.getElementById('imageModal');
    if (imageModal) {
        imageModal.addEventListener('show.bs.modal', function (event) {
            // Button that triggered the modal
            const button = event.relatedTarget;
            // Extract info from data-bs-* attributes
            const imageUrl = button.getAttribute('data-bs-image');
            // Update the modal's image
            const modalImage = document.getElementById('modalImage');
            if (modalImage) {
                modalImage.src = imageUrl;
            }
        });
    }
}

/**
 * Toggle advanced form fields
 */
function toggleAdvancedFields() {
    const advancedSection = document.getElementById('advancedSection');
    const toggleButton = document.getElementById('toggleAdvanced');
    
    if (advancedSection) {
        advancedSection.classList.toggle('d-none');
        
        if (toggleButton) {
            if (advancedSection.classList.contains('d-none')) {
                toggleButton.innerHTML = 'Show Advanced Fields <i class="fas fa-chevron-down"></i>';
            } else {
                toggleButton.innerHTML = 'Hide Advanced Fields <i class="fas fa-chevron-up"></i>';
            }
        }
    }
}

/**
 * Update price range hidden inputs based on selected range
 * @param {HTMLSelectElement} selectElement - The price range select element
 */
function updatePriceRange(selectElement) {
    // If no range selected, clear the values
    if (!selectElement.value) {
        document.getElementById('price_min').value = '';
        document.getElementById('price_max').value = '';
        return;
    }
    
    try {
        // Get the selected option
        const selectedOption = selectElement.options[selectElement.selectedIndex];
        
        // Get min and max values from data attributes
        const minValue = selectedOption.getAttribute('data-min');
        const maxValue = selectedOption.getAttribute('data-max');
        
        if (minValue && maxValue) {
            document.getElementById('price_min').value = minValue;
            document.getElementById('price_max').value = maxValue;
        }
    } catch (error) {
        console.error('Error updating price range:', error);
    }
}

/**
 * Confirm property deletion
 * @param {number} propertyId - The ID of the property to delete
 * @param {string} reference - The reference of the property to delete
 */
function confirmDelete(propertyId, reference) {
    if (confirm(`Are you sure you want to delete property #${reference}? This action cannot be undone.`)) {
        window.location.href = `delete-property.php?id=${propertyId}`;
    }
}

/**
 * Clear the search input and refresh the page
 */
function clearSearch() {
    // Get the search input and clear it
    document.getElementById('searchInput').value = '';
    
    // Submit the form to refresh the page
    document.getElementById('filterForm').submit();
}

// Removed infinite scroll functionality as requested

/**
 * Toggle between light and dark theme
 */
function toggleTheme() {
    const currentTheme = document.documentElement.getAttribute('data-theme');
    const newTheme = currentTheme === 'light' ? 'dark' : 'light';
    
    document.documentElement.setAttribute('data-theme', newTheme);
    localStorage.setItem('theme', newTheme);
    updateThemeButton(newTheme);
}

/**
 * Update theme toggle button icon
 */
function updateThemeButton(theme) {
    const button = document.getElementById('themeToggle');
    if (button) {
        button.innerHTML = `<i class="fas fa-${theme === 'light' ? 'moon' : 'sun'}"></i>`;
    }
}
