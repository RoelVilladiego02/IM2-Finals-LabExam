<?php
// Footer content
$current_year = date('Y');
$site_name = 'GoCrypto.PH';
$social_links = [
    'twitter' => 'https://twitter.com/gocryptoph',
    'facebook' => 'https://facebook.com/gocryptoph',
    'instagram' => 'https://instagram.com/gocryptoph',
    'linkedin' => 'https://linkedin.com/company/gocryptoph'
];

// Quick links with icons and URLs
$quick_links = [
    ['url' => 'crypto_store.php', 'icon' => 'house-door-fill', 'title' => 'Home'],
    ['url' => 'trade_crypto.php', 'icon' => 'currency-bitcoin', 'title' => 'Trade Crypto'],
    ['url' => 'account_info.php', 'icon' => 'person-circle', 'title' => 'My Account'],
    ['url' => 'transactions.php', 'icon' => 'file-earmark-richtext', 'title' => 'View Transactions'],
    ['url' => 'feedback.php', 'icon' => 'chat-left-text', 'title' => 'View Feedbacks']
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Bootstrap and custom styles -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary-background: #0a0a1a;
            --secondary-background: #121228;
            --accent-color: #ffd700;
            --text-primary: #ffffff;
            --text-secondary: #b0b0b0;
            --transition-speed: 0.3s;
        }

        body {
            background-color: var(--secondary-background);
        }

        .footer {
        background: linear-gradient(135deg, var(--primary-background), var(--secondary-background));
        color: var(--text-primary);
        padding: 4rem 0;
        border-top: 2px solid var(--accent-color);
        box-shadow: 0 -15px 30px rgba(0, 0, 0, 0.3);
        width: 200%; 
        margin-left: -50%; 
        position: relative;
        overflow: hidden;
    }

        .footer::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(
                circle at center, 
                rgba(255, 215, 0, 0.1) 0%, 
                transparent 70%
            );
            z-index: 1;
        }

        .footer-content {
            position: relative;
            z-index: 2;
        }

        .footer-logo {
            display: flex;
            align-items: center;
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 1.5rem;
            color: var(--accent-color);
            text-shadow: 0 0 10px rgba(255, 215, 0, 0.3);
        }

        .footer-logo i {
            margin-right: 0.75rem;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        .footer a {
            color: var(--text-primary);
            text-decoration: none;
            transition: all var(--transition-speed) ease;
        }

        .footer a:hover {
            color: var(--accent-color);
            transform: translateY(-3px);
        }

        .footer-social-icons {
            display: flex;
            gap: 1.5rem;
            margin-top: 1.5rem;
        }

        .footer-social-icons a {
            font-size: 2rem;
            opacity: 0.7;
            transition: all var(--transition-speed) ease;
        }

        .footer-social-icons a:hover {
            opacity: 1;
            transform: scale(1.2) translateY(-5px);
        }

        .footer-quick-links {
            list-style: none;
            padding: 0;
        }

        .footer-quick-links li {
            margin-bottom: 1rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            transition: background var(--transition-speed) ease;
        }

        .footer-quick-links li:hover {
            background: rgba(255, 215, 0, 0.1);
        }

        .footer-quick-links li a {
            display: flex;
            align-items: center;
            padding: 0.5rem 1rem;
            color: var(--text-primary);
        }

        .footer-quick-links li a i {
            margin-right: 1rem;
            color: var(--accent-color);
            transition: transform var(--transition-speed) ease;
        }

        .footer-quick-links li a:hover i {
            transform: rotate(15deg) scale(1.2);
        }

        .footer-divider {
            border-color: rgba(255, 215, 0, 0.3);
            margin: 2.5rem 0;
        }

        .footer-bottom {
            text-align: center;
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .footer {
                text-align: center;
            }

            .footer-social-icons, 
            .footer-quick-links {
                display: flex;
                flex-direction: column;
                align-items: center;
            }
        }
    </style>
</head>
<body>
    <footer class="footer mt-auto py-3">
        <div class="container footer-content">
            <div class="row">
                <div class="col-md-4">
                    <div class="footer-logo">
                        <i class="bi bi-currency-bitcoin"></i>
                        <?php echo htmlspecialchars($site_name); ?>
                    </div>
                    <p class="text-muted">
                        Empowering your crypto journey with secure and innovative trading solutions.
                    </p>
                    <div class="footer-social-icons">
                        <?php foreach($social_links as $platform => $link): ?>
                            <a href="<?php echo htmlspecialchars($link); ?>" target="_blank" title="<?php echo ucfirst($platform); ?>">
                                <i class="bi bi-<?php echo $platform; ?>"></i>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="col-md-4">
                    <h5 class="mb-3">Quick Links</h5>
                    <ul class="footer-quick-links">
                        <?php foreach($quick_links as $link): ?>
                            <li>
                                <a href="<?php echo htmlspecialchars($link['url']); ?>">
                                    <i class="bi bi-<?php echo htmlspecialchars($link['icon']); ?>"></i>
                                    <?php echo htmlspecialchars($link['title']); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                        <?php if(isset($_SESSION['user_id'])): ?>
                            <li>
                                <a href="logout.php">
                                    <i class="bi bi-box-arrow-right"></i>Logout
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5 class="mb-3">Contact Us</h5>
                    <p class="text-muted mb-2">
                        <i class="bi bi-envelope me-2"></i> 
                        <a href="mailto:support@gocrypto.ph">support@gocrypto.ph</a>
                    </p>
                    <p class="text-muted mb-2">
                        <i class="bi bi-telephone me-2"></i> 
                        <a href="tel:+15551234567">(555) 123-4567</a>
                    </p>
                    <p class="text-muted mb-3">
                        <i class="bi bi-geo-alt me-2"></i>
                        123 Crypto Street, Tech City, Digital State
                    </p>
                </div>
            </div>
            
            <hr class="footer-divider">
            
            <div class="footer-bottom">
                <p class="mb-1">
                    &copy; <?php echo $current_year; ?> <?php echo htmlspecialchars($site_name); ?>. 
                    All Rights Reserved.
                </p>
                <small>
                    Powered by REV Technologies
                </small>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
</body>
</html>