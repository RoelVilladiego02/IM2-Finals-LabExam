<?php
// Start the session
session_start();

// Redirect to login page if the user is not logged in or not an admin
if (!isset($_SESSION['admin_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Log out if the logout button is clicked
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: login.php');
    exit();
}

include 'header.php';
include '../config/db.php';



// Handle payment method verification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_payment_method'])) {
    $payment_method_id = $_POST['payment_method_id'];
    $action = $_POST['action'];

    try {
        if ($action === 'verify') {
            $stmt = $pdo->prepare("
                UPDATE payment_methods 
                SET 
                    is_verified = TRUE, 
                    verification_status = 'verified',
                    verified_at = CURRENT_TIMESTAMP,
                    verified_by_admin_id = :admin_id
                WHERE id = :payment_method_id
            ");
            $stmt->execute([
                ':admin_id' => $_SESSION['admin_id'],
                ':payment_method_id' => $payment_method_id
            ]);
            $success_message = "Payment method successfully verified.";
        } elseif ($action === 'reject') {
            $stmt = $pdo->prepare("
                UPDATE payment_methods 
                SET 
                    is_verified = FALSE, 
                    verification_status = 'rejected',
                    verified_at = CURRENT_TIMESTAMP,
                    verified_by_admin_id = :admin_id
                WHERE id = :payment_method_id
            ");
            $stmt->execute([
                ':admin_id' => $_SESSION['admin_id'],
                ':payment_method_id' => $payment_method_id
            ]);
            $error_message = "Payment method rejected.";
        }
    } catch (PDOException $e) {
        $error_message = "Error processing payment method: " . $e->getMessage();
    }
}

// Fetch unverified crypto wallets
$stmt = $pdo->query("
    SELECT cw.*, u.name 
    FROM crypto_wallets cw 
    JOIN users u ON cw.user_id = u.id 
    WHERE cw.is_verified = FALSE 
    ORDER BY cw.created_at
");
$unverified_wallets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle wallet verification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_wallet'])) {
    $wallet_id = $_POST['wallet_id'];
    $action = $_POST['action'];

    try {
        if ($action === 'verify') {
            $stmt = $pdo->prepare("
                UPDATE crypto_wallets 
                SET 
                    is_verified = TRUE, 
                    verification_status = 'verified',
                    verified_at = CURRENT_TIMESTAMP,
                    verified_by_admin_id = :admin_id
                WHERE id = :wallet_id
            ");
            $stmt->execute([
                ':admin_id' => $_SESSION['admin_id'], 
                ':wallet_id' => $wallet_id
            ]);
            
            // Use flash message in session
            $_SESSION['wallet_success_message'] = "Crypto wallet successfully verified.";
            
            // Redirect to prevent form resubmission
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit();
        } elseif ($action === 'reject') {
            $stmt = $pdo->prepare("
                UPDATE crypto_wallets 
                SET 
                    is_verified = FALSE, 
                    verification_status = 'rejected',
                    verified_at = CURRENT_TIMESTAMP,
                    verified_by_admin_id = :admin_id
                WHERE id = :wallet_id
            ");
            $stmt->execute([
                ':admin_id' => $_SESSION['admin_id'], 
                ':wallet_id' => $wallet_id
            ]);
            
            // Use flash message in session
            $_SESSION['wallet_error_message'] = "Crypto wallet rejected.";
            
            // Redirect to prevent form resubmission
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['wallet_error_message'] = "Error processing crypto wallet: " . $e->getMessage();
        
        // Redirect to prevent form resubmission
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Fetch crypto listings
$stmt = $pdo->query("SELECT * FROM cryptos ORDER BY current_marketprice DESC");
$cryptos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch unverified payment methods
$stmt = $pdo->query("
    SELECT pm.*, u.name 
    FROM payment_methods pm
    JOIN users u ON pm.user_id = u.id
    WHERE pm.is_verified = FALSE 
    ORDER BY pm.created_at
");
$unverified_payment_methods = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>



<div class="container-fluid mt-4">
    <!-- Existing Crypto Listings Section -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body">
                    <h2 class="card-title text-center mb-0">
                        <i class="bi bi-currency-bitcoin me-2 text-primary"></i>
                        Welcome, Crypto Admin
                    </h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Existing Crypto Table -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4>
                    <i class="bi bi-currency-exchange me-2"></i>Current Crypto Listings
                </h4>
                <a href="add_crypto.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-2"></i>Add Crypto
                </a>
            </div>
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Symbol</th>
                                    <th>Name</th>
                                    <th>Market Price</th>
                                    <th>Available Supply</th>
                                    <th>Highest Market Price</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cryptos as $crypto): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($crypto['symbol']); ?></td>
                                        <td><?php echo htmlspecialchars($crypto['name']); ?></td>
                                        <td>$<?php echo number_format($crypto['current_marketprice'], 2); ?></td>
                                        <td><?php echo $crypto['available_supply'] ? number_format($crypto['available_supply'], 0) : 'N/A'; ?></td>
                                        <td><?php echo $crypto['highest_marketprice'] ? '$' . number_format($crypto['highest_marketprice'], 2) : 'N/A'; ?></td>
                                        <td>
                                            <a href="edit_crypto.php?id=<?php echo $crypto['id']; ?>" class="btn btn-sm btn-success">
                                                <i class="bi bi-pencil-square"></i> Edit
                                            </a>
                                            <a href="delete_crypto.php?id=<?php echo $crypto['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to remove this crypto?');">
                                                <i class="bi bi-trash-fill"></i> Delete
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if (empty($cryptos)): ?>
                        <div class="text-center py-4">
                            <i class="bi bi-currency-bitcoin text-muted fs-1 mb-3"></i>
                            <h4 class="mb-3">No Cryptos Found</h4>
                            <a href="add_crypto.php" class="btn btn-primary">
                                <i class="bi bi-plus-circle me-2"></i>Restock Crypto
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Method Verification Section -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4>
                    <i class="bi bi-credit-card-2-front me-2"></i>Payment Method Verification
                </h4>
            </div>
            
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <?php if (empty($unverified_payment_methods)): ?>
                        <div class="text-center py-4">
                            <i class="bi bi-credit-card text-muted fs-1 mb-3"></i>
                            <h4 class="mb-3">No Pending Payment Methods</h4>
                            <p class="text-muted">All payment methods are currently verified.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>User</th>
                                        <th>Method Type</th>
                                        <th>Account Name</th>
                                        <th>Details</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($unverified_payment_methods as $method): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($method['name']); ?></td>
                                            <td><?php echo htmlspecialchars($method['method_type']); ?></td>
                                            <td><?php echo htmlspecialchars($method['account_name']); ?></td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#methodDetailsModal<?php echo $method['id']; ?>">
                                                    View Details
                                                </button>
                                            </td>
                                            <td>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="payment_method_id" value="<?php echo $method['id']; ?>">
                                                    <input type="hidden" name="action" value="verify">
                                                    <button type="submit" name="verify_payment_method" class="btn btn-sm btn-success me-2">
                                                        <i class="bi bi-check-circle"></i> Verify
                                                    </button>
                                                </form>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="payment_method_id" value="<?php echo $method['id']; ?>">
                                                    <input type="hidden" name="action" value="reject">
                                                    <button type="submit" name="verify_payment_method" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to reject this payment method?');">
                                                        <i class="bi bi-x-circle"></i> Reject
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>

                                        <!-- Details Modal -->
                                        <div class="modal fade" id="methodDetailsModal<?php echo $method['id']; ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Payment Method Details</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <p><strong>User:</strong> <?php echo htmlspecialchars($method['name']); ?></p>
                                                        <p><strong>Method Type:</strong> <?php echo htmlspecialchars($method['method_type']); ?></p>
                                                        <p><strong>Account Name:</strong> <?php echo htmlspecialchars($method['account_name']); ?></p>
                                                        <p><strong>Account Number:</strong> <?php echo htmlspecialchars($method['account_number']); ?></p>
                                                        <p><strong>Created At:</strong> <?php echo htmlspecialchars($method['created_at']); ?></p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>


<!-- Crypto Wallet Verification Section -->
<div class="row mt-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4>
                <i class="bi bi-wallet2 me-2"></i>Crypto Wallet Verification
            </h4>
        </div>
        
        <?php
            if (isset($_SESSION['wallet_success_message'])) {
                echo '<div class="alert alert-success">' . 
                    htmlspecialchars($_SESSION['wallet_success_message']) . 
                    '</div>';
                // Clear the message after displaying
                unset($_SESSION['wallet_success_message']);
            }

            if (isset($_SESSION['wallet_error_message'])) {
                echo '<div class="alert alert-danger">' . 
                    htmlspecialchars($_SESSION['wallet_error_message']) . 
                    '</div>';
                // Clear the message after displaying
                unset($_SESSION['wallet_error_message']);
            }
            ?>
        
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <?php if (empty($unverified_wallets)): ?>
                    <div class="text-center py-4">
                        <i class="bi bi-wallet text-muted fs-1 mb-3"></i>
                        <h4 class="mb-3">No Pending Crypto Wallets</h4>
                        <p class="text-muted">All crypto wallets are currently verified.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Username</th>
                                    <th>Wallet Type</th>
                                    <th>Wallet Address</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($unverified_wallets as $wallet): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($wallet['name']); ?></td>
                                        <td><?php echo htmlspecialchars($wallet['wallet_type']); ?></td>
                                        <td>
                                            <?php 
                                            $display_address = substr($wallet['wallet_address'], 0, 6) . 
                                                '...' . 
                                                substr($wallet['wallet_address'], -4);
                                            echo htmlspecialchars($display_address); 
                                            ?>
                                        </td>
                                        <td>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="wallet_id" value="<?php echo $wallet['id']; ?>">
                                                <input type="hidden" name="action" value="verify">
                                                <button type="submit" name="verify_wallet" class="btn btn-sm btn-success me-2">
                                                    <i class="bi bi-check-circle"></i> Verify
                                                </button>
                                            </form>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="wallet_id" value="<?php echo $wallet['id']; ?>">
                                                <input type="hidden" name="action" value="reject">
                                                <button type="submit" name="verify_wallet" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to reject this crypto wallet?');">
                                                    <i class="bi bi-x-circle"></i> Reject
                                                </button>
                                            </form>
                                            <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#walletDetailsModal<?php echo $wallet['id']; ?>">
                                                <i class="bi bi-info-circle"></i> Details
                                            </button>
                                        </td>
                                    </tr>

                                    <!-- Wallet Details Modal -->
                                    <div class="modal fade" id="walletDetailsModal<?php echo $wallet['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Crypto Wallet Details</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p><strong>Username:</strong> <?php echo htmlspecialchars($wallet['name']); ?></p>
                                                    <p><strong>Wallet Type:</strong> <?php echo htmlspecialchars($wallet['wallet_type']); ?></p>
                                                    <p><strong>Full Wallet Address:</strong> <?php echo htmlspecialchars($wallet['wallet_address']); ?></p>
                                                    <p><strong>Added At:</strong> <?php echo htmlspecialchars($wallet['created_at']); ?></p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
    body {
        background-color: #f4f6f9;
    }
    .dashboard-icon {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto;
    }
    .shadow-hover {
        transition: box-shadow 0.3s ease;
    }
    .shadow-hover:hover {
        box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important;
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>