<?php
session_start();
include '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $contact_info = trim($_POST['contact_info']);
    $error = '';
    $success = '';

    if (empty($name) || empty($email) || empty($password) || empty($confirm_password) || empty($contact_info)) {
        $error = 'All fields are required.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match. Please try again.';
    } else {
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $email_exists = $stmt->fetchColumn() > 0;

        if ($email_exists) {
            $error = 'The email is already registered. Please use a different email.';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $role = 'user'; // Always set to User for this registration form

            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, contact_info, role) VALUES (?, ?, ?, ?, ?)");
            if ($stmt->execute([$name, $email, $hashed_password, $contact_info, $role])) {
                $success = 'Registration successful! You can now log in.';
            } else {
                $error = 'Registration failed. Please try again later.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Registration - CryptoVault</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-gradient-start: #f6d365;
            --primary-gradient-end: #fda085;
        }

        body {
            background: linear-gradient(135deg, var(--primary-gradient-start) 0%, var(--primary-gradient-end) 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
        }

        .register-container {
            background-color: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 450px;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .register-container::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 100%);
            transform: rotate(-45deg);
            z-index: 1;
            pointer-events: none;
        }

        .form-label {
            font-weight: 600;
            color: #333;
        }

        .form-control {
            border: 1px solid #e1e1e1;
            padding: 12px 15px;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--primary-gradient-end);
            box-shadow: 0 0 0 0.2rem rgba(253, 160, 133, 0.25);
        }

        .btn-register {
            background: linear-gradient(135deg, var(--primary-gradient-start) 0%, var(--primary-gradient-end) 100%);
            color: white;
            border: none;
            padding: 12px;
            font-weight: 600;
            letter-spacing: 1px;
            transition: all 0.3s ease;
        }

        .btn-register:hover {
            transform: translateY(-3px);
            box-shadow: 0 7px 14px rgba(50, 50, 93, 0.1), 0 3px 6px rgba(0, 0, 0, 0.08);
        }

        .footer-link {
            text-align: center;
            margin-top: 25px;
            color: #6c757d;
        }

        .footer-link a {
            color: #007bff;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .footer-link a:hover {
            color: #0056b3;
            text-decoration: underline;
        }

        .register-logo {
            text-align: center;
            margin-bottom: 30px;
        }

        .register-logo img {
            max-height: 100px;
            filter: drop-shadow(0 4px 6px rgba(0,0,0,0.1));
        }

        @media (max-width: 480px) {
            .register-container {
                margin: 0 15px;
                padding: 30px 25px;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-logo">
            <img src="../img/logo2.png" alt="CryptoVault Logo" class="img-fluid">
        </div>
        <h2 class="text-center mb-4">User Registration</h2>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger d-flex align-items-center" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php elseif (!empty($success)): ?>
            <div class="alert alert-success d-flex align-items-center" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="register.php" id="registrationForm">
            <div class="mb-3">
                <label for="name" class="form-label">
                    <i class="fas fa-user me-2"></i>Name
                </label>
                <input type="text" class="form-control" id="name" name="name" required placeholder="Enter your full name">
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">
                    <i class="fas fa-envelope me-2"></i>Email
                </label>
                <input type="email" class="form-control" id="email" name="email" required placeholder="Enter your email address">
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">
                    <i class="fas fa-lock me-2"></i>Password
                </label>
                <input type="password" class="form-control" id="password" name="password" required placeholder="Create a strong password" minlength="8">
            </div>
            <div class="mb-3">
                <label for="confirm_password" class="form-label">
                    <i class="fas fa-lock me-2"></i>Confirm Password
                </label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required placeholder="Confirm your password" minlength="8">
                <div id="passwordMatchError" class="text-danger d-none mt-2">
                    Passwords do not match
                </div>
            </div>
            <div class="mb-3">
                <label for="contact_info" class="form-label">
                    <i class="fas fa-phone me-2"></i>Contact Info
                </label>
                <input type="text" class="form-control" id="contact_info" name="contact_info" required placeholder="Enter your contact number">
            </div>
            <button type="submit" class="btn btn-register w-100">
                Register <i class="fas fa-user-plus ms-2"></i>
            </button>
        </form>
        <div class="footer-link">
            <p>Already have an account? <a href="login.php">Login here</a></p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('registrationForm');
            const password = document.getElementById('password');
            const confirmPassword = document.getElementById('confirm_password');
            const passwordMatchError = document.getElementById('passwordMatchError');

            function validatePasswordMatch() {
                if (password.value !== confirmPassword.value) {
                    passwordMatchError.classList.remove('d-none');
                    confirmPassword.setCustomValidity('Passwords do not match');
                } else {
                    passwordMatchError.classList.add('d-none');
                    confirmPassword.setCustomValidity('');
                }
            }

            confirmPassword.addEventListener('input', validatePasswordMatch);
            password.addEventListener('input', validatePasswordMatch);

            form.addEventListener('submit', function(event) {
                if (password.value !== confirmPassword.value) {
                    event.preventDefault();
                    passwordMatchError.classList.remove('d-none');
                    confirmPassword.focus();
                }
            });
        });
    </script>
</body>
</html>