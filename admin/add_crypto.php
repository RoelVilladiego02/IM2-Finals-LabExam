<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['admin_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Initialize variables to avoid undefined variable warnings
$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $symbol = strtoupper($_POST['symbol']);
    $name = $_POST['name'];
    $available_supply = $_POST['available_supply'] ?: null;
    $current_marketprice = $_POST['current_marketprice'];
    $highest_marketprice = $_POST['highest_marketprice'] ?: null;

    // Check if the crypto symbol already exists to avoid duplicates
    $stmt = $pdo->prepare("SELECT * FROM cryptos WHERE symbol = ?");
    $stmt->execute([$symbol]);
    if ($stmt->rowCount() > 0) {
        $error = "Crypto symbol already exists. Please use a unique symbol.";
    } else {
        // Insert crypto into database
        $stmt = $pdo->prepare("INSERT INTO cryptos (symbol, name, available_supply, current_marketprice, highest_marketprice) VALUES (?, ?, ?, ?, ?)");
        if ($stmt->execute([$symbol, $name, $available_supply, $current_marketprice, $highest_marketprice])) {
            $success = "Cryptocurrency added successfully!";
        } else {
            $error = "Failed to add cryptocurrency. Please try again.";
        }
    }
}
?>

<?php include 'header.php'; ?>

<div class="container-fluid my-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow-lg border-0 rounded-lg">
                <div class="card-header bg-gradient-primary text-white text-center py-4">
                    <h2 class="mb-0">
                        <i class="bi bi-currency-bitcoin me-2"></i>Add New Cryptocurrency
                    </h2>
                </div>
                <div class="card-body p-4">
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <?php echo htmlspecialchars($error); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php elseif ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="bi bi-check-circle-fill me-2"></i>
                            <?php echo htmlspecialchars($success); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form action="add_crypto.php" method="POST" class="needs-validation" novalidate>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="symbol" class="form-label">
                                    <i class="bi bi-tags me-2 text-primary"></i>Crypto Symbol
                                </label>
                                <input type="text" class="form-control" id="symbol" name="symbol" required>
                                <div class="invalid-feedback">Please enter a cryptocurrency symbol (e.g., BTC, ETH).</div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="name" class="form-label">
                                    <i class="bi bi-currency-exchange me-2 text-primary"></i>Cryptocurrency Name
                                </label>
                                <input type="text" class="form-control" id="name" name="name" required>
                                <div class="invalid-feedback">Please enter the cryptocurrency name.</div>
                            </div>
                            
                            <div class="col-md-4">
                                <label for="current_marketprice" class="form-label">
                                    <i class="bi bi-graph-up me-2 text-primary"></i>Current Market Price
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" step="0.00000001" class="form-control" id="current_marketprice" name="current_marketprice" required>
                                    <div class="invalid-feedback">Please enter the current market price.</div>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <label for="available_supply" class="form-label">
                                    <i class="bi bi-stack me-2 text-primary"></i>Available Supply
                                </label>
                                <input type="number" step="0.00000001" class="form-control" id="available_supply" name="available_supply">
                                <div class="invalid-feedback">Enter the available supply (optional).</div>
                            </div>
                            
                            <div class="col-md-4">
                                <label for="highest_marketprice" class="form-label">
                                    <i class="bi bi-bar-chart-line me-2 text-primary"></i>Highest Market Price
                                </label>
                                <input type="number" step="0.00000001" class="form-control" id="highest_marketprice" name="highest_marketprice">
                                <div class="invalid-feedback">Enter the highest market price (optional).</div>
                            </div>
                            
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary w-100 py-2">
                                    <i class="bi bi-plus-circle me-2"></i>Add Cryptocurrency
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    body {
        background-color: #f4f6f9;
    }
    .bg-gradient-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%)!important;
    }
    .card {
        border: none;
        border-radius: 15px;
        overflow: hidden;
    }
    .card-header {
        font-weight: bold;
    }
    .form-label {
        font-weight: 500;
    }
</style>

<script>
    // Add additional custom validation
    (function() {
        'use strict';
        window.addEventListener('load', function() {
            var forms = document.getElementsByClassName('needs-validation');
            var validation = Array.prototype.filter.call(forms, function(form) {
                form.addEventListener('submit', function(event) {
                    if (form.checkValidity() === false) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        }, false);
    })();
</script>