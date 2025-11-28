<?php

ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);


    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    require_once __DIR__ . '/../helpers/AuthHelper.php';
    require_once __DIR__ . '/../classes/VendorApplication.php';
    require_once __DIR__ . '/../classes/User.php';

    // Require customer role (only customers can apply to become vendors)
    AuthHelper::requireCustomer('../login/login.php');

    $userId = AuthHelper::getUserId();
    $applicationModel = new VendorApplication();
    $userModel = new User();

    // Get user info to check if institution
    $user = $userModel->find($userId);
    $isInstitution = isset($user['account_type']) && $user['account_type'] === 'institution';

    // Check if user already has a pending application
    $hasPending = $applicationModel->hasPendingApplication($userId);

    // Get latest application to show status
    $latestApplication = $applicationModel->getLatestByUser($userId);

    // Preserve old form data on error
    $old = $_SESSION['old_form_data'] ?? [];
    $errors = $_SESSION['form_errors'] ?? [];
    unset($_SESSION['old_form_data'], $_SESSION['form_errors']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Apply to Become a Vendor - Ba Dɛre Exchange</title>
    <link rel="stylesheet" href="../css/styles.css">
    <script src="../js/app.js" defer></script>
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <div class="nav-wrapper">
                <a href="../index.php" class="logo">
                    <svg class="logo-icon" width="32" height="32" viewBox="0 0 32 32" fill="none">
                        <rect x="4" y="8" width="24" height="16" rx="2" stroke="currentColor" stroke-width="2"/>
                        <path d="M10 12h12M10 16h8M10 20h10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                    <div class="logo-text">
                        <h1>Ba Dere Exchange</h1>
                        <p>Come and bring</p>
                    </div>
                </a>
                <div class="nav-actions">
                    <a href="user_account.php" class="btn-secondary">My Account</a>
                </div>
            </div>
        </div>
    </nav>

    <main class="container" style="max-width: 800px; margin-top: 32px; margin-bottom: 48px;">
        <h1>Apply to Become a Vendor</h1>
        <?php if ($isInstitution): ?>
            <p style="margin-top: 16px; color: #666;">
                As a vendor institution, you'll be able to list books and academic resources from your <?= htmlspecialchars($user['institution_type'] ?? 'institution') ?> for sale, rent, or exchange on our platform.
            </p>
        <?php else: ?>
            <p style="margin-top: 16px; color: #666;">
                As a vendor, you'll be able to list your books and academic resources for sale, rent, or exchange on our platform.
            </p>
        <?php endif; ?>

        <?php if ($hasPending): ?>
            <div class="alert" style="margin-top: 24px; padding: 16px; background: #fff3cd; border: 1px solid #ffc107; border-radius: 8px;">
                <h3 style="margin-bottom: 8px;">Application Pending Review</h3>
                <p>You already have a pending vendor application. Our admin team will review it soon.</p>
                <p style="margin-top: 8px;">
                    <strong>Applied on:</strong> <?= htmlspecialchars($latestApplication['created_at']) ?>
                </p>
                <p style="margin-top: 16px;">
                    <a href="user_account.php" class="btn-secondary">Back to My Account</a>
                </p>
            </div>
        <?php elseif ($latestApplication && $latestApplication['status'] === 'rejected'): ?>
            <div class="alert alert-error" style="margin-top: 24px; padding: 16px; border-radius: 8px;">
                <h3 style="margin-bottom: 8px;">Previous Application Rejected</h3>
                <p>Your previous vendor application was rejected.</p>
                <?php if (!empty($latestApplication['rejection_reason'])): ?>
                    <p style="margin-top: 8px;">
                        <strong>Reason:</strong> <?= htmlspecialchars($latestApplication['rejection_reason']) ?>
                    </p>
                <?php endif; ?>
                <p style="margin-top: 12px; color: #666;">
                    You can submit a new application below if you'd like to reapply.
                </p>
            </div>
        <?php endif; ?>

        <?php if (!$hasPending): ?>
            <?php if (!empty($errors['general'])): ?>
                <div class="alert alert-error" style="margin-top: 24px;">
                    <?= htmlspecialchars($errors['general']) ?>
                </div>
            <?php endif; ?>

            <form action="../actions/apply_vendor_store.php" method="post" enctype="multipart/form-data" style="margin-top: 32px;">
                <?php if ($isInstitution): ?>
                    <section class="account-section">
                        <h2>Institution Information</h2>
                        <div style="padding: 16px; background: #e7f3ff; border: 1px solid #2196F3; border-radius: 8px; margin-top: 16px;">
                            <p style="margin: 0; color: #1976D2;">
                                <strong>Institution Account Detected:</strong> We already have your institution details (<?= htmlspecialchars($user['institution_name'] ?? '') ?>).
                                Please provide additional information about your vendor operations below.
                            </p>
                        </div>

                        <div style="margin-top: 16px;">
                            <label class="field-label">Department/Division (Optional)</label>
                            <input type="text" name="business_name" class="field-input"
                                   placeholder="e.g., Library Services, Bookstore Department"
                                   value="<?= htmlspecialchars($old['business_name'] ?? '') ?>">
                            <p style="margin-top: 4px; font-size: 14px; color: #666;">
                                Specific department or division handling book sales/exchanges.
                            </p>
                        </div>

                        <div style="margin-top: 16px;">
                            <label class="field-label">Vendor Operations Description (Optional)</label>
                            <textarea name="business_description" class="field-textarea"
                                      placeholder="Describe your institution's book distribution operations..."
                                      rows="4"><?= htmlspecialchars($old['business_description'] ?? '') ?></textarea>
                            <p style="margin-top: 4px; font-size: 14px; color: #666;">
                                Describe what types of books/resources your institution will offer and any special programs.
                            </p>
                        </div>

                        <div style="margin-top: 16px;">
                            <label class="field-label">Contact Phone Number</label>
                            <input type="tel" name="phone" class="field-input"
                                   placeholder="+233 XX XXX XXXX"
                                   value="<?= htmlspecialchars($old['phone'] ?? $user['phone'] ?? '') ?>">
                            <?php if (!empty($errors['phone'])): ?>
                                <div class="field-error"><?= htmlspecialchars($errors['phone']) ?></div>
                            <?php endif; ?>
                        </div>
                    </section>
                <?php else: ?>
                    <section class="account-section">
                        <h2>Business Information</h2>
                        <div style="margin-top: 16px;">
                            <label class="field-label">Business/Shop Name (Optional)</label>
                            <input type="text" name="business_name" class="field-input"
                                   placeholder="e.g., Accra Book Store"
                                   value="<?= htmlspecialchars($old['business_name'] ?? '') ?>">
                            <p style="margin-top: 4px; font-size: 14px; color: #666;">
                                If you operate under a business name, please provide it here.
                            </p>
                        </div>

                        <div style="margin-top: 16px;">
                            <label class="field-label">Business Description (Optional)</label>
                            <textarea name="business_description" class="field-textarea"
                                      placeholder="Briefly describe what types of books/resources you plan to sell..."
                                      rows="4"><?= htmlspecialchars($old['business_description'] ?? '') ?></textarea>
                        </div>

                        <div style="margin-top: 16px;">
                            <label class="field-label">Contact Phone Number</label>
                            <input type="tel" name="phone" class="field-input"
                                   placeholder="+233 XX XXX XXXX"
                                   value="<?= htmlspecialchars($old['phone'] ?? '') ?>">
                            <?php if (!empty($errors['phone'])): ?>
                                <div class="field-error"><?= htmlspecialchars($errors['phone']) ?></div>
                            <?php endif; ?>
                        </div>
                    </section>
                <?php endif; ?>

                <section class="account-section" style="margin-top: 24px;">
                    <h2>Application Details</h2>
                    <div style="margin-top: 16px;">
                        <label class="field-label">Why do you want to become a vendor? *</label>
                        <textarea name="application_reason" class="field-textarea"
                                  placeholder="Tell us why you want to sell on our platform..."
                                  rows="5"
                                  required><?= htmlspecialchars($old['application_reason'] ?? '') ?></textarea>
                        <?php if (!empty($errors['application_reason'])): ?>
                            <div class="field-error"><?= htmlspecialchars($errors['application_reason']) ?></div>
                        <?php endif; ?>
                        <p style="margin-top: 4px; font-size: 14px; color: #666;">
                            Please provide a brief explanation of your intentions.
                        </p>
                    </div>

                    <div style="margin-top: 16px;">
                        <label class="field-label">ID Document (Optional)</label>
                        <input type="file" name="id_document" accept="image/*,.pdf"
                               style="padding: 8px; border: 1px solid #ddd; border-radius: 4px; width: 100%;">
                        <p style="margin-top: 4px; font-size: 14px; color: #666;">
                            Upload a copy of your student ID, national ID, or other identification (Optional but recommended).
                        </p>
                    </div>
                </section>

                <div style="margin-top: 32px; display: flex; gap: 16px;">
                    <a href="user_account.php" class="btn-secondary">Cancel</a>
                    <button type="submit" class="btn-primary">Submit Application</button>
                </div>
            </form>
        <?php endif; ?>
    </main>
</body>
</html>
