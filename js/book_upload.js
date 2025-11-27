/**
 * Ba Dere Exchange - Book Upload/Listing JavaScript
 * Handles book listing form with image preview and validation
 */

(function() {
    'use strict';

    /**
     * Initialize book upload functionality
     */
    function init() {
        setupImageUpload();
        setupFormValidation();
        setupAvailabilityToggles();
        setupCategoryDependentFields();
        setupCharacterCounters();
    }

    /**
     * Setup image upload with drag & drop and preview
     */
    function setupImageUpload() {
        const dropZone = document.querySelector('.image-drop-zone, [data-drop-zone]');
        const fileInput = document.querySelector('input[type="file"][name="images[]"], #bookImages');
        const previewContainer = document.querySelector('.image-previews, [data-previews]');

        if (!fileInput) return;

        // Create drop zone if it doesn't exist
        if (!dropZone && fileInput.parentElement) {
            const zone = document.createElement('div');
            zone.className = 'image-drop-zone';
            zone.innerHTML = `
                <div class="drop-zone-content">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                        <circle cx="8.5" cy="8.5" r="1.5"></circle>
                        <polyline points="21,15 16,10 5,21"></polyline>
                    </svg>
                    <p>Drag and drop images here or <span class="browse-link">browse</span></p>
                    <small>JPG, PNG, GIF, WebP - Max 5MB</small>
                </div>
            `;
            fileInput.parentElement.insertBefore(zone, fileInput);
            fileInput.style.display = 'none';

            // Click to browse
            zone.addEventListener('click', () => fileInput.click());
        }

        const activeDropZone = dropZone || document.querySelector('.image-drop-zone');

        if (activeDropZone) {
            // Drag events
            ['dragenter', 'dragover'].forEach(event => {
                activeDropZone.addEventListener(event, (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    activeDropZone.classList.add('drag-over');
                });
            });

            ['dragleave', 'drop'].forEach(event => {
                activeDropZone.addEventListener(event, (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    activeDropZone.classList.remove('drag-over');
                });
            });

            // Handle drop
            activeDropZone.addEventListener('drop', (e) => {
                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    handleFiles(files, fileInput, previewContainer);
                }
            });
        }

        // Handle file input change
        fileInput.addEventListener('change', () => {
            handleFiles(fileInput.files, fileInput, previewContainer);
        });
    }

    /**
     * Handle selected files
     */
    function handleFiles(files, input, previewContainer) {
        const maxSize = 5 * 1024 * 1024; // 5MB
        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        const validFiles = [];
        const errors = [];

        Array.from(files).forEach(file => {
            if (!allowedTypes.includes(file.type)) {
                errors.push(`${file.name}: Invalid file type. Only JPG, PNG, GIF, WebP allowed.`);
            } else if (file.size > maxSize) {
                errors.push(`${file.name}: File too large. Maximum size is 5MB.`);
            } else {
                validFiles.push(file);
            }
        });

        // Show errors
        if (errors.length > 0) {
            if (typeof BaDere !== 'undefined') {
                errors.forEach(err => BaDere.toast(err, 'error'));
            } else {
                alert(errors.join('\n'));
            }
        }

        // Create previews
        if (previewContainer && validFiles.length > 0) {
            // Clear existing previews
            previewContainer.innerHTML = '';

            validFiles.forEach((file, index) => {
                createImagePreview(file, index, previewContainer);
            });
        }

        // Update input with valid files only
        if (validFiles.length > 0) {
            const dt = new DataTransfer();
            validFiles.forEach(file => dt.items.add(file));
            input.files = dt.files;
        }
    }

    /**
     * Create image preview element
     */
    function createImagePreview(file, index, container) {
        const preview = document.createElement('div');
        preview.className = 'image-preview';
        preview.innerHTML = `
            <div class="preview-loading">Loading...</div>
            <button type="button" class="preview-remove" data-index="${index}">&times;</button>
        `;

        container.appendChild(preview);

        // Read and display image
        const reader = new FileReader();
        reader.onload = (e) => {
            const img = document.createElement('img');
            img.src = e.target.result;
            img.alt = file.name;
            preview.insertBefore(img, preview.firstChild);
            preview.querySelector('.preview-loading').remove();
        };
        reader.readAsDataURL(file);

        // Remove button handler
        preview.querySelector('.preview-remove').addEventListener('click', function() {
            preview.remove();
            // Note: Removing from FileList is handled by form reset or re-selection
        });
    }

    /**
     * Setup form validation
     */
    function setupFormValidation() {
        const form = document.querySelector('.list-book-form, form[action*="list_book"]');
        if (!form) return;

        form.addEventListener('submit', function(e) {
            const errors = validateForm(this);

            if (errors.length > 0) {
                e.preventDefault();

                // Show errors
                showFormErrors(errors);

                // Scroll to first error
                const firstError = form.querySelector('.is-invalid, .error');
                if (firstError) {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            } else {
                // Disable submit button to prevent double submission
                const submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.textContent = 'Submitting...';
                }
            }
        });

        // Real-time validation on blur
        form.querySelectorAll('input, textarea, select').forEach(field => {
            field.addEventListener('blur', function() {
                validateField(this);
            });

            // Clear error on input
            field.addEventListener('input', function() {
                this.classList.remove('is-invalid');
                const errorEl = this.parentElement.querySelector('.field-error');
                if (errorEl) errorEl.remove();
            });
        });
    }

    /**
     * Validate entire form
     */
    function validateForm(form) {
        const errors = [];
        const data = new FormData(form);

        // Title
        const title = data.get('title')?.trim();
        if (!title) {
            errors.push({ field: 'title', message: 'Title is required' });
        } else if (title.length < 2) {
            errors.push({ field: 'title', message: 'Title must be at least 2 characters' });
        }

        // Author
        const author = data.get('author')?.trim();
        if (!author) {
            errors.push({ field: 'author', message: 'Author is required' });
        }

        // Category
        const categoryId = data.get('category_id');
        if (!categoryId || categoryId === '0') {
            errors.push({ field: 'category_id', message: 'Please select a category' });
        }

        // Description
        const description = data.get('description')?.trim();
        if (!description) {
            errors.push({ field: 'description', message: 'Description is required' });
        } else if (description.length < 10) {
            errors.push({ field: 'description', message: 'Description must be at least 10 characters' });
        }

        // Location
        const city = data.get('city')?.trim();
        const region = data.get('region');
        if (!city) {
            errors.push({ field: 'city', message: 'City is required' });
        }
        if (!region) {
            errors.push({ field: 'region', message: 'Region is required' });
        }

        // Availability options
        const availPurchase = data.get('available_purchase');
        const availRent = data.get('available_rent');
        const availExchange = data.get('available_exchange');

        if (!availPurchase && !availRent && !availExchange) {
            errors.push({ field: 'availability', message: 'Select at least one availability option' });
        }

        // Price (required if purchase is selected)
        if (availPurchase) {
            const price = data.get('price');
            if (!price || parseFloat(price) <= 0) {
                errors.push({ field: 'price', message: 'Price is required for purchase option' });
            }
        }

        // Condition
        const condition = data.get('condition');
        if (!condition) {
            errors.push({ field: 'condition', message: 'Please select condition' });
        }

        // Mark invalid fields
        form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        errors.forEach(err => {
            const field = form.querySelector(`[name="${err.field}"]`);
            if (field) {
                field.classList.add('is-invalid');
            }
        });

        return errors;
    }

    /**
     * Validate single field
     */
    function validateField(field) {
        const name = field.name;
        const value = field.value?.trim();
        let error = null;

        switch (name) {
            case 'title':
                if (!value) error = 'Title is required';
                else if (value.length < 2) error = 'Title must be at least 2 characters';
                break;
            case 'author':
                if (!value) error = 'Author is required';
                break;
            case 'description':
                if (!value) error = 'Description is required';
                else if (value.length < 10) error = 'Description must be at least 10 characters';
                break;
            case 'city':
                if (!value) error = 'City is required';
                break;
            case 'price':
                const purchaseChecked = document.querySelector('[name="available_purchase"]')?.checked;
                if (purchaseChecked && (!value || parseFloat(value) <= 0)) {
                    error = 'Price is required for purchase option';
                }
                break;
        }

        // Show/hide error
        field.classList.toggle('is-invalid', !!error);

        const existingError = field.parentElement.querySelector('.field-error');
        if (existingError) existingError.remove();

        if (error) {
            const errorEl = document.createElement('span');
            errorEl.className = 'field-error';
            errorEl.textContent = error;
            field.parentElement.appendChild(errorEl);
        }

        return !error;
    }

    /**
     * Show form errors
     */
    function showFormErrors(errors) {
        // Remove existing error summary
        const existingSummary = document.querySelector('.form-error-summary');
        if (existingSummary) existingSummary.remove();

        // Create error summary
        const summary = document.createElement('div');
        summary.className = 'form-error-summary alert alert-danger';
        summary.innerHTML = `
            <strong>Please fix the following errors:</strong>
            <ul>
                ${errors.map(e => `<li>${e.message}</li>`).join('')}
            </ul>
        `;

        const form = document.querySelector('.list-book-form, form[action*="list_book"]');
        if (form) {
            form.insertBefore(summary, form.firstChild);
        }

        // Also show toast if available
        if (typeof BaDere !== 'undefined') {
            BaDere.toast('Please fix the errors in the form', 'error');
        }
    }

    /**
     * Setup availability toggle interactions
     */
    function setupAvailabilityToggles() {
        const purchaseCheckbox = document.querySelector('[name="available_purchase"]');
        const priceField = document.querySelector('[name="price"]');
        const priceWrapper = priceField?.closest('.form-group, .price-group');

        if (!purchaseCheckbox || !priceField) return;

        function togglePriceField() {
            const isRequired = purchaseCheckbox.checked;

            if (priceWrapper) {
                priceWrapper.style.display = isRequired ? 'block' : 'none';
            }

            priceField.required = isRequired;

            if (!isRequired) {
                priceField.value = '';
                priceField.classList.remove('is-invalid');
            }
        }

        purchaseCheckbox.addEventListener('change', togglePriceField);
        togglePriceField(); // Initial state
    }

    /**
     * Setup category-dependent fields
     */
    function setupCategoryDependentFields() {
        const listingTypeSelect = document.querySelector('[name="listing_type"]');
        const isbnField = document.querySelector('[name="isbn_doi"]');
        const isbnLabel = isbnField?.closest('.form-group')?.querySelector('label');

        if (!listingTypeSelect || !isbnField) return;

        listingTypeSelect.addEventListener('change', function() {
            const type = this.value;

            // Update ISBN/DOI label based on listing type
            if (isbnLabel) {
                if (type === 'paper') {
                    isbnLabel.textContent = 'DOI (optional)';
                    isbnField.placeholder = 'e.g., 10.1000/xyz123';
                } else {
                    isbnLabel.textContent = 'ISBN (optional)';
                    isbnField.placeholder = 'e.g., 978-3-16-148410-0';
                }
            }
        });
    }

    /**
     * Setup character counters for textareas
     */
    function setupCharacterCounters() {
        const description = document.querySelector('[name="description"]');
        if (!description) return;

        const maxLength = description.getAttribute('maxlength') || 5000;

        // Create counter
        const counter = document.createElement('div');
        counter.className = 'char-counter';
        counter.textContent = `0 / ${maxLength}`;
        description.parentElement.appendChild(counter);

        description.addEventListener('input', function() {
            const length = this.value.length;
            counter.textContent = `${length} / ${maxLength}`;
            counter.classList.toggle('near-limit', length > maxLength * 0.9);
        });
    }

    // Add styles for drop zone
    function addStyles() {
        if (document.getElementById('book-upload-styles')) return;

        const style = document.createElement('style');
        style.id = 'book-upload-styles';
        style.textContent = `
            .image-drop-zone {
                border: 2px dashed var(--border-color, #dee2e6);
                border-radius: 8px;
                padding: 40px 20px;
                text-align: center;
                cursor: pointer;
                transition: all 0.2s;
                background: var(--bg-light, #f8f9fa);
            }
            .image-drop-zone:hover,
            .image-drop-zone.drag-over {
                border-color: var(--primary-color, #28a745);
                background: rgba(40, 167, 69, 0.05);
            }
            .drop-zone-content svg {
                color: var(--text-muted, #6c757d);
                margin-bottom: 10px;
            }
            .drop-zone-content p {
                margin: 0 0 5px 0;
                color: var(--text-color, #333);
            }
            .drop-zone-content small {
                color: var(--text-muted, #6c757d);
            }
            .browse-link {
                color: var(--primary-color, #28a745);
                text-decoration: underline;
            }
            .image-previews {
                display: flex;
                flex-wrap: wrap;
                gap: 10px;
                margin-top: 15px;
            }
            .image-preview {
                position: relative;
                width: 100px;
                height: 100px;
                border-radius: 8px;
                overflow: hidden;
                background: var(--bg-light, #f8f9fa);
            }
            .image-preview img {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }
            .preview-remove {
                position: absolute;
                top: 4px;
                right: 4px;
                width: 24px;
                height: 24px;
                border-radius: 50%;
                border: none;
                background: rgba(0,0,0,0.6);
                color: white;
                cursor: pointer;
                font-size: 16px;
                line-height: 1;
            }
            .preview-loading {
                display: flex;
                align-items: center;
                justify-content: center;
                height: 100%;
                font-size: 12px;
                color: var(--text-muted, #6c757d);
            }
            .field-error {
                display: block;
                color: var(--danger-color, #dc3545);
                font-size: 12px;
                margin-top: 4px;
            }
            .is-invalid {
                border-color: var(--danger-color, #dc3545) !important;
            }
            .form-error-summary {
                margin-bottom: 20px;
            }
            .form-error-summary ul {
                margin: 10px 0 0 20px;
                padding: 0;
            }
            .char-counter {
                font-size: 12px;
                color: var(--text-muted, #6c757d);
                text-align: right;
                margin-top: 4px;
            }
            .char-counter.near-limit {
                color: var(--warning-color, #ffc107);
            }
        `;
        document.head.appendChild(style);
    }

    // Initialize when DOM is ready
    addStyles();

    if (typeof BaDere !== 'undefined') {
        BaDere.ready(init);
    } else {
        document.addEventListener('DOMContentLoaded', init);
    }
})();
