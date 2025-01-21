<?php
$page_title = 'Account Information';
include 'header.php';
include '../config/db.php';

// User authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Variables
$user_id = $_SESSION['user_id'];
$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_payment_method'])) {
    try {
        $payment_method_id = $_POST['payment_method_id'];

        // Check if there are any related transactions
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM trade_transactions WHERE payment_method_id = ?");
        $stmt->execute([$payment_method_id]);
        $transaction_count = $stmt->fetchColumn();

        if ($transaction_count > 0) {
            throw new Exception("Cannot remove payment method. There are existing transactions associated with this payment method. View transactions first.");
        }

        // Remove the payment method from the database
        $stmt = $pdo->prepare("DELETE FROM payment_methods WHERE id = ? AND user_id = ?");
        $stmt->execute([$payment_method_id, $user_id]);

        $success_message = 'Payment method removed successfully.';
    } catch (Exception $e) {
        $error_message = 'Error removing payment method: ' . $e->getMessage();
    }
}

// Handle wallet removal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_wallet'])) {
    try {
        $wallet_id = $_POST['wallet_id'];

        // First, check if there are any related transactions
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM trade_transactions WHERE wallet_id = ?");
        $stmt->execute([$wallet_id]);
        $transaction_count = $stmt->fetchColumn();

        if ($transaction_count > 0) {
            throw new Exception("Cannot remove wallet. There are existing transactions associated with this wallet. View transactions first.");
        }

        // Remove associated holdings
        $stmt = $pdo->prepare("DELETE FROM user_crypto_holdings WHERE wallet_id = ?");
        $stmt->execute([$wallet_id]);

        // Then remove the wallet
        $stmt = $pdo->prepare("DELETE FROM crypto_wallets WHERE id = ? AND user_id = ?");
        $stmt->execute([$wallet_id, $user_id]);

        $success_message = 'Crypto wallet removed successfully.';
    } catch (Exception $e) {
        $error_message = 'Error removing wallet: ' . $e->getMessage();
    }
}


