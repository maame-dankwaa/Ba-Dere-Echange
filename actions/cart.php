<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../controllers/CartController.php';

$controller = new CartController();
$controller->index();          // show cart
