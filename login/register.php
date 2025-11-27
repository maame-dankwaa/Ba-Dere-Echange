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
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    // Validation
    if (empty($username) || empty($email) || empty($password)) {
        $error = 'Please fill in all required fields.';
        $old = ['username' => $username, 'email' => $email, 'phone' => $phone, 'location' => $location];
    } elseif ($password !== $password_confirm) {
        $error = 'Passwords do not match.';
        $old = ['username' => $username, 'email' => $email, 'phone' => $phone, 'location' => $location];
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
        $old = ['username' => $username, 'email' => $email, 'phone' => $phone, 'location' => $location];
    } else {
        try {
            $userModel = new User();

            // Check if email already exists
            if ($userModel->findByEmail($email)) {
                $error = 'Email already registered.';
                $old = ['username' => $username, 'email' => $email, 'phone' => $phone, 'location' => $location];
            } else {
                // Create user
                $userData = [
                    'username' => $username,
                    'email' => $email,
                    'phone' => $phone,
                    'location' => $location,
                    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                    'user_role' => 'customer',
                    'created_at' => date('Y-m-d H:i:s')
                ];

                $userId = $userModel->createUser($userData);

                // Auto login after registration
                $_SESSION['user_id'] = $userId;
                $_SESSION['user_role'] = 'customer';
                $_SESSION['username'] = $username;
                $_SESSION['logged_in'] = true;

                header('Location: ../index.php');
                exit();
            }
        } catch (Exception $e) {
            $error = 'An error occurred. Please try again later.';
            $old = ['username' => $username, 'email' => $email, 'phone' => $phone, 'location' => $location];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - Ba Dɛre Exchange</title>
    <link rel="stylesheet" href="../css/styles.css">
    <script src="../js/app.js" defer></script>
</head>
<body>
    <main class="auth-container">
        <h1>Create Account</h1>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form id="registerForm" action="register.php" method="post">
            <label>Username
                <input type="text" name="username" value="<?= htmlspecialchars($old['username'] ?? '') ?>" required>
            </label>

            <label>Email
                <input type="email" name="email" value="<?= htmlspecialchars($old['email'] ?? '') ?>" required>
            </label>

            <label>Phone
                <input type="text" name="phone" value="<?= htmlspecialchars($old['phone'] ?? '') ?>">
            </label>

            <label>Location
                <input type="text" name="location" value="<?= htmlspecialchars($old['location'] ?? '') ?>">
            </label>

            <label>Password
                <input type="password" name="password" required>
            </label>

            <label>Confirm Password
                <input type="password" name="password_confirm" required>
            </label>

            <button type="submit" class="btn-primary">Register</button>
        </form>

        <p style="margin-top: 24px; text-align: center;">
            Registering for an institution? <a href="register_institution.php">Register as an institution</a>
        </p>

        <p style="margin-top: 12px; text-align: center;">
            Already have an account? <a href="login.php">Login</a>
        </p>
    </main>
</body>
</html>
