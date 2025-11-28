<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../helpers/AuthHelper.php';
require_once __DIR__ . '/../classes/VendorApplication.php';

// Require customer role
AuthHelper::requireCustomer('../login/login.php');

$userId = AuthHelper::getUserId();
$applicationModel = new VendorApplication();

// Check if already has pending application
if ($applicationModel->hasPendingApplication($userId)) {
    $_SESSION['form_errors'] = ['general' => 'You already have a pending application.'];
    header('Location: ../view/apply_vendor.php');
    exit();
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    $old = [];

    // Get form data
    $businessName = trim($_POST['business_name'] ?? '');
    $businessDescription = trim($_POST['business_description'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $applicationReason = trim($_POST['application_reason'] ?? '');

    // Store old data for repopulation
    $old = [
        'business_name' => $businessName,
        'business_description' => $businessDescription,
        'phone' => $phone,
        'application_reason' => $applicationReason
    ];

    // Validation
    if (empty($applicationReason)) {
        $errors['application_reason'] = 'Please provide a reason for your application.';
    } elseif (strlen($applicationReason) < 50) {
        $errors['application_reason'] = 'Please provide at least 50 characters explaining why you want to become a vendor.';
    }

    if (empty($phone)) {
        $errors['phone'] = 'Phone number is required.';
    }

    // Handle file upload (optional)
    $idDocumentPath = null;
    if (isset($_FILES['id_document']) && $_FILES['id_document']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'];
        $fileType = $_FILES['id_document']['type'];
        $fileSize = $_FILES['id_document']['size'];
        $maxSize = 5 * 1024 * 1024; // 5MB

        if (!in_array($fileType, $allowedTypes)) {
            $errors['id_document'] = 'Only JPG, PNG, and PDF files are allowed.';
        } elseif ($fileSize > $maxSize) {
            $errors['id_document'] = 'File size must not exceed 5MB.';
        } else {
            // Create upload directory if it doesn't exist
            $uploadDir = __DIR__ . '/../uploads/vendor_applications/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            // Generate unique filename
            $extension = pathinfo($_FILES['id_document']['name'], PATHINFO_EXTENSION);
            $filename = 'id_' . $userId . '_' . time() . '.' . $extension;
            $uploadPath = $uploadDir . $filename;

            if (move_uploaded_file($_FILES['id_document']['tmp_name'], $uploadPath)) {
                $idDocumentPath = 'uploads/vendor_applications/' . $filename;
            } else {
                $errors['id_document'] = 'Failed to upload file. Please try again.';
            }
        }
    }

    // If there are errors, redirect back with errors
    if (!empty($errors)) {
        $_SESSION['form_errors'] = $errors;
        $_SESSION['old_form_data'] = $old;
        header('Location: ../view/apply_vendor.php');
        exit();
    }

    // Create application
    try {
        $applicationData = [
            'user_id' => $userId,
            'business_name' => !empty($businessName) ? $businessName : null,
            'business_description' => !empty($businessDescription) ? $businessDescription : null,
            'phone' => $phone,
            'id_document' => $idDocumentPath,
            'application_reason' => $applicationReason
        ];

        $applicationId = $applicationModel->createApplication($applicationData);

        if ($applicationId) {
            $_SESSION['success_message'] = 'Your vendor application has been submitted successfully! We will review it and get back to you soon.';
            header('Location: ../view/user_account.php');
            exit();
        } else {
            $_SESSION['form_errors'] = ['general' => 'Failed to submit application. Please try again.'];
            $_SESSION['old_form_data'] = $old;
            header('Location: ../view/apply_vendor.php');
            exit();
        }
    } catch (Exception $e) {
        error_log("Vendor application error: " . $e->getMessage());
        $_SESSION['form_errors'] = ['general' => 'An error occurred. Please try again later.'];
        $_SESSION['old_form_data'] = $old;
        header('Location: ../view/apply_vendor.php');
        exit();
    }
} else {
    // Not a POST request
    header('Location: ../view/apply_vendor.php');
    exit();
}
