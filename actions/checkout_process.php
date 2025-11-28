<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../controllers/CheckoutController.php';

$controller = new CheckoutController();
$controller->process();        // creates transactions, clears cart
