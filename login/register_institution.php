<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../classes/User.php';

$error = '';
$old = [];

// Check if already logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header('Location: ../index.php');
    exit();
}

// Process registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $institutionName = trim($_POST['institution_name'] ?? '');
    $institutionType = trim($_POST['institution_type'] ?? '');
    $registrationNumber = trim($_POST['registration_number'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $website = trim($_POST['website'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    // Store old data for repopulation
    $old = [
        'institution_name' => $institutionName,
        'institution_type' => $institutionType,
        'registration_number' => $registrationNumber,
        'email' => $email,
        'phone' => $phone,
        'address' => $address,
        'website' => $website,
        'location' => $location
    ];

    // Validation
    if (empty($institutionName) || empty($institutionType) || empty($email) || empty($password) || empty($phone)) {
        $error = 'Please fill in all required fields.';
    } elseif ($password !== $password_confirm) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please provide a valid email address.';
    } else {
        try {
            $userModel = new User();

            // Check if email already exists
            if ($userModel->findByEmail($email)) {
                $error = 'Email already registered.';
            } else {
                // Handle document upload (optional)
                $verificationDocPath = null;
                if (isset($_FILES['verification_document']) && $_FILES['verification_document']['error'] === UPLOAD_ERR_OK) {
                    $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'];
                    $fileType = $_FILES['verification_document']['type'];
                    $fileSize = $_FILES['verification_document']['size'];
                    $maxSize = 5 * 1024 * 1024; // 5MB

                    if (!in_array($fileType, $allowedTypes)) {
                        $error = 'Only JPG, PNG, and PDF files are allowed for verification documents.';
                    } elseif ($fileSize > $maxSize) {
                        $error = 'File size must not exceed 5MB.';
                    } else {
                        // Create upload directory if it doesn't exist
                        $uploadDir = __DIR__ . '/../uploads/institution_verification/';
                        if (!is_dir($uploadDir)) {
                            mkdir($uploadDir, 0755, true);
                        }

                        // Generate unique filename
                        $extension = pathinfo($_FILES['verification_document']['name'], PATHINFO_EXTENSION);
                        $filename = 'inst_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
                        $uploadPath = $uploadDir . $filename;

                        if (move_uploaded_file($_FILES['verification_document']['tmp_name'], $uploadPath)) {
                            $verificationDocPath = 'uploads/institution_verification/' . $filename;
                        } else {
                            $error = 'Failed to upload verification document. Please try again.';
                        }
                    }
                }

                if (empty($error)) {
                    // Create institution account
                    $userData = [
                        'username' => $institutionName, // Use institution name as username
                        'email' => $email,
                        'phone' => $phone,
                        'location' => $location,
                        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                        'user_role' => 'customer', // Start as customer, can apply to become vendor
                        'account_type' => 'institution',
                        'institution_name' => $institutionName,
                        'institution_type' => $institutionType,
                        'institution_registration_number' => $registrationNumber,
                        'institution_address' => $address,
                        'institution_website' => $website,
                        'institution_verification_document' => $verificationDocPath,
                        'institution_verified' => 0, // Not verified yet
                        'created_at' => date('Y-m-d H:i:s')
                    ];

                    $userId = $userModel->createUser($userData);

                    // Auto login after registration
                    $_SESSION['user_id'] = $userId;
                    $_SESSION['user_role'] = 'customer';
                    $_SESSION['username'] = $institutionName;
                    $_SESSION['account_type'] = 'institution';
                    $_SESSION['logged_in'] = true;

                    header('Location: ../index.php');
                    exit();
                }
            }
        } catch (Exception $e) {
            error_log("Institution registration error: " . $e->getMessage());
            $error = 'An error occurred. Please try again later.';
        }
    }
}

$institutionTypes = [
    'University/College',
    'High School',
    'Primary/Middle School',
    'Library',
    'Bookstore',
    'Publisher',
    'Research Institution',
    'NGO/Non-Profit',
    'Other Educational Institution'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Institution Registration - Ba Dɛre Exchange</title>
    <link rel="stylesheet" href="../css/styles.css">
    <script src="../js/app.js" defer></script>
</head>
<body>
    <main class="auth-container" style="max-width: 700px;">
        <h1>Register Your Institution</h1>
        <p style="color: #666; margin-top: 8px;">
            For universities, schools, libraries, bookstores, and other educational institutions.
        </p>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error" style="margin-top: 16px;"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form id="registerInstitutionForm" action="register_institution.php" method="post" enctype="multipart/form-data" style="margin-top: 24px;">

            <h3 style="margin-bottom: 16px; margin-top: 24px;">Institution Information</h3>

            <label>Institution Name *
                <input type="text" name="institution_name"
                       placeholder="e.g., University of Ghana Library"
                       value="<?= htmlspecialchars($old['institution_name'] ?? '') ?>" required>
            </label>

            <label>Institution Type *
                <select name="institution_type" required>
                    <option value="">Select institution type</option>
                    <?php foreach ($institutionTypes as $type): ?>
                        <option value="<?= htmlspecialchars($type) ?>"
                                <?= ($old['institution_type'] ?? '') === $type ? 'selected' : '' ?>>
                            <?= htmlspecialchars($type) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label>Registration/License Number
                <input type="text" name="registration_number"
                       placeholder="Business registration or license number"
                       value="<?= htmlspecialchars($old['registration_number'] ?? '') ?>">
                <small style="color: #666; font-size: 14px;">Optional but recommended for verification</small>
            </label>

            <h3 style="margin-bottom: 16px; margin-top: 24px;">Contact Information</h3>

            <label>Institution Email *
                <input type="email" name="email"
                       placeholder="contact@institution.edu"
                       value="<?= htmlspecialchars($old['email'] ?? '') ?>" required>
            </label>

            <label>Phone Number *
                <input type="tel" name="phone"
                       placeholder="+233 XX XXX XXXX"
                       value="<?= htmlspecialchars($old['phone'] ?? '') ?>" required>
            </label>

            <label>Physical Address *
                <textarea name="address" rows="3"
                          placeholder="Enter institution's physical address"
                          required><?= htmlspecialchars($old['address'] ?? '') ?></textarea>
            </label>

            <label>City/Location *
                <input type="text" name="location"
                       placeholder="e.g., Accra"
                       value="<?= htmlspecialchars($old['location'] ?? '') ?>" required>
            </label>

            <label>Website
                <input type="url" name="website"
                       placeholder="https://www.institution.edu"
                       value="<?= htmlspecialchars($old['website'] ?? '') ?>">
            </label>

            <h3 style="margin-bottom: 16px; margin-top: 24px;">Verification Document</h3>

            <label>Upload Verification Document (Optional)
                <input type="file" name="verification_document" accept="image/*,.pdf"
                       style="padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                <small style="color: #666; font-size: 14px; display: block; margin-top: 4px;">
                    Upload business registration, license, or official letterhead (JPG, PNG, or PDF, max 5MB)
                </small>
            </label>

            <h3 style="margin-bottom: 16px; margin-top: 24px;">Account Security</h3>

            <label>Password *
                <input type="password" name="password" required>
                <small style="color: #666; font-size: 14px;">Minimum 6 characters</small>
            </label>

            <label>Confirm Password *
                <input type="password" name="password_confirm" required>
            </label>

            <button type="submit" class="btn-primary" style="margin-top: 24px;">Register Institution</button>
        </form>

        <p style="margin-top: 24px; text-align: center;">
            Individual registration? <a href="register.php">Register as an individual</a>
        </p>

        <p style="margin-top: 12px; text-align: center;">
            Already have an account? <a href="login.php">Login</a>
        </p>
    </main>
</body>
</html>
