<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../helpers/AuthHelper.php';
require_once __DIR__ . '/../classes/VendorApplication.php';

// Require admin role
AuthHelper::requireAdmin('../index.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $applicationId = (int)($_POST['application_id'] ?? 0);
    $rejectionReason = trim($_POST['rejection_reason'] ?? '');
    $adminId = AuthHelper::getUserId();

    if ($applicationId <= 0) {
        $_SESSION['error_message'] = 'Invalid application ID.';
        header('Location: ../view/vendor_applications_admin.php');
        exit();
    }

    $applicationModel = new VendorApplication();

    // Reject the application
    $success = $applicationModel->rejectApplication(
        $applicationId,
        $adminId,
        !empty($rejectionReason) ? $rejectionReason : null
    );

    if ($success) {
        $_SESSION['success_message'] = 'Vendor application rejected.';
    } else {
        $_SESSION['error_message'] = 'Failed to reject application. It may have already been processed.';
    }

    header('Location: ../view/vendor_applications_admin.php');
    exit();
} else {
    header('Location: ../view/vendor_applications_admin.php');
    exit();
}
