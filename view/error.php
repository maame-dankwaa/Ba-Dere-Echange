<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error <?= htmlspecialchars($viewData['error_code'] ?? 404) ?> - Ba Dere Exchange</title>
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        .error-page {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 60vh;
            text-align: center;
            padding: 2rem;
        }
        .error-code {
            font-size: 6rem;
            font-weight: bold;
            color: #c96f4c;
            margin-bottom: 0.5rem;
        }
        .error-message {
            font-size: 1.5rem;
            color: #2d3436;
            margin-bottom: 2rem;
        }
        .error-description {
            color: #636e72;
            margin-bottom: 2rem;
            max-width: 500px;
        }
        .back-button {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background-color: #c96f4c;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        .back-button:hover {
            background-color: #a85a3b;
        }
    </style>
</head>
<body>
    <div class="error-page">
        <div class="error-code"><?= htmlspecialchars($viewData['error_code'] ?? 404) ?></div>
        <h1 class="error-message">
            <?php
            $code = $viewData['error_code'] ?? 404;
            switch ($code) {
                case 403:
                    echo 'Access Denied';
                    break;
                case 404:
                    echo 'Page Not Found';
                    break;
                case 500:
                    echo 'Server Error';
                    break;
                default:
                    echo 'Error';
            }
            ?>
        </h1>
        <p class="error-description">
            <?= htmlspecialchars($viewData['error_message'] ?? 'The page you are looking for could not be found.') ?>
        </p>
        <a href="/index.php" class="back-button">Back to Home</a>
    </div>
</body>
</html>
