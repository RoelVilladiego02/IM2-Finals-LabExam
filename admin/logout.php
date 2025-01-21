<?php
session_start();

// Check if the user is logged in, redirect to login if not
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Handle logout form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Unset all session variables
    $_SESSION = array();

    // Destroy the session
    session_destroy();

    // Redirect to the login page
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logout Confirmation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script>
        // Show confirmation dialog
        function confirmLogout() {
            return confirm("Are you sure you want to log out?");
        }

        // Trigger logout form submission if confirmed
        function handleLogout() {
            if (confirmLogout()) {
                document.getElementById('logoutForm').submit();
            } else {
                // Redirect to the dashboard if user cancels logout
                window.location.href = 'dashboard.php'; // Change 'dashboard.php' to your actual dashboard page
            }
        }
    </script>
</head>
<body onload="handleLogout()">
    <div class="container">
        <form id="logoutForm" method="POST" action="">
            <!-- This form will submit if the user confirms -->
        </form>
    </div>
</body>
</html>