<?php

ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);


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

// Process login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
        $old['email'] = $email;
    } else {
        try {
            $userModel = new User();
            $user = $userModel->verifyCredentials($email, $password);

            if ($user) {
                // Login successful
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['user_role'] = $user['user_role'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['logged_in'] = true;

                header('Location: ../index.php');
                exit();
            } else {
                $error = 'Invalid email or password.';
                $old['email'] = $email;
            }
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            $error = 'An error occurred. Please try again later.';
            $old['email'] = $email;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Ba Dɛre Exchange</title>
    <link rel="stylesheet" href="../css/styles.css">
    <script src="../js/app.js" defer></script>
</head>

<body>
    <main class="auth-container">
        <h1>Login</h1>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form id="loginForm" action="login.php" method="post">
            <label>Email
                <input type="email" name="email" value="<?= htmlspecialchars($old['email'] ?? '') ?>" required>
            </label>

            <label>Password
                <input type="password" name="password" required>
            </label>

            <button type="submit" class="btn-primary">Login</button>
        </form>

        <p style="margin-top: 24px; text-align: center;">
            Don't have an account?
            <a href="register.php">Sign up</a> or
            <a href="register_institution.php">Register as institution</a>
        </p>
    </main>
</body>
</html>
