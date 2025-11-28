<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['in_wishlist' => false]);
    exit;
}

require_once __DIR__ . '/../classes/Database.php';

$bookId = isset($_GET['book_id']) ? (int)$_GET['book_id'] : 0;
$userId = (int)$_SESSION['user_id'];

if ($bookId <= 0) {
    echo json_encode(['in_wishlist' => false]);
    exit;
}

try {
    $db = Database::getInstance();
    $sql = "SELECT wishlist_id FROM fp_wishlists
            WHERE user_id = :user_id AND book_id = :book_id
            LIMIT 1";

    $result = $db->fetch($sql, [
        'user_id' => $userId,
        'book_id' => $bookId
    ]);

    echo json_encode(['in_wishlist' => !empty($result)]);
} catch (Exception $e) {
    echo json_encode(['in_wishlist' => false]);
}
