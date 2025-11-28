<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../controllers/VendorController.php';

$controller = new VendorController();
$controller->show();           // renders view/book_listing.php
