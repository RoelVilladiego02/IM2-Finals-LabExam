<?php
// Start the session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$current_page = basename($_SERVER['PHP_SELF']);

// Check if the user is logged in
$isLoggedIn = isset($_SESSION['user_id']);

// Redirect to login if not logged in
if (!$isLoggedIn && $current_page !== 'login.php') {
    header('Location: login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) : 'GoCrypto.PH'; ?></title>
    
    <!-- SEO Meta Tags -->
    <meta name="description" content="GoCrypto.PH - Your Trusted Cryptocurrency Trading Platform">
    <meta name="keywords" content="cryptocurrency, crypto trading, bitcoin, blockchain">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="path/to/favicon.png">
    
    <!-- CSS Dependencies (Combined and Optimized) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.8.1/font/bootstrap-icons.min.css">
    
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-gradient-start: #f6d365;
            --primary-gradient-end: #fda085;
            --navbar-bg: rgba(0, 0, 0, 0.7);
            --content-bg: rgba(255, 255, 255, 0.95);
            --font-family: 'Inter', 'Arial', sans-serif;
        }

        body {
            background: linear-gradient(135deg, var(--primary-gradient-start) 0%, var(--primary-gradient-end) 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-attachment: fixed;
            display: flex;
            flex-direction: column;
        }

        .navbar {
            background: var(--navbar-bg) !important;
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .navbar-brand {
            font-weight: bold;
            letter-spacing: 1px;
            display: flex;
            align-items: center;
        }

        .navbar-brand i {
            margin-right: 10px;
        }

        .content-container {
            background-color: var(--content-bg);
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            padding: 30px;
            margin-top: 20px;
            backdrop-filter: blur(5px);
            flex-grow: 1;
        }

        .btn-custom {
            background: linear-gradient(to right, #f6d365, #fda085);
            color: white;
            border: none;
            transition: all 0.3s ease;
        }

        .btn-custom:hover {
            transform: scale(1.05);
            opacity: 0.9;
            color: white;
        }

        .nav-link {
            transition: color 0.3s ease;
            display: flex;
            align-items: center;
        }

        .nav-link i {
            margin-right: 5px;
        }

        .nav-link:hover {
            color: var(--primary-gradient-start) !important;
        }

        @media (max-width: 768px) {
            .content-container {
                padding: 15px;
                margin-top: 10px;
            }

            .navbar-toggler {
                border: none;
            }
        }
    </style>
    
    <?php echo isset($additional_styles) ? $additional_styles : ''; ?>

    <!-- Preload critical assets -->
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" as="style">
    <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.8.1/font/bootstrap-icons.min.css" as="style">
</head>
<body>
<?php if ($isLoggedIn): ?>
<nav class="navbar navbar-expand-lg navbar-dark" aria-label="Main Navigation">
    <div class="container-fluid">
        <a class="navbar-brand" href="crypto_store.php" aria-label="GoCrypto.PH Home">
            <i class="bi bi-currency-bitcoin" aria-hidden="true"></i>GoCrypto.PH
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="trade_crypto.php" aria-label="Trade Cryptocurrency">
                        <i class="bi bi-graph-up-arrow" aria-hidden="true"></i> Trade
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="account_info.php" aria-label="Account Information">
                        <i class="bi bi-person-circle" aria-hidden="true"></i> Account
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="logout.php" aria-label="Logout">
                        <i class="bi bi-box-arrow-right" aria-hidden="true"></i> Logout
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>
<?php endif; ?>
<div class="container">