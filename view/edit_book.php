<?php
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    require_once __DIR__ . '/../helpers/AuthHelper.php';
    require_once __DIR__ . '/../classes/Category.php';
    require_once __DIR__ . '/../classes/Book.php';

    // Require vendor role or above
    AuthHelper::requireVendor('../index.php');

    // Get book ID
    $bookId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    if ($bookId <= 0) {
        header('Location: ../index.php');
        exit();
    }

    // Load book
    $bookModel = new Book();
    $book = $bookModel->getById($bookId);

    if (!$book) {
        $_SESSION['flash'] = ['error' => 'Book not found.'];
        header('Location: ../index.php');
        exit();
    }

    // Check if user owns this book (or is admin)
    $userId = (int)AuthHelper::getUserId();
    $sellerId = (int)$book['seller_id'];

    if ($sellerId !== $userId && !AuthHelper::isAdmin()) {
        $_SESSION['flash'] = ['error' => 'You do not have permission to edit this listing.'];
        header('Location: ../index.php');
        exit();
    }

    // Load categories
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
    $old = $_SESSION['old_form_data'] ?? $book;
    $errors = $_SESSION['form_errors'] ?? [];
    unset($_SESSION['old_form_data'], $_SESSION['form_errors']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit <?= htmlspecialchars($book['title']) ?> - Ba Dɛre Exchange</title>
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
                    <a href="../view/user_account.php" class="btn-secondary">My Account</a>
                </div>
            </div>
        </div>
    </nav>
<main class="listing-container">
    <h1 class="listing-title">Edit Book Listing</h1>
    <p class="listing-subtitle">Update your listing details</p>

    <?php if (!empty($errors['general'])): ?>
        <div class="alert alert-error"><?= htmlspecialchars($errors['general']) ?></div>
    <?php endif; ?>

    <form id="editBookForm"
          action="../actions/edit_book_store.php"
          method="post"
          enctype="multipart/form-data"
          class="listing-form">

        <input type="hidden" name="book_id" value="<?= $book['book_id'] ?>">

        <!-- Current Image -->
        <?php if (!empty($book['cover_image'])): ?>
        <section class="listing-card">
            <div class="listing-card-header">
                <h2>Current Cover Image</h2>
            </div>
            <div class="listing-card-body">
                <img src="../<?= htmlspecialchars($book['cover_image']) ?>"
                     alt="Current cover"
                     style="max-width: 300px; max-height: 300px; object-fit: contain;">
                <p style="margin-top: 10px; color: #666;">Upload a new image below to replace this one</p>
            </div>
        </section>
        <?php endif; ?>

        <!-- Basic Information -->
        <section class="listing-card">
            <div class="listing-card-header">
                <h2>Basic Information</h2>
            </div>
            <div class="listing-card-body grid-2">
                <div class="field-group">
                    <label class="field-label">Title *</label>
                    <input type="text" name="title" class="field-input"
                           value="<?= htmlspecialchars($old['title'] ?? '') ?>">
                    <?php if (!empty($errors['title'])): ?>
                        <div class="field-error"><?= htmlspecialchars($errors['title']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="field-group">
                    <label class="field-label">Author(s) *</label>
                    <input type="text" name="author" class="field-input"
                           value="<?= htmlspecialchars($old['author'] ?? '') ?>">
                    <?php if (!empty($errors['author'])): ?>
                        <div class="field-error"><?= htmlspecialchars($errors['author']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="field-group">
                    <label class="field-label">ISBN / DOI</label>
                    <input type="text" name="isbn_doi" class="field-input"
                           value="<?= htmlspecialchars($old['isbn'] ?? '') ?>">
                </div>

                <div class="field-group">
                    <label class="field-label">Publication Year</label>
                    <input type="number" name="publication_year" class="field-input"
                           value="<?= htmlspecialchars($old['publication_year'] ?? '') ?>">
                </div>

                <div class="field-group">
                    <label class="field-label">Number of Pages</label>
                    <input type="number" name="pages" class="field-input"
                           value="<?= htmlspecialchars($old['pages'] ?? '') ?>">
                </div>

                <div class="field-group">
                    <label class="field-label">Condition *</label>
                    <select name="condition" class="field-select">
                        <option value="">Select condition</option>
                        <?php foreach ($conditions as $key => $label): ?>
                            <option value="<?= $key ?>"
                                <?= (($old['condition_type'] ?? '') === $key) ? 'selected' : '' ?>>
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
                              placeholder="Provide a detailed description..."><?= htmlspecialchars($old['description'] ?? '') ?></textarea>
                    <?php if (!empty($errors['description'])): ?>
                        <div class="field-error"><?= htmlspecialchars($errors['description']) ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- Upload New Image -->
        <section class="listing-card">
            <div class="listing-card-header">
                <h2><?= empty($book['cover_image']) ? 'Upload' : 'Replace' ?> Cover Image</h2>
            </div>
            <div class="listing-card-body">
                <div id="imageDropzone" class="image-dropzone">
                    <input id="imagesInput"
                           type="file"
                           name="images[]"
                           accept="image/*"
                           style="display: none;">
                    <div class="image-dropzone-inner">
                        <div class="upload-icon">⬆️</div>
                        <p class="upload-title">Click to upload or drag and drop</p>
                        <p class="upload-subtitle">Upload a new cover image (JPG, PNG, GIF, WebP - Max 5MB)</p>
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
                            <?= !empty($old['is_rentable']) ? 'checked' : '' ?>>
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
                            <?= !empty($old['is_rentable']) ? 'checked' : '' ?>>
                        <span>Available for Rent</span>
                    </label>
                    <div class="availability-price" id="rentPriceSection" style="display: <?= !empty($old['is_rentable']) ? 'flex' : 'none' ?>; gap: 10px; margin-top: 10px;">
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
                            <?= !empty($old['is_exchangeable']) ? 'checked' : '' ?>>
                        <span>Available for Exchange</span>
                    </label>
                    <div id="exchangePeriodSection" style="display: <?= !empty($old['is_exchangeable']) ? 'block' : 'none' ?>; margin-top: 10px;">
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
            </div>
        </section>

        <!-- Buttons -->
        <section class="listing-actions">
            <a href="../actions/single_book.php?id=<?= $book['book_id'] ?>" class="btn-secondary outline">Cancel</a>
            <button type="submit" class="btn-primary large">Update Listing</button>
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
