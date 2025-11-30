<?php
/**
 * Book Controller
 * Handles book browsing, listing, and management with secure file uploads
 */

require_once __DIR__ . '/../classes/Book.php';
require_once __DIR__ . '/../classes/Category.php';
require_once __DIR__ . '/../classes/Review.php';
require_once __DIR__ . '/../services/Validator.php';
require_once __DIR__ . '/../services/Logger.php';

class BookController
{
    private Book $book;
    private Category $category;
    private Review $review;

    // Allowed file types for book images
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
    ];

    private const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB
    private const MAX_IMAGE_WIDTH = 2000;
    private const MAX_IMAGE_HEIGHT = 2000;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->book = new Book();
        $this->category = new Category();
        $this->review = new Review();
    }

    private function getInt(string $key, string $method = 'POST', int $default = 0, int $min = 0, int $max = PHP_INT_MAX): int
    {
        $source = $method === 'GET' ? $_GET : $_POST;
        $value = $source[$key] ?? $default;
        if (!is_numeric($value)) return $default;
        $value = (int)$value;
        return max($min, min($max, $value));
    }

    private function getString(string $key, string $method = 'POST', int $maxLength = 255): string
    {
        $source = $method === 'GET' ? $_GET : $_POST;
        $value = $source[$key] ?? '';
        if (!is_string($value)) return '';
        $value = trim($value);
        return strlen($value) > $maxLength ? substr($value, 0, $maxLength) : $value;
    }

    private function render(string $view, array $data = []): void
    {
        extract($data);
        require __DIR__ . '/../view/' . $view . '.php';
    }

    private function getFlash(): array
    {
        $flash = ['success' => $_SESSION['flash_success'] ?? null, 'error' => $_SESSION['flash_error'] ?? null];
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);
        return $flash;
    }

    private function setFlash(string $type, string $message): void
    {
        $_SESSION['flash_' . $type] = $message;
    }

    private function redirect(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }

    private function isPost(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    private function requireAuth(string $redirectTo = '/login/login.php'): void
    {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect($redirectTo);
        }
    }

    private function getUserId(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }

    /**
     * Browse books with filters
     */
    public function browse(): void
    {
        $page = $this->getInt('page', 'GET', 1, 1, 1000);

        $filters = [
            'category_id' => $this->getInt('category_id', 'GET', 0, 0) ?: null,
            'search' => $this->getString('q', 'GET') ?: ($this->getString('search', 'GET') ?: null),
            'min_price' => $this->getInt('min_price', 'GET', 0, 0) ?: null,
            'max_price' => $this->getInt('max_price', 'GET', 0, 0) ?: null,
            'condition' => $this->getString('condition', 'GET') ?: null,
            'min_rating' => $this->getInt('min_rating', 'GET', 0, 0, 5) ?: null,
            'location' => $this->getString('location', 'GET') ?: null,
            'is_rentable' => isset($_GET['is_rentable']) ? 1 : null,
            'is_exchangeable' => isset($_GET['is_exchangeable']) ? 1 : null,
            'sort' => $this->getString('sort', 'GET') ?: null,
        ];

        // Validate sort option
        $validSorts = ['price_low', 'price_high', 'rating', 'newest'];
        if ($filters['sort'] && !in_array($filters['sort'], $validSorts, true)) {
            $filters['sort'] = null;
        }

        // Validate condition
        $validConditions = ['like_new', 'good', 'acceptable', 'poor'];
        if ($filters['condition'] && !in_array($filters['condition'], $validConditions, true)) {
            $filters['condition'] = null;
        }

        $books = $this->book->getBooksWithFilters($filters, $page, 20);
        $categories = $this->category->getActiveCategories();

        $this->render('browse_books', [
            'books' => $books,
            'categories' => $categories,
            'filters' => $filters,
            'page' => $page,
            'flash' => $this->getFlash(),
        ]);
    }

    /**
     * Show single book details
     */
    public function show(): void
    {
        $id = $this->getInt('id', 'GET', 0, 1);

        if ($id <= 0) {
            $this->redirect('/index.php');
            return;
        }

        $book = $this->book->getBookDetails($id);

        if (!$book) {
            http_response_code(404);
            $this->render('error', [
                'error_code' => 404,
                'error_message' => 'Book not found.',
            ]);
            return;
        }

        // Increment view count
        $this->book->incrementViews($id);

        $reviews = $this->review->getBookReviews($id);
        $rating = $this->review->getBookRating($id);
        $similar = $this->book->getSimilarBooks($id, 4);

        $this->render('single_book', [
            'book' => $book,
            'reviews' => $reviews,
            'ratingStats' => $rating,
            'similarBooks' => $similar,
            'flash' => $this->getFlash(),
        ]);
    }

    /**
     * Search books
     */
    public function search(): void
    {
        $query = $this->getString('q', 'GET');
        $results = [];

        if ($query !== '') {
            // Limit query length
            $query = substr($query, 0, 100);
            $results = $this->book->searchBooks($query, 30);
        }

        $this->render('search', [
            'query' => $query,
            'results' => $results,
        ]);
    }

    /**
     * Show seller's books
     */
    public function sellerBooks(): void
    {
        $sellerId = $this->getInt('seller_id', 'GET', 0, 1);

        if ($sellerId <= 0) {
            $this->redirect('/index.php');
            return;
        }

        $filters = ['seller_id' => $sellerId];
        $books = $this->book->getBooksWithFilters($filters, 1, 50);

        $this->render('book_listing', [
            'books' => $books,
            'seller_id' => $sellerId,
        ]);
    }

    /**
     * Show book listing form
     */
    public function listForm(): void
    {
        $this->requireAuth('../view/list_book.php');

        $categories = $this->category->getActiveCategories();

        $listingTypes = [
            'academic_book' => 'Academic Book',
            'paper' => 'Academic Paper',
            'other_resource' => 'Other Academic Resource',
        ];

        $conditions = [
            'new' => 'New',
            'good' => 'Good',
            'fair' => 'Fair',
            'used' => 'Well used',
        ];

        $regions = Config::regions();

        $this->render('list_book', [
            'categories' => $categories,
            'listingTypes' => $listingTypes,
            'conditions' => $conditions,
            'regions' => $regions,
            'errors' => $_SESSION['form_errors'] ?? [],
            'old' => $_SESSION['old_form_data'] ?? [],
            'flash' => $this->getFlash(),
        ]);

        // Clear flash
        unset($_SESSION['form_errors'], $_SESSION['old_form_data']);
    }

    /**
     * Store new book listing
     */
    public function storeListing(): void
    {
        if (!$this->isPost()) {
            $this->redirect('../view/list_book.php');
            return;
        }

        $this->requireAuth('../view/list_book.php');

        // Check if user is vendor or admin
        $userRole = $_SESSION['user_role'] ?? 'guest';
        if ($userRole !== 'vendor' && $userRole !== 'admin') {
            $_SESSION['flash_error'] = 'You must be a vendor to list books.';
            $this->redirect('../view/list_book.php');
            return;
        }

        $sellerId = $this->getUserId();
        
        if (!$sellerId) {
            $_SESSION['flash_error'] = 'Invalid user session. Please login again.';
            $this->redirect('../login/login.php');
            return;
        }

        // Collect form data
        $data = [
            'seller_id' => $sellerId,
            'listing_type' => $this->getString('listing_type', 'POST'),
            'title' => $this->getString('title', 'POST'),
            'author' => $this->getString('author', 'POST'),
            'isbn_doi' => $this->getString('isbn_doi', 'POST'),
            'publication_year' => $this->getString('publication_year', 'POST'),
            'pages' => $this->getString('pages', 'POST'),
            'condition_type' => $this->getString('condition', 'POST'),
            'category_id' => $this->getInt('category_id', 'POST', 0),
            'description' => $this->getString('description', 'POST'),
            'available_quantity' => $this->getInt('available_quantity', 'POST', 1, 1),
            'available_purchase' => !empty($_POST['available_purchase']) ? 1 : 0,
            'available_rent' => !empty($_POST['available_rent']) ? 1 : 0,
            'available_exchange' => !empty($_POST['available_exchange']) ? 1 : 0,
            'price' => $this->getString('price', 'POST'),
            'rental_price' => $this->getString('rental_price', 'POST'),
            'rental_period_unit' => $this->getString('rental_period_unit', 'POST'),
            'rental_min_period' => $this->getInt('rental_min_period', 'POST', 1, 1),
            'rental_max_period' => $this->getInt('rental_max_period', 'POST', 30, 1),
            'exchange_duration' => $this->getInt('exchange_duration', 'POST', 14, 1),
            'exchange_duration_unit' => $this->getString('exchange_duration_unit', 'POST'),
            'city' => $this->getString('city', 'POST'),
            'region' => $this->getString('region', 'POST'),
        ];

        // Validate input
        $validator = Validator::make($data);

        $validator
            ->field('title', ['required', 'min:2', 'max:255'], 'Title')
            ->field('author', ['required', 'min:2', 'max:255'], 'Author')
            ->field('category_id', ['required', 'integer', 'min:1'], 'Category')
            ->field('description', ['required', 'min:10', 'max:5000'], 'Description')
            ->field('condition_type', ['required', 'in:like_new,good,acceptable,poor'], 'Condition')
            ->field('available_quantity', ['required', 'integer', 'min:1'], 'Available Quantity');

        // Validate price if purchase is available
        if ($data['available_purchase']) {
            $validator->field('price', ['required', 'numeric', 'positive'], 'Price');
        }

        // Validate rental price if rent is available
        if ($data['available_rent']) {
            $validator->field('rental_price', ['required', 'numeric', 'positive'], 'Rental Price');
            if (empty($data['rental_period_unit']) || !in_array($data['rental_period_unit'], ['day', 'week', 'month'])) {
                $validator->addError('rental_period_unit', 'Please select a valid rental period unit.');
            }
        }

        // Validate exchange duration if exchange is available
        if ($data['available_exchange']) {
            if (empty($data['exchange_duration_unit']) || !in_array($data['exchange_duration_unit'], ['day', 'week', 'month'])) {
                $validator->addError('exchange_duration_unit', 'Please select a valid exchange duration unit.');
            }
        }

        // At least one availability option
        if (!$data['available_purchase'] && !$data['available_rent'] && !$data['available_exchange']) {
            $validator->addError('availability', 'Select at least one availability option.');
        }

        // Validate publication year if provided
        if (!empty($data['publication_year'])) {
            $validator->field('publication_year', ['year'], 'Publication year');
        }

        // Validate ISBN if provided
        if (!empty($data['isbn_doi'])) {
            $validator->field('isbn_doi', ['max:50'], 'ISBN/DOI');
        }

        // Validate location fields
        $validator->field('city', ['required', 'min:2', 'max:100'], 'City');
        $validator->field('region', ['required', 'min:2', 'max:100'], 'Region');

        if ($validator->fails()) {
            $_SESSION['form_errors'] = $validator->errors();
            $_SESSION['old_form_data'] = $data;
            $this->redirect('../view/list_book.php');
            return;
        }

        // Handle image upload securely
        $coverImage = null;

        if (!empty($_FILES['images']['name'][0])) {
            $uploadResult = $this->handleImageUpload($_FILES['images']);

            if ($uploadResult['error']) {
                $_SESSION['form_errors'] = ['images' => $uploadResult['error']];
                $_SESSION['old_form_data'] = $data;
                $this->redirect('../view/list_book.php');
                return;
            }

            $coverImage = $uploadResult['path'];
        }

        $data['cover_image'] = $coverImage;

        // Save listing
        try {
            // Log the data being inserted for debugging
            if (defined('SHOW_DEBUG_ERRORS') && SHOW_DEBUG_ERRORS) {
                Logger::info('Attempting to create listing', [
                    'seller_id' => $sellerId,
                    'data_keys' => array_keys($data),
                    'has_cover_image' => !empty($data['cover_image']),
                ]);
            }
            
            $bookId = $this->book->createListing($data);

            if ($bookId && $bookId > 0) {
                Logger::info('Book listing created', [
                    'book_id' => $bookId,
                    'seller_id' => $sellerId,
                    'title' => $data['title'],
                ]);

                $this->setFlash('success', 'Your book has been listed successfully!');
                $this->redirect('../actions/single_book.php?id=' . $bookId);
            } else {
                throw new Exception('Failed to create listing: createListing returned ' . var_export($bookId, true));
            }

        } catch (Exception $e) {
            Logger::error('Book listing failed', [
                'seller_id' => $sellerId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $data,
            ]);

            // Show actual error in debug mode
            if (defined('SHOW_DEBUG_ERRORS') && SHOW_DEBUG_ERRORS) {
                // In debug mode, show the full error message
                $errorMessage = 'Could not create listing. Error: ' . htmlspecialchars($e->getMessage());
                
                // Add more context for common errors
                if (strpos($e->getMessage(), 'SQLSTATE') !== false || strpos($e->getMessage(), 'Database') !== false) {
                    $errorMessage .= ' This appears to be a database error. Please check that all required fields are filled correctly.';
                }
            } else {
                // In production, provide user-friendly messages
                $errorMessage = 'Could not create listing. Please try again.';
                
                if (strpos($e->getMessage(), 'SQLSTATE[23000]') !== false) {
                    $errorMessage = 'Could not create listing. This may be due to missing required information or a duplicate entry.';
                } elseif (strpos($e->getMessage(), 'SQLSTATE[42S22]') !== false) {
                    $errorMessage = 'Could not create listing. Database structure error detected. Please contact support.';
                } elseif (strpos($e->getMessage(), 'SQLSTATE[HY000]') !== false) {
                    $errorMessage = 'Could not create listing. Database connection issue. Please try again in a moment.';
                }
            }

            $_SESSION['form_errors'] = ['general' => $errorMessage];
            $_SESSION['old_form_data'] = $data;
            $this->redirect('../view/list_book.php');
        }
    }

    /**
     * Update existing book listing
     */
    public function updateListing(): void
    {
        if (!$this->isPost()) {
            $this->redirect('../index.php');
            return;
        }

        $this->requireAuth('../login/login.php');

        $bookId = $this->getInt('book_id', 'POST', 0);
        $userId = $this->getUserId();

        if ($bookId <= 0) {
            $this->redirect('../index.php');
            return;
        }

        // Load existing book
        $existingBook = $this->book->getById($bookId);
        if (!$existingBook) {
            $_SESSION['form_errors'] = ['general' => 'Book not found.'];
            $this->redirect('../index.php');
            return;
        }

        // Check ownership (or admin)
        $isOwner = (int)$existingBook['seller_id'] === (int)$userId;
        $isAdmin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';

        if (!$isOwner && !$isAdmin) {
            $_SESSION['form_errors'] = ['general' => 'You do not have permission to edit this listing.'];
            $this->redirect('../index.php');
            return;
        }

        // Collect form data
        $data = [
            'title' => $this->getString('title', 'POST'),
            'author' => $this->getString('author', 'POST'),
            'isbn_doi' => $this->getString('isbn_doi', 'POST'),
            'publication_year' => $this->getString('publication_year', 'POST'),
            'pages' => $this->getString('pages', 'POST'),
            'condition_type' => $this->getString('condition', 'POST'),
            'category_id' => $this->getInt('category_id', 'POST', 0),
            'description' => $this->getString('description', 'POST'),
            'available_quantity' => $this->getInt('available_quantity', 'POST', 1, 1),
            'available_purchase' => !empty($_POST['available_purchase']) ? 1 : 0,
            'available_rent' => !empty($_POST['available_rent']) ? 1 : 0,
            'available_exchange' => !empty($_POST['available_exchange']) ? 1 : 0,
            'price' => $this->getString('price', 'POST'),
            'rental_price' => $this->getString('rental_price', 'POST'),
            'rental_period_unit' => $this->getString('rental_period_unit', 'POST'),
            'rental_min_period' => $this->getInt('rental_min_period', 'POST', 1, 1),
            'rental_max_period' => $this->getInt('rental_max_period', 'POST', 30, 1),
            'exchange_duration' => $this->getInt('exchange_duration', 'POST', 14, 1),
            'exchange_duration_unit' => $this->getString('exchange_duration_unit', 'POST'),
        ];

        // Validate
        $validator = Validator::make($data);
        $validator
            ->field('title', ['required', 'min:2', 'max:255'], 'Title')
            ->field('author', ['required', 'min:2', 'max:255'], 'Author')
            ->field('category_id', ['required', 'integer', 'min:1'], 'Category')
            ->field('description', ['required', 'min:10', 'max:5000'], 'Description')
            ->field('condition_type', ['required', 'in:like_new,good,acceptable,poor'], 'Condition')
            ->field('available_quantity', ['required', 'integer', 'min:1'], 'Available Quantity');

        if ($data['available_purchase']) {
            $validator->field('price', ['required', 'numeric', 'positive'], 'Price');
        }

        if ($data['available_rent']) {
            $validator->field('rental_price', ['required', 'numeric', 'positive'], 'Rental Price');
            if (empty($data['rental_period_unit']) || !in_array($data['rental_period_unit'], ['day', 'week', 'month'])) {
                $validator->addError('rental_period_unit', 'Please select a valid rental period unit.');
            }
        }

        if ($data['available_exchange']) {
            if (empty($data['exchange_duration_unit']) || !in_array($data['exchange_duration_unit'], ['day', 'week', 'month'])) {
                $validator->addError('exchange_duration_unit', 'Please select a valid exchange duration unit.');
            }
        }

        if (!$data['available_purchase'] && !$data['available_rent'] && !$data['available_exchange']) {
            $validator->addError('availability', 'Select at least one availability option.');
        }

        if ($validator->fails()) {
            $_SESSION['form_errors'] = $validator->errors();
            $_SESSION['old_form_data'] = $data;
            $this->redirect('../view/edit_book.php?id=' . $bookId);
            return;
        }

        // Handle image upload if new image provided
        $coverImage = $existingBook['cover_image']; // Keep existing by default

        if (!empty($_FILES['images']['name'][0])) {
            $uploadResult = $this->handleImageUpload($_FILES['images']);

            if ($uploadResult['error']) {
                $_SESSION['form_errors'] = ['images' => $uploadResult['error']];
                $_SESSION['old_form_data'] = $data;
                $this->redirect('../view/edit_book.php?id=' . $bookId);
                return;
            }

            $coverImage = $uploadResult['path'];
        }

        $data['cover_image'] = $coverImage;

        // Update listing
        try {
            $success = $this->book->updateListing($bookId, $data);

            if ($success) {
                $this->setFlash('success', 'Your listing has been updated successfully!');
                $this->redirect('../actions/single_book.php?id=' . $bookId);
            } else {
                throw new Exception('Failed to update listing');
            }
        } catch (Exception $e) {
            $_SESSION['form_errors'] = ['general' => 'Could not update listing. Please try again.'];
            $_SESSION['old_form_data'] = $data;
            $this->redirect('../view/edit_book.php?id=' . $bookId);
        }
    }

    /**
     * Handle image upload securely
     *
     * @param array $files $_FILES array for images
     * @return array ['path' => string|null, 'error' => string|null]
     */
    private function handleImageUpload(array $files): array
    {
        // Get first file
        $tmpName = $files['tmp_name'][0] ?? null;
        $originalName = $files['name'][0] ?? '';
        $size = $files['size'][0] ?? 0;
        $error = $files['error'][0] ?? UPLOAD_ERR_NO_FILE;

        if ($error === UPLOAD_ERR_NO_FILE) {
            return ['path' => null, 'error' => null];
        }

        if ($error !== UPLOAD_ERR_OK) {
            return ['path' => null, 'error' => 'File upload failed. Please try again.'];
        }

        // Check file size
        if ($size > self::MAX_FILE_SIZE) {
            return ['path' => null, 'error' => 'Image must be less than 5MB.'];
        }

        // Verify it's a real uploaded file
        if (!is_uploaded_file($tmpName)) {
            Logger::security('Invalid file upload attempt', [
                'user_id' => $this->getUserId(),
            ]);
            return ['path' => null, 'error' => 'Invalid file upload.'];
        }

        // Check MIME type using finfo (not relying on file extension)
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($tmpName);

        if (!in_array($mimeType, self::ALLOWED_MIME_TYPES, true)) {
            return ['path' => null, 'error' => 'Only JPG, PNG, GIF, and WebP images are allowed.'];
        }

        // Verify it's actually an image
        $imageInfo = @getimagesize($tmpName);
        if ($imageInfo === false) {
            return ['path' => null, 'error' => 'Invalid image file.'];
        }

        // Check dimensions
        if ($imageInfo[0] > self::MAX_IMAGE_WIDTH || $imageInfo[1] > self::MAX_IMAGE_HEIGHT) {
            return ['path' => null, 'error' => 'Image dimensions must not exceed 2000x2000 pixels.'];
        }

        // Generate secure filename
        $extension = $this->getExtensionFromMime($mimeType);
        $filename = 'book_' . bin2hex(random_bytes(16)) . '.' . $extension;

        // Create upload directory outside project folder in public_html/uploads/books/
        // __DIR__ is controllers/, so we go up 2 levels: ../../
        $uploadDir = __DIR__ . '/../../uploads/books/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);

            // Create .htaccess to prevent PHP execution
            $htaccess = $uploadDir . '.htaccess';
            file_put_contents($htaccess, "php_flag engine off\nOptions -ExecCGI\nAddHandler cgi-script .php .pl .py .jsp .asp .sh .cgi\n");
        }

        $targetPath = $uploadDir . $filename;

        // Move uploaded file
        if (!move_uploaded_file($tmpName, $targetPath)) {
            Logger::error('Failed to move uploaded file', [
                'user_id' => $this->getUserId(),
                'target_path' => $targetPath,
            ]);
            return ['path' => null, 'error' => 'Failed to save image. Please try again.'];
        }

        // Set secure permissions
        chmod($targetPath, 0644);

        // Return path relative to project root for web access
        // Since images are in ../uploads/books/ from project root, use ../uploads/books/
        return ['path' => '../uploads/books/' . $filename, 'error' => null];
    }

    /**
     * Get file extension from MIME type
     *
     * @param string $mimeType
     * @return string
     */
    private function getExtensionFromMime(string $mimeType): string
    {
        $map = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
        ];

        return $map[$mimeType] ?? 'jpg';
    }
}
