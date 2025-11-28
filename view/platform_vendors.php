<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Vendors - Ba Dɛre Exchange</title>
    <link rel="stylesheet" href="../css/styles.css">
    <script src="../js/app.js" defer></script>
</head>
<body>
    <main class="container">
        <h1>Vendors</h1>

        <?php
            ini_set('display_errors',1);
            ini_set('display_startup_errors',1);
            error_reporting(E_ALL);

 if (empty($vendors)): ?>
            <p>No vendors yet.</p>
        <?php else: ?>
            <ul class="vendor-list">
                <?php foreach ($vendors as $v): ?>
                    <li>
                        <strong><?= htmlspecialchars($v['username']) ?></strong>
                        <span><?= htmlspecialchars($v['location'] ?? '') ?></span>
                        <a href="/actions/vendor_show.php?id=<?= $v['user_id'] ?>">View books</a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </main>
</body>
</html>
