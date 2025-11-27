<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Vendor Books - Ba Dɛre Exchange</title>
    <link rel="stylesheet" href="../css/styles.css">
    <script src="../js/app.js" defer></script>
</head>
<body>
    <main class="container">
        <?php if (!empty($vendor)): ?>
            <header>
                <h1><?= htmlspecialchars($vendor['username']) ?>'s Listings</h1>
                <p>Location: <?= htmlspecialchars($vendor['location'] ?? '') ?></p>
            </header>
        <?php else: ?>
            <h1>Book Listings</h1>
        <?php endif; ?>

        <?php if (empty($books)): ?>
            <p>No books listed yet.</p>
        <?php else: ?>
            <div class="book-list">
                <?php foreach ($books as $book): ?>
                    <article class="book-card">
                        <h3><?= htmlspecialchars($book['title']) ?></h3>
                        <p><?= htmlspecialchars($book['author'] ?? '') ?></p>
                        <p>GH₵<?= number_format($book['price'], 2) ?></p>
                        <a href="../actions/single_book.php?id=<?= $book['book_id'] ?>">View</a>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
