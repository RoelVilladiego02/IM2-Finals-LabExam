<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['admin_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Initialize variables
$error = '';
$success = '';
$crypto = null;

// Check if a crypto ID is provided for editing
if (isset($_GET['id'])) {
    $crypto_id = $_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM cryptos WHERE id = ?");
    $stmt->execute([$crypto_id]);
    $crypto = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$crypto) {
        $error = "Cryptocurrency not found.";
    }
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $crypto_id = $_POST['crypto_id'];
    $symbol = strtoupper(trim($_POST['symbol']));
    $name = trim($_POST['name']);
    $current_marketprice = floatval($_POST['current_marketprice']);
    $available_supply = $_POST['available_supply'] ?: null;
    $highest_marketprice = $_POST['highest_marketprice'] ?: null;

    try {
        // Update crypto in the database
        $stmt = $pdo->prepare("
            UPDATE cryptos 
            SET symbol = ?, name = ?, current_marketprice = ?, available_supply = ?, highest_marketprice = ?
            WHERE id = ?
        ");
        $stmt->execute([$symbol, $name, $current_marketprice, $available_supply, $highest_marketprice, $crypto_id]);

        $success = "Cryptocurrency updated successfully!";
        // Refresh crypto data after update
        $stmt = $pdo->prepare("SELECT * FROM cryptos WHERE id = ?");
        $stmt->execute([$crypto_id]);
        $crypto = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        if ($e->getCode() == 23000 && strpos($e->getMessage(), 'Duplicate entry') !== false) {
            $error = "The symbol '{$symbol}' is already in use. Please choose a unique symbol.";
        } else {
            $error = "An unexpected error occurred: " . htmlspecialchars($e->getMessage());
        }
    }
}

// Fetch all cryptos for the dropdown
$stmt = $pdo->query("SELECT id, name FROM cryptos");
$all_cryptos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include 'header.php'; ?>

<div class="container-fluid my-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow-lg border-0 rounded-lg">
                <div class="card-header bg-gradient-primary text-white text-center py-4">
                    <h2 class="mb-0">
                        <i class="bi bi-pencil-square me-2"></i>Edit Cryptocurrency
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

                    <form action="edit_crypto.php" method="POST" class="needs-validation" novalidate>
                        <div class="mb-3">
                            <label for="crypto_select" class="form-label">
                                <i class="bi bi-search me-2 text-primary"></i>Select Cryptocurrency to Edit
                            </label>
                            <select class="form-select" id="crypto_select" onchange="loadCryptoDetails(this.value)">
                                <option value="" disabled selected>Choose a cryptocurrency</option>
                                <?php foreach ($all_cryptos as $crypto_item): ?>
                                    <option value="<?php echo htmlspecialchars($crypto_item['id']); ?>">
                                        <?php echo htmlspecialchars($crypto_item['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div id="crypto_edit_section" <?php echo $crypto ? '' : 'style="display:none;"'; ?>>
                            <input type="hidden" name="crypto_id" id="crypto_id" value="<?php echo $crypto ? htmlspecialchars($crypto['id']) : ''; ?>">
                            
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="symbol" class="form-label">
                                        <i class="bi bi-tags me-2 text-primary"></i>Crypto Symbol
                                    </label>
                                    <input type="text" class="form-control" id="symbol" name="symbol" 
                                           value="<?php echo $crypto ? htmlspecialchars($crypto['symbol']) : ''; ?>" required>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="name" class="form-label">
                                        <i class="bi bi-currency-exchange me-2 text-primary"></i>Cryptocurrency Name
                                    </label>
                                    <input type="text" class="form-control" id="name" name="name" 
                                           value="<?php echo $crypto ? htmlspecialchars($crypto['name']) : ''; ?>" required>
                                </div>
                                
                                <div class="col-md-4">
                                    <label for="current_marketprice" class="form-label">
                                        <i class="bi bi-graph-up me-2 text-primary"></i>Market Price
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="number" step="0.00000001" class="form-control" id="current_marketprice" name="current_marketprice" 
                                               value="<?php echo $crypto ? htmlspecialchars($crypto['current_marketprice']) : ''; ?>" required>
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <label for="available_supply" class="form-label">
                                        <i class="bi bi-stack me-2 text-primary"></i>Available Supply
                                    </label>
                                    <input type="number" step="0.00000001" class="form-control" id="available_supply" name="available_supply" 
                                           value="<?php echo $crypto ? htmlspecialchars($crypto['available_supply']) : ''; ?>">
                                </div>
                                
                                <div class="col-md-4">
                                    <label for="highest_marketprice" class="form-label">
                                        <i class="bi bi-infinity me-2 text-primary"></i>Highest Market Price
                                    </label>
                                    <input type="number" step="0.00000001" class="form-control" id="highest_marketprice" name="highest_marketprice" 
                                           value="<?php echo $crypto ? htmlspecialchars($crypto['highest_marketprice']) : ''; ?>">
                                </div>
                                
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary w-100 py-2">
                                        <i class="bi bi-save me-2"></i>Update Cryptocurrency
                                    </button>
                                </div>
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
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
    }
    .card {
        border: none;
        border-radius: 15px;
        overflow: hidden;
    }
</style>

<script>
    function loadCryptoDetails(cryptoId) {
        window.location.href = 'edit_crypto.php?id=' + cryptoId;
    }

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