// Update account information
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_info'])) {
    try {
        $name = $_POST['name'];
        $contact_info = $_POST['contact_info'];

        if (empty($name)) {
            throw new Exception('Name is required.');
        }

        $stmt = $pdo->prepare("UPDATE users SET name = ?, contact_info = ? WHERE id = ?");
        $stmt->execute([$name, $contact_info, $user_id]);

        $success_message = 'Account information updated successfully.';
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Fetch user information
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception("User not found.");
    }

    // Fetch payment methods
    $stmt = $pdo->prepare("SELECT * FROM payment_methods WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $payment_methods = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch crypto wallets and holdings
    $stmt = $pdo->prepare("
        SELECT 
            cw.id, cw.wallet_type, cw.wallet_address, cw.is_verified, cw.verification_status,
            c.symbol, c.name, COALESCE(uch.quantity, 0) AS quantity, c.id AS crypto_id
        FROM crypto_wallets cw
        LEFT JOIN user_crypto_holdings uch ON cw.id = uch.wallet_id
        LEFT JOIN cryptos c ON uch.crypto_id = c.id
        WHERE cw.user_id = ?
    ");
    $stmt->execute([$user_id]);
    $wallet_holdings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Organize holdings by wallet
    $crypto_wallets = [];
    foreach ($wallet_holdings as $holding) {
        $wallet_id = $holding['id'];
        if (!isset($crypto_wallets[$wallet_id])) {
            $crypto_wallets[$wallet_id] = [
                'wallet_id' => $wallet_id,
                'wallet_type' => $holding['wallet_type'],
                'wallet_address' => $holding['wallet_address'],
                'is_verified' => $holding['is_verified'],
                'verification_status' => $holding['verification_status'],
                'holdings' => []
            ];
        }
        if ($holding['symbol'] && $holding['quantity'] > 0) {
            $crypto_wallets[$wallet_id]['holdings'][] = [
                'symbol' => $holding['symbol'],
                'name' => $holding['name'],
                'quantity' => $holding['quantity'],
                'crypto_id' => $holding['crypto_id']
            ];
        }
    }
} catch (PDOException $e) {
    $error_message = "Error fetching account information.";
}
?>

<!-- Styles -->
<style>
    body { background: linear-gradient(135deg, #f6d365 0%, #fda085 100%); font-family: 'Inter', 'Arial', sans-serif; }
    .content-container { max-width: 2000px; margin: 0 auto; padding: 2 15px; overflow: hidden; margin: 2rem auto; }
    .card-hover-effect { transition: all 0.3s ease; transform-origin: center; }
    .card-hover-effect:hover { transform: translateY(-10px); box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1); }
    .crypto-wallet-card { border-left: 4px solid; }
    .crypto-wallet-card.verified { border-left-color: #28a745; }
    .crypto-wallet-card.pending { border-left-color: #ffc107; }
    .card-title { font-size: 1.25rem; font-weight: bold; margin-bottom: 1rem; }
    .table-hover tbody tr:hover { background-color: #f8f9fa; }
    .mb-4 { margin-bottom: 1rem !important; }
    .g-4 > * { margin-bottom: 1.5rem; }
    .modal-backdrop { z-index: 1040 !important; }
    .modal-open {
    overflow: auto !important; /* Allow scrolling */
    padding-right: 0 !important; /* Avoid scrollbar shift */
}

.modal-dialog {
    max-height: 90vh; /* Keep the modal within the viewport */
    overflow-y: auto; /* Make the modal scrollable if content overflows */
}

</style>

<div class="content-container">
    <div class="row">
        <div class="col-12">
            <h2 class="mb-4">
                <i class="bi bi-person-circle me-2"></i>Account Information
            </h2>
        </div>
    </div>

    <?php if ($success_message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($success_message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($error_message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Personal Information -->
        <div class="col-md-6">
            <div class="card card-hover-effect shadow-sm">
                <div class="card-body">
                    <h5 class="card-title d-flex align-items-center">
                        <i class="bi bi-person-badge me-2"></i>Personal Information
                    </h5>
                    <form method="POST" action="">
                        <input type="hidden" name="update_info" value="1">
                        <div class="mb-3">
                            <label for="name" class="form-label">
                                <i class="bi bi-person me-1"></i>Full Name
                            </label>
                            <input type="text" class="form-control" id="name" name="name" 
                                value="<?php echo htmlspecialchars($user['name']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">
                                <i class="bi bi-envelope me-1"></i>Email Address
                            </label>
                            <input type="email" class="form-control" id="email" 
                                value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="contact_info" class="form-label">
                                <i class="bi bi-telephone me-1"></i>Contact Information
                            </label>
                            <input type="text" class="form-control" id="contact_info" name="contact_info" 
                                value="<?php echo htmlspecialchars($user['contact_info'] ?? ''); ?>" 
                                placeholder="Phone number or alternative contact">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="bi bi-calendar-check me-1"></i>Registered Since
                            </label>
                            <input type="text" class="form-control" 
                                value="<?php echo date('F d, Y', strtotime($user['created_at'])); ?>" readonly>
                        </div>
                        <button type="submit" class="btn btn-primary" id="update-btn" disabled>
                            <i class="bi bi-save me-1"></i>Update Information
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Payment Methods -->
        <div class="col-md-6">
            <div class="card card-hover-effect shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="card-title d-flex align-items-center">
                        <i class="bi bi-credit-card me-2"></i>Payment Methods
                    </h5>
                    
                    <?php if (empty($payment_methods)): ?>
                        <div class="alert alert-info d-flex align-items-center">
                            <i class="bi bi-info-circle me-2"></i>
                            <div>
                                No payment methods added yet.
                            </div>
                        </div>
                        <a href="add_payment_method.php" class="btn btn-outline-primary">
                            <i class="bi bi-plus-circle me-1"></i>Add Payment Method
                        </a>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Method</th>
                                        <th>Account Name</th>
                                        <th>Status</th>
                                        <th>Balance</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($payment_methods as $method): ?>
                                        <tr>
                                            <td><?php echo ucfirst(htmlspecialchars($method['method_type'])); ?></td>
                                            <td><?php echo htmlspecialchars($method['account_name']); ?></td>
                                            <td>
                                                <?php echo $method['is_verified'] ? 
                                                    '<span class="badge bg-success">Verified</span>' : 
                                                    '<span class="badge bg-warning">Pending</span>'; 
                                                ?>
                                            </td>
                                            <td>₱<?php echo number_format($method['balance'], 2); ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-danger remove-payment-method" 
                                                        data-payment-method-id="<?php echo $method['id']; ?>"
                                                        data-method-type="<?php echo htmlspecialchars($method['method_type']); ?>">
                                                    <i class="bi bi-trash me-1"></i>Remove
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <a href="add_payment_method.php" class="btn btn-outline-primary">
                            <i class="bi bi-plus-circle me-1"></i>Add Another Payment Method
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Crypto Wallets -->
        <div class="card card-hover-effect shadow-sm">
                <div class="card-body">
                    <h5 class="card-title d-flex align-items-center">
                        <i class="bi bi-wallet2 me-2"></i>Crypto Wallets
                    </h5>
                    
                    <?php if (empty($crypto_wallets)): ?>
                        <div class="alert alert-info d-flex align-items-center">
                            <i class="bi bi-info-circle me-2"></i>
                            <div>
                                No crypto wallets added yet.
                            </div>
                        </div>
                        <a href="add_wallet.php" class="btn btn-outline-primary">
                            <i class="bi bi-plus-circle me-1"></i>Add Crypto Wallet
                        </a>
                    <?php else: ?>
                        <?php foreach ($crypto_wallets as $wallet): ?>
                            <div class="card mb-3 crypto-wallet-card <?php echo $wallet['is_verified'] ? 'verified' : 'pending'; ?>">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <span>
                                        <i class="bi bi-shield-fill-check me-2"></i>
                                        <?php echo htmlspecialchars($wallet['wallet_type']); ?> Wallet
                                    </span>
                                    <div>
                                        <span class="badge <?php echo $wallet['is_verified'] ? 'bg-success' : 'bg-warning'; ?> me-2">
                                            <?php echo $wallet['verification_status']; ?>
                                        </span>
                                        <button class="btn btn-sm btn-danger remove-wallet" 
                                            data-wallet-id="<?php echo $wallet['wallet_id']; ?>"
                                            data-wallet-type="<?php echo htmlspecialchars($wallet['wallet_type']); ?>">
                                            <i class="bi bi-trash me-1"></i>Remove
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <p>
                                        <strong>Address:</strong>
                                        <?php
                                        $display_address = substr($wallet['wallet_address'], 0, 6) .
                                            '...' .
                                            substr($wallet['wallet_address'], -4);
                                        echo htmlspecialchars($display_address);
                                        ?>
                                    </p>
                                    <?php if (empty($wallet['holdings'])): ?>
                                        <p class="text-muted">No crypto holdings in this wallet.</p>
                                    <?php else: ?>
                                        <h6 class="mt-3">Crypto Holdings</h6>
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Crypto</th>
                                                        <th>Symbol</th>
                                                        <th>Quantity</th>
                                                        <th>Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($wallet['holdings'] as $holding): ?>
                                                        <tr>
                                                        <td><?php echo htmlspecialchars($holding['name']); ?></td>
                                                        <td><?php echo htmlspecialchars($holding['symbol']); ?></td>
                                                        <td><?php echo number_format($holding['quantity'], 8); ?></td>
                                                        <td>
                                                            <a href="trade_crypto.php?id=<?php echo $holding['crypto_id']; ?>&trade_type=sell" class="btn btn-sm btn-primary">
                                                                <i class="bi bi-cash-coin me-1"></i>Sell
                                                            </a>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <a href="add_wallet.php" class="btn btn-outline-primary">
                            <i class="bi bi-plus-circle me-1"></i>Add Another Crypto Wallet
                        </a>
                    <?php endif; ?>
                </div>
            </div>

        <div class="card card-hover-effect shadow-sm mt-4">
            <div class="card-body">
                <h5 class="card-title d-flex align-items-center">
                    <i class="bi bi-file-text me-2"></i>Check Transactions
                </h5>
                <p class="text-muted">
                    View your past transactions, including trade history, payment updates, and more.
                </p>
                <a href="transactions.php" class="btn btn-outline-primary">
                    <i class="bi bi-arrow-right-circle me-1"></i>Go to Transactions
                </a>
            </div>
        </div>
        
        <div class="modal fade" id="removeWalletModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="false">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="bi bi-exclamation-triangle-fill text-warning me-2"></i> Confirm Wallet Removal
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        Are you sure you want to remove the <strong><span id="walletTypeText"></span></strong> wallet?
                        This action cannot be undone and will permanently delete all associated holdings.
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <form method="POST" id="removeWalletForm">
                            <input type="hidden" name="remove_wallet" value="1">
                            <input type="hidden" name="wallet_id" id="walletIdInput">
                            <button type="submit" class="btn btn-danger">Yes, Remove Wallet</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="removePaymentMethodModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="false">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="bi bi-exclamation-triangle-fill text-warning me-2"></i> Confirm Payment Method Removal
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        Are you sure you want to remove the <strong><span id="methodTypeText"></span></strong> payment method?
                        This action cannot be undone.
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <form method="POST" id="removePaymentMethodForm">
                            <input type="hidden" name="remove_payment_method" value="1">
                            <input type="hidden" name="payment_method_id" id="paymentMethodIdInput">
                            <button type="submit" class="btn btn-danger">Yes, Remove</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>                                            

    </div>
</div>



<script>
document.addEventListener('DOMContentLoaded', function () {
    const formFields = document.querySelectorAll('#name, #contact_info');
    const updateButton = document.getElementById('update-btn');

    // Enable button if any input is changed
    formFields.forEach(field => {
        field.addEventListener('input', () => {
            updateButton.disabled = false;
        });
    });
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const viewBalanceButtons = document.querySelectorAll('.view-balance');
    const balanceModalBody = document.getElementById('balanceModalBody');
    const balanceModal = new bootstrap.Modal(document.getElementById('balanceModal'));

    viewBalanceButtons.forEach(button => {
        button.addEventListener('click', function() {
            const name = this.getAttribute('data-name');
            const symbol = this.getAttribute('data-symbol');
            const quantity = parseFloat(this.getAttribute('data-quantity')).toFixed(8);

            balanceModalBody.innerHTML = 
                `<h4 class="mb-3">${name} (${symbol})</h4>
                <div class="alert alert-info">
                    <strong>Current Holdings:</strong> ${quantity} ${symbol}
                </div>`;

            balanceModal.show();
        });
    });
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const removeWalletButtons = document.querySelectorAll('.remove-wallet');
    const removeWalletModal = new bootstrap.Modal(document.getElementById('removeWalletModal'));
    const walletTypeText = document.getElementById('walletTypeText');
    const walletIdInput = document.getElementById('walletIdInput');

    removeWalletButtons.forEach(button => {
        button.addEventListener('click', function () {
            const walletId = this.getAttribute('data-wallet-id');
            const walletType = this.getAttribute('data-wallet-type');

            // Set modal content
            walletTypeText.textContent = walletType;
            walletIdInput.value = walletId;

            // Show the modal
            removeWalletModal.show();
        });
    });

    // Fix scrolling issues when the modal opens or closes
    document.getElementById('removeWalletModal').addEventListener('hidden.bs.modal', function () {
        document.body.classList.remove('modal-open');
    });
});

</script>

<script>

document.addEventListener('DOMContentLoaded', function () {
    const removeButtons = document.querySelectorAll('.remove-payment-method');
    const modal = new bootstrap.Modal(document.getElementById('removePaymentMethodModal'));
    const paymentMethodIdInput = document.getElementById('paymentMethodIdInput');
    const methodTypeText = document.getElementById('methodTypeText');

    removeButtons.forEach(button => {
        button.addEventListener('click', function () {
            const paymentMethodId = this.dataset.paymentMethodId;
            const methodType = this.dataset.methodType;

            // Set modal content
            paymentMethodIdInput.value = paymentMethodId;
            methodTypeText.textContent = methodType.charAt(0).toUpperCase() + methodType.slice(1);

            // Show modal
            modal.show();
        });
    });
});

</script>



<?php include 'footer.php'; ?>