<?php
session_start();
include '../config/db.php';

// Check if admin passkey is verified
if (!isset($_SESSION['admin_passkey_verified']) || $_SESSION['admin_passkey_verified'] !== true) {
    header('Location: admin_passkey.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $contact_info = trim($_POST['contact_info']);
    $error = '';
    $success = '';

    if (empty($name) || empty($email) || empty($password) || empty($contact_info)) {
        $error = 'All fields are required.';
    } else {
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM admin WHERE email = ?");
        $stmt->execute([$email]);
        $email_exists = $stmt->fetchColumn() > 0;

        if ($email_exists) {
            $error = 'The email is already registered. Please use a different email.';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $role = 'Admin';

            $stmt = $pdo->prepare("INSERT INTO admin (name, email, password, contact_info, role) VALUES (?, ?, ?, ?, ?)");
            if ($stmt->execute([$name, $email, $hashed_password, $contact_info, $role])) {
                $success = 'Admin registration successful!';
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
    <title>Admin Registration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .register-container {
            background-color: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 450px;
        }
        .btn-register {
            background: linear-gradient(to right, #667eea, #764ba2);
            color: white;
            border: none;
        }
        .btn-register:hover {
            opacity: 0.9;
            color: white;
        }
        .footer-link {
            text-align: center;
            margin-top: 15px;
            font-size: 14px;
        }
        .footer-link a {
            color: #007bff;
            text-decoration: none;
        }
        .footer-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="register-container">
        <h2 class="text-center mb-4">Admin Registration</h2>
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php elseif (!empty($success)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <form method="POST" action="register.php">
            <div class="mb-3">
                <label for="name" class="form-label">Name</label>
                <input type="text" class="form-control" id="name" name="name" required>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <div class="mb-3">
                <label for="contact_info" class="form-label">Contact Info</label>
                <input type="text" class="form-control" id="contact_info" name="contact_info" required>
            </div>
            <button type="submit" class="btn btn-register w-100">Register</button>
        </form>
        <div class="footer-link">
            <p>Already have an account? <a href="login.php">Login here</a></p>
        </div>
    </div>
</div>
</body>
</html>
