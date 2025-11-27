<?php
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    require_once __DIR__ . '/../helpers/AuthHelper.php';
    require_once __DIR__ . '/../classes/Category.php';

    // Require vendor role or above (vendor or admin)
    AuthHelper::requireVendor('../index.php');

    // Load categories and other form data
    $categoryModel = new Category();
    $categories = $categoryModel->getActiveCategories();

    // Form options
    $listingTypes = [
        'book' => 'Physical Book',
        'paper' => 'Academic Paper/Document',
        'digital' => 'Digital Resource'
    ];

    $conditions = [
        'like_new' => 'Like New',
        'good' => 'Good',
        'acceptable' => 'Acceptable',
        'poor' => 'Poor'
    ];

    $regions = [
        'Greater Accra', 'Ashanti', 'Central', 'Eastern', 'Western',
        'Northern', 'Upper East', 'Upper West', 'Volta', 'Oti',
        'Bono', 'Bono East', 'Ahafo', 'Savannah', 'North East', 'Western North'
    ];

    // Preserve old form data on error
    $old = $_SESSION['old_form_data'] ?? [];
    $errors = $_SESSION['form_errors'] ?? [];
    unset($_SESSION['old_form_data'], $_SESSION['form_errors']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>List Your Book or Paper - Ba Dɛre Exchange</title>
    <link rel="stylesheet" href="../css/styles.css">
    <script src="../js/app.js" defer></script>
    <script src="../js/book_upload.js" defer></script>
    <?php include __DIR__ . '/../includes/sweetalert.php'; ?>
</head>
<body class="listing-body">
   <nav class="navbar">
        <div class="container">
            <div class="nav-wrapper">
                <a href="../index.php" class="logo">
                    <svg class="logo-icon" width="32" height="32" viewBox="0 0 32 32" fill="none">
                        <rect x="4" y="8" width="24" height="16" rx="2" stroke="currentColor" stroke-width="2"/>
                        <path d="M10 12h12M10 16h8M10 20h10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                    <div class="logo-text">
                        <h1>Ba Dere Exchange</h1>
                        <p>Come and bring</p>
                    </div>
                </a>
                <div class="nav-actions">
                    <form class="search-bar" action="../actions/search.php" method="get">
                        <svg class="search-icon" width="20" height="20" viewBox="0 0 20 20" fill="none">
                            <circle cx="9" cy="9" r="6" stroke="currentColor" stroke-width="2"/>
                            <path d="M14 14l4 4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                        <input type="text" name="q" placeholder="Search for books..." required>
                        <button type="submit" style="display: none;">Search</button>
                    </form>

                    <?php if (AuthHelper::isLoggedIn()): ?>
                        <!-- Logged in users: Show wishlist, profile, and list book (for vendors) -->
                        <a href="../actions/wishlist.php" class="icon-btn" aria-label="Favorites">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" stroke="currentColor" stroke-width="2"/>
                            </svg>
                        </a>

                        <a href="../view/user_account.php" class="icon-btn" aria-label="Profile">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                <circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="2"/>
                                <path d="M6 20c0-4 2.5-6 6-6s6 2 6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                        </a>

                        <?php if (AuthHelper::canCreateListing()): ?>
                        <a href="../view/list_book.php" class="btn-primary">List a Book</a>
                        <?php else: ?>
                        <a href="../view/apply_vendor.php" class="btn-primary">Become a Vendor</a>
                        <?php endif; ?>

                        <?php if (AuthHelper::isAdmin()): ?>
                        <a href="../admin/admin_dashboard.php" class="btn-secondary">Admin</a>
                        <?php endif; ?>
                    <?php else: ?>
                        <!-- Guest users: Show login and register -->
                        <a href="../login/login.php" class="btn-secondary">Login</a>
                        <a href="../login/register.php" class="btn-primary">Sign Up</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>
<main class="listing-container">
    <h1 class="listing-title">List Your Book or Paper</h1>
    <p class="listing-subtitle">Share your academic resources with the community</p>

    <?php if (!empty($errors['general'])): ?>
        <div class="alert alert-error"><?= htmlspecialchars($errors['general']) ?></div>
    <?php endif; ?>

    <form id="listBookForm"
          action="../actions/list_book_store.php"
          method="post"
          enctype="multipart/form-data"
          class="listing-form">

        <!-- Type of Listing -->
        <section class="listing-card">
            <div class="listing-card-header">
                <h2>Type of Listing</h2>
            </div>
            <div class="listing-card-body">
                <label class="field-label">Type of Listing</label>
                <select name="listing_type" class="field-select">
                    <?php foreach ($listingTypes as $key => $label): ?>
                        <option value="<?= $key ?>"
                            <?= (($old['listing_type'] ?? '') === $key) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </section>

        <!-- Basic Information -->
        <section class="listing-card">
            <div class="listing-card-header">
                <h2>Basic Information</h2>
            </div>
            <div class="listing-card-body grid-2">
                <div class="field-group">
                    <label class="field-label">Title *</label>
                    <input type="text" name="title" class="field-input"
                           placeholder="Enter book/paper title"
                           value="<?= htmlspecialchars($old['title'] ?? '') ?>">
                    <?php if (!empty($errors['title'])): ?>
                        <div class="field-error"><?= htmlspecialchars($errors['title']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="field-group">
                    <label class="field-label">Author(s) *</label>
                    <input type="text" name="author" class="field-input"
                           placeholder="Enter author name(s)"
                           value="<?= htmlspecialchars($old['author'] ?? '') ?>">
                    <?php if (!empty($errors['author'])): ?>
                        <div class="field-error"><?= htmlspecialchars($errors['author']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="field-group">
                    <label class="field-label">ISBN / DOI</label>
                    <input type="text" name="isbn_doi" class="field-input"
                           placeholder="978-X-XXX-XXXXX-X"
                           value="<?= htmlspecialchars($old['isbn_doi'] ?? '') ?>">
                </div>

                <div class="field-group">
                    <label class="field-label">Publication Year</label>
                    <input type="number" name="publication_year" class="field-input"
                           placeholder="2024"
                           value="<?= htmlspecialchars($old['publication_year'] ?? '') ?>">
                </div>

                <div class="field-group">
                    <label class="field-label">Number of Pages</label>
                    <input type="number" name="pages" class="field-input"
                           placeholder="456"
                           value="<?= htmlspecialchars($old['pages'] ?? '') ?>">
                </div>

                <div class="field-group">
                    <label class="field-label">Condition *</label>
                    <select name="condition" class="field-select">
                        <option value="">Select condition</option>
                        <?php foreach ($conditions as $key => $label): ?>
                            <option value="<?= $key ?>"
                                <?= (($old['condition'] ?? '') === $key) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!empty($errors['condition'])): ?>
                        <div class="field-error"><?= htmlspecialchars($errors['condition']) ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- Category & Description -->
        <section class="listing-card">
            <div class="listing-card-header">
                <h2>Details</h2>
            </div>
            <div class="listing-card-body grid-1">
                <div class="field-group">
                    <label class="field-label">Category *</label>
                    <select name="category_id" class="field-select">
                        <option value="">Select category</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['category_id'] ?>"
                                <?= (($old['category_id'] ?? '') == $cat['category_id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!empty($errors['category_id'])): ?>
                        <div class="field-error"><?= htmlspecialchars($errors['category_id']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="field-group">
                    <label class="field-label">Description *</label>
                    <textarea name="description" class="field-textarea"
                              placeholder="Provide a detailed description of the book/paper..."><?= htmlspecialchars($old['description'] ?? '') ?></textarea>
                    <?php if (!empty($errors['description'])): ?>
                        <div class="field-error"><?= htmlspecialchars($errors['description']) ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- Images -->
        <section class="listing-card">
            <div class="listing-card-header">
                <h2>Images</h2>
            </div>
            <div class="listing-card-body">
                <div id="imageDropzone" class="image-dropzone">
                    <input id="imagesInput"
                           type="file"
                           name="images[]"
                           accept="image/*"
                           multiple
                           style="display: none;">
                    <div class="image-dropzone-inner">
                        <div class="upload-icon">⬆️</div>
                        <p class="upload-title">Click to upload or drag and drop</p>
                        <p class="upload-subtitle">Upload cover image and additional photos (Max 5 images)</p>
                        <p id="imageDropzoneText" class="upload-count"></p>
                    </div>
                </div>
                <div id="imagePreview" style="display: flex; gap: 10px; flex-wrap: wrap; margin-top: 16px;"></div>
            </div>
        </section>

        <!-- Pricing & Availability -->
        <section class="listing-card">
            <div class="listing-card-header">
                <h2>Pricing &amp; Availability</h2>
            </div>
            <div class="listing-card-body">
                <div class="field-group">
                    <label class="field-label">Available Quantity *</label>
                    <input type="number" name="available_quantity" class="field-input"
                           placeholder="How many copies do you have?"
                           min="1"
                           value="<?= htmlspecialchars($old['available_quantity'] ?? '1') ?>">
                    <small class="field-hint">Enter the number of copies you have available</small>
                    <?php if (!empty($errors['available_quantity'])): ?>
                        <div class="field-error"><?= htmlspecialchars($errors['available_quantity']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="availability-option">
                    <label>
                        <input type="checkbox" name="available_purchase" value="1"
                            <?= !empty($old['available_purchase']) ? 'checked' : '' ?>>
                        <span>Available for Purchase</span>
                    </label>
                    <div class="availability-price">
                        <span>Price (₵)</span>
                        <input type="number" step="0.01" name="price"
                               value="<?= htmlspecialchars($old['price'] ?? '') ?>">
                    </div>
                </div>

                <div class="availability-option">
                    <label>
                        <input type="checkbox" name="available_rent" value="1" id="rentCheckbox"
                            <?= !empty($old['available_rent']) ? 'checked' : '' ?>>
                        <span>Available for Rent</span>
                    </label>
                    <div class="availability-price" id="rentPriceSection" style="display: <?= !empty($old['available_rent']) ? 'flex' : 'none' ?>; gap: 10px; margin-top: 10px;">
                        <div>
                            <span>Rental Price (₵)</span>
                            <input type="number" step="0.01" name="rental_price"
                                   value="<?= htmlspecialchars($old['rental_price'] ?? '') ?>"
                                   placeholder="Price">
                        </div>
                        <div>
                            <span>Per</span>
                            <select name="rental_period_unit" class="field-select" style="padding: 8px;">
                                <option value="day" <?= ($old['rental_period_unit'] ?? 'day') === 'day' ? 'selected' : '' ?>>Day</option>
                                <option value="week" <?= ($old['rental_period_unit'] ?? '') === 'week' ? 'selected' : '' ?>>Week</option>
                                <option value="month" <?= ($old['rental_period_unit'] ?? '') === 'month' ? 'selected' : '' ?>>Month</option>
                            </select>
                        </div>
                        <div>
                            <span>Min Duration</span>
                            <input type="number" name="rental_min_period" min="1"
                                   value="<?= htmlspecialchars($old['rental_min_period'] ?? '1') ?>"
                                   placeholder="1" style="width: 70px;">
                        </div>
                        <div>
                            <span>Max Duration</span>
                            <input type="number" name="rental_max_period" min="1"
                                   value="<?= htmlspecialchars($old['rental_max_period'] ?? '30') ?>"
                                   placeholder="30" style="width: 70px;">
                        </div>
                    </div>
                </div>

                <div class="availability-option">
                    <label>
                        <input type="checkbox" name="available_exchange" value="1" id="exchangeCheckbox"
                            <?= !empty($old['available_exchange']) ? 'checked' : '' ?>>
                        <span>Available for Exchange</span>
                    </label>
                    <div id="exchangePeriodSection" style="display: <?= !empty($old['available_exchange']) ? 'block' : 'none' ?>; margin-top: 10px;">
                        <label style="font-size: 0.9em; color: #666;">Exchange Duration</label>
                        <div style="display: flex; gap: 10px; margin-top: 5px;">
                            <input type="number" name="exchange_duration" min="1"
                                   value="<?= htmlspecialchars($old['exchange_duration'] ?? '14') ?>"
                                   placeholder="14" style="width: 70px;">
                            <select name="exchange_duration_unit" class="field-select" style="padding: 8px;">
                                <option value="day" <?= ($old['exchange_duration_unit'] ?? 'day') === 'day' ? 'selected' : '' ?>>Days</option>
                                <option value="week" <?= ($old['exchange_duration_unit'] ?? '') === 'week' ? 'selected' : '' ?>>Weeks</option>
                                <option value="month" <?= ($old['exchange_duration_unit'] ?? '') === 'month' ? 'selected' : '' ?>>Months</option>
                            </select>
                        </div>
                        <small class="field-hint">How long the exchange will last before books are returned</small>
                    </div>
                </div>

                <script>
                document.getElementById('rentCheckbox')?.addEventListener('change', function(e) {
                    document.getElementById('rentPriceSection').style.display = e.target.checked ? 'flex' : 'none';
                });
                document.getElementById('exchangeCheckbox')?.addEventListener('change', function(e) {
                    document.getElementById('exchangePeriodSection').style.display = e.target.checked ? 'block' : 'none';
                });
                </script>

                <?php if (!empty($errors['price'])): ?>
                    <div class="field-error"><?= htmlspecialchars($errors['price']) ?></div>
                <?php endif; ?>
                <?php if (!empty($errors['availability'])): ?>
                    <div class="field-error"><?= htmlspecialchars($errors['availability']) ?></div>
                <?php endif; ?>
            </div>
        </section>

        <!-- Location -->
        <section class="listing-card">
            <div class="listing-card-header">
                <h2>Location</h2>
            </div>
            <div class="listing-card-body grid-2">
                <div class="field-group">
                    <label class="field-label">City *</label>
                    <input type="text" name="city" class="field-input"
                           placeholder="e.g., Accra"
                           value="<?= htmlspecialchars($old['city'] ?? '') ?>">
                    <?php if (!empty($errors['city'])): ?>
                        <div class="field-error"><?= htmlspecialchars($errors['city']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="field-group">
                    <label class="field-label">Region *</label>
                    <select name="region" class="field-select">
                        <option value="">Select region</option>
                        <?php foreach ($regions as $region): ?>
                            <option value="<?= htmlspecialchars($region) ?>"
                                <?= (($old['region'] ?? '') === $region) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($region) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!empty($errors['region'])): ?>
                        <div class="field-error"><?= htmlspecialchars($errors['region']) ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- Buttons -->
        <section class="listing-actions">
            <a href="../index.php" class="btn-secondary outline">Cancel</a>
            <button type="submit" class="btn-primary large">Create Listing</button>
        </section>
    </form>
</main>

<script>
// Image upload handling
document.addEventListener('DOMContentLoaded', function() {
    const dropzone = document.getElementById('imageDropzone');
    const fileInput = document.getElementById('imagesInput');
    const dropzoneText = document.getElementById('imageDropzoneText');

    if (dropzone && fileInput) {
        // Click to upload
        dropzone.addEventListener('click', function(e) {
            if (e.target !== fileInput) {
                fileInput.click();
            }
        });

        // Drag and drop
        dropzone.addEventListener('dragover', function(e) {
            e.preventDefault();
            dropzone.style.borderColor = 'var(--primary-color)';
            dropzone.style.backgroundColor = 'rgba(37, 99, 235, 0.05)';
        });

        dropzone.addEventListener('dragleave', function(e) {
            e.preventDefault();
            dropzone.style.borderColor = '#e5e7eb';
            dropzone.style.backgroundColor = 'transparent';
        });

        dropzone.addEventListener('drop', function(e) {
            e.preventDefault();
            dropzone.style.borderColor = '#e5e7eb';
            dropzone.style.backgroundColor = 'transparent';

            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
                updateFileCount(files.length);
            }
        });

        // File selection change
        fileInput.addEventListener('change', function() {
            updateFileCount(fileInput.files.length);
        });

        function updateFileCount(count) {
            if (count > 0) {
                dropzoneText.textContent = count + ' image' + (count > 1 ? 's' : '') + ' selected';
                showImagePreviews(fileInput.files);
            } else {
                dropzoneText.textContent = '';
                document.getElementById('imagePreview').innerHTML = '';
            }
        }

        function showImagePreviews(files) {
            const previewContainer = document.getElementById('imagePreview');
            previewContainer.innerHTML = '';

            Array.from(files).forEach((file, index) => {
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const imgWrapper = document.createElement('div');
                        imgWrapper.style.cssText = 'position: relative; width: 100px; height: 100px;';

                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.style.cssText = 'width: 100%; height: 100%; object-fit: cover; border-radius: 8px; border: 2px solid #e5e7eb;';

                        imgWrapper.appendChild(img);
                        previewContainer.appendChild(imgWrapper);
                    };
                    reader.readAsDataURL(file);
                }
            });
        }
    }
});
</script>
</body>
</html>
