<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../controllers/WishlistController.php';

$controller = new WishlistController();
$controller->remove();         // expects GET book_id
