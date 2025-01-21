<?php
session_start();

// Set a predefined admin passkey (in a real-world scenario, store this securely)
$ADMIN_PASSKEY = 'Password123';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $passkey = trim($_POST['passkey']);
    
    if ($passkey === $ADMIN_PASSKEY) {
        $_SESSION['admin_passkey_verified'] = true;
        header('Location: login.php');
        exit();
    } else {
        $error = 'Invalid passkey. Access denied.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Passkey Verification</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #ff4e50 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .passkey-container {
            background-color: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
        }
        .btn-passkey {
            background: linear-gradient(to right, #667eea, #764ba2);
            color: white;
            border: none;
        }
        .btn-passkey:hover {
            opacity: 0.9;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="passkey-container">
            <h2 class="text-center mb-4">Admin Access</h2>
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <form method="POST" action="admin_passkey.php">
                <div class="mb-3">
                    <label for="passkey" class="form-label">Enter Admin Passkey</label>
                    <input type="password" class="form-control" id="passkey" name="passkey" required>
                </div>
                <button type="submit" class="btn btn-passkey w-100">Verify Passkey</button>
            </form>
        </div>
    </div>
</body>
</html>