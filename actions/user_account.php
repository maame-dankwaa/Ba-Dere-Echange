<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../controllers/UserController.php';

$controller = new UserController();
$controller->account();
