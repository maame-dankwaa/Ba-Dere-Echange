<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../controllers/BookController.php';

$controller = new BookController();
$controller->search();         // uses ?q=search+term
