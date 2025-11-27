<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../helpers/AuthHelper.php';
require_once __DIR__ . '/../classes/Book.php';
require_once __DIR__ . '/../classes/FeaturedListing.php';

// Require authentication
AuthHelper::requireLogin();

$bookId = (int)($_GET['book_id'] ?? 0);

if ($bookId <= 0) {
    header('Location: ../index.php');
    exit;
}

// Get book details
$bookModel = new Book();
$book = $bookModel->getBookDetails($bookId);

if (!$book) {
    $_SESSION['flash_error'] = 'Book not found';
    header('Location: ../index.php');
    exit;
}

// Verify user owns this book
$userId = AuthHelper::getUserId();
if ((int)$book['seller_id'] !== $userId) {
    $_SESSION['flash_error'] = 'You can only feature your own listings';
    header('Location: ../index.php');
    exit;
}

// Check if already featured
$featuredModel = new FeaturedListing();
if ($featuredModel->isFeatured($bookId)) {
    $_SESSION['flash_warning'] = 'This listing is already featured';
    header('Location: manage_listings.php');
    exit;
}

// Get packages
$packages = FeaturedListing::getPackages();
$config = require __DIR__ . '/../config/settings/featured_listings.php';
$benefits = $config['benefits'] ?? [];

$flash = [
    'success' => $_SESSION['flash_success'] ?? null,
    'error' => $_SESSION['flash_error'] ?? null
];
unset($_SESSION['flash_success'], $_SESSION['flash_error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feature Your Listing - Ba Dɛre Exchange</title>
    <link rel="stylesheet" href="../css/styles.css">
    <?php include __DIR__ . '/../includes/sweetalert.php'; ?>
    <style>
        .package-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
            margin: 32px 0;
        }
        .package-card {
            background: white;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 32px 24px;
            text-align: center;
            transition: all 0.3s;
            position: relative;
        }
        .package-card:hover {
            border-color: #2563eb;
            transform: translateY(-4px);
            box-shadow: 0 10px 30px rgba(37, 99, 235, 0.1);
        }
        .package-popular {
            border-color: #2563eb;
        }
        .package-popular::before {
            content: 'Most Popular';
            position: absolute;
            top: -12px;
            left: 50%;
            transform: translateX(-50%);
            background: #2563eb;
            color: white;
            padding: 4px 16px;
            border-radius: 20px;
            font-size: 0.75em;
            font-weight: 600;
        }
        .package-price {
            font-size: 3em;
            font-weight: bold;
            color: #1f2937;
            margin: 16px 0;
        }
        .package-label {
            font-size: 1.5em;
            font-weight: 600;
            color: #374151;
        }
        .package-description {
            color: #6b7280;
            margin: 12px 0;
        }
        .package-savings {
            background: #d1fae5;
            color: #065f46;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.875em;
            font-weight: 600;
            display: inline-block;
            margin-top: 12px;
        }
        .benefits-list {
            list-style: none;
            padding: 0;
            margin: 32px 0;
            text-align: left;
        }
        .benefits-list li {
            padding: 12px 0;
            padding-left: 32px;
            position: relative;
            color: #374151;
        }
        .benefits-list li::before {
            content: '✓';
            position: absolute;
            left: 0;
            color: #10b981;
            font-weight: bold;
            font-size: 1.2em;
        }
        .book-preview {
            background: #f9fafb;
            padding: 24px;
            border-radius: 8px;
            margin-bottom: 32px;
            display: flex;
            gap: 16px;
            align-items: center;
        }
        .book-preview img {
            width: 80px;
            height: 120px;
            object-fit: cover;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <div class="nav-wrapper">
                <a href="../index.php" class="logo">
                    <h1>Ba Dere Exchange</h1>
                </a>
                <div class="nav-actions">
                    <a href="manage_listings.php" class="btn-secondary">My Listings</a>
                    <a href="../login/logout.php" class="btn-danger">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <main class="container" style="max-width: 1200px; margin-top: 30px;">
        <h1>✨ Feature Your Listing</h1>
        <p style="color: #6b7280; font-size: 1.1em;">Boost your visibility and sell faster with a featured listing</p>

        <?php if ($flash['success']): ?>
            <div class="alert alert-success"><?= htmlspecialchars($flash['success']) ?></div>
        <?php endif; ?>
        <?php if ($flash['error']): ?>
            <div class="alert alert-error"><?= htmlspecialchars($flash['error']) ?></div>
        <?php endif; ?>

        <div class="book-preview">
            <?php if (!empty($book['cover_image'])): ?>
                <img src="../<?= htmlspecialchars($book['cover_image']) ?>" alt="<?= htmlspecialchars($book['title']) ?>">
            <?php else: ?>
                <div style="width: 80px; height: 120px; background: #e5e7eb; border-radius: 4px; display: flex; align-items: center; justify-content: center; color: #9ca3af;">No Image</div>
            <?php endif; ?>
            <div>
                <h3 style="margin: 0 0 8px 0;"><?= htmlspecialchars($book['title']) ?></h3>
                <p style="color: #6b7280; margin: 0;">by <?= htmlspecialchars($book['author']) ?></p>
                <p style="margin: 8px 0 0 0;"><strong>GH₵<?= number_format($book['price'], 2) ?></strong></p>
            </div>
        </div>

        <section style="background: white; padding: 32px; border-radius: 12px; margin-bottom: 32px;">
            <h2>Why Feature Your Listing?</h2>
            <ul class="benefits-list">
                <?php foreach ($benefits as $benefit): ?>
                    <li><?= htmlspecialchars($benefit) ?></li>
                <?php endforeach; ?>
            </ul>
        </section>

        <h2 style="text-align: center; margin-bottom: 16px;">Choose Your Package</h2>
        <div class="package-grid">
            <?php foreach ($packages as $key => $package): ?>
                <div class="package-card <?= $key == '14' ? 'package-popular' : '' ?>">
                    <div class="package-label"><?= htmlspecialchars($package['label']) ?></div>
                    <div class="package-price">GH₵<?= number_format($package['price'], 0) ?></div>
                    <div class="package-description"><?= htmlspecialchars($package['description']) ?></div>

                    <?php if ($package['savings']): ?>
                        <div class="package-savings"><?= htmlspecialchars($package['savings']) ?></div>
                    <?php endif; ?>

                    <form action="../actions/feature_listing_process.php" method="POST" style="margin-top: 24px;">
                        <input type="hidden" name="book_id" value="<?= $bookId ?>">
                        <input type="hidden" name="duration_days" value="<?= $package['days'] ?>">
                        <input type="hidden" name="amount" value="<?= $package['price'] ?>">
                        <button type="submit" class="btn-primary" style="width: 100%;">
                            Select Package
                        </button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>

        <div style="text-align: center; margin: 32px 0; padding: 24px; background: #fef3c7; border-radius: 8px;">
            <p style="color: #92400e; margin: 0;">
                <strong>Secure Payment:</strong> All payments are processed securely through Paystack.
            </p>
        </div>
    </main>
</body>
</html>
