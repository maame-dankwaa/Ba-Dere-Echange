<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../controllers/WishlistController.php';

$controller = new WishlistController();
$controller->add();            // expects POST book_id
