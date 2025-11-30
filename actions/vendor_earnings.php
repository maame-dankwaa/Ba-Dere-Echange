<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Location: ../view/vendor_earnings.php');
exit;

