<?php
$page_title = 'Add Crypto Wallet';
include 'header.php';
include '../config/db.php';

// User authentication check
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$error_message = '';
$success_message = '';

$wallet_types = [
    'MetaMask' => 'MetaMask (Ethereum)', 
    'Ledger Nano X' => 'Ledger Nano X (Hardware)', 
    'Trezor Model T' => 'Trezor Model T (Hardware)', 
    'Exodus' => 'Exodus (Multi-Currency)', 
    'Trust Wallet' => 'Trust Wallet (Mobile)', 
    'Coinbase Wallet' => 'Coinbase Wallet (Web/Mobile)', 
    'Electrum' => 'Electrum (Bitcoin)', 
    'Mycelium' => 'Mycelium (Mobile Bitcoin)', 
    'Samourai Wallet' => 'Samourai Wallet (Privacy-Focused)', 
    'BitPay Wallet' => 'BitPay Wallet (Multi-Signature)'
];

// Fetch user's existing wallets
try {
    $stmt = $pdo->prepare("SELECT * FROM crypto_wallets WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $existing_wallets = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Error fetching crypto wallets.";
}

// Handle wallet addition
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $wallet_type = $_POST['wallet_type'];
    $wallet_address = trim($_POST['wallet_address']);

    // Enhanced wallet address validation (supports multiple blockchain formats)
    $validation_patterns = [
        'ethereum' => '/^0x[a-fA-F0-9]{40}$/',
        'bitcoin' => '/^(bc1|[13])[a-zA-HJ-NP-Z0-9]{25,39}$/',
        'bitcoin_cash' => '/^(bitcoincash:)?(q|p)[a-z0-9]{41}$/',
        'litecoin' => '/^[LM3][a-km-zA-HJ-NP-Z1-9]{26,33}$/'
    ];

    $is_valid_address = false;
    foreach ($validation_patterns as $pattern) {
        if (preg_match($pattern, $wallet_address)) {
            $is_valid_address = true;
            break;
        }
    }

    if (!$is_valid_address) {
        $error_message = "Invalid wallet address format. Please check and try again.";
    } elseif (!array_key_exists($wallet_type, $wallet_types)) {
        $error_message = "Invalid wallet type selected.";
    } else {
        try {
            // Check for existing wallet
            $stmt = $pdo->prepare("
                SELECT id FROM crypto_wallets 
                WHERE wallet_address = ? AND user_id = ?
            ");
            $stmt->execute([$wallet_address, $user_id]);
            
            if ($stmt->fetch()) {
                $error_message = "This wallet is already registered.";
            } else {
                // Insert new wallet
                $stmt = $pdo->prepare("
                    INSERT INTO crypto_wallets 
                    (user_id, wallet_type, wallet_address) 
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([$user_id, $wallet_type, $wallet_address]);
                
                $success_message = "Wallet added successfully. It will be verified by admin.";
            }
        } catch (PDOException $e) {
            $error_message = "Error adding wallet: " . $e->getMessage();
            error_log("Wallet addition error: " . $e->getMessage());
        }
    }
}
?>

<style>
body { background: linear-gradient(135deg, #f6d365 0%, #fda085 100%); font-family: 'Inter', 'Arial', sans-serif; }
.content-container {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 2 15px;
        }
</style>

<div class="content-container">
    <h2>Add Crypto Wallet</h2>

    <?php if ($success_message): ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($success_message); ?>
        </div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="alert alert-danger">
            <?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <h5 class="card-title">Existing Crypto Wallets</h5>
            <?php if (empty($existing_wallets)): ?>
                <p>No crypto wallets added yet.</p>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Wallet Type</th>
                            <th>Wallet Address</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($existing_wallets as $wallet): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($wallet['wallet_type']); ?></td>
                                <td><?php echo htmlspecialchars($wallet['wallet_address']); ?></td>
                                <td>
                                    <?php 
                                    switch ($wallet['verification_status']) {
                                        case 'verified': 
                                            echo '<span class="badge bg-success">Verified</span>'; 
                                            break;
                                        case 'pending': 
                                            echo '<span class="badge bg-warning">Pending Verification</span>'; 
                                            break;
                                        case 'rejected':
                                            echo '<span class="badge bg-danger">Rejected</span>'; 
                                            break;
                                        default: 
                                            echo '<span class="badge bg-secondary">Unknown</span>';
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-body">
            <h5 class="card-title">Add New Crypto Wallet</h5>
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="wallet_type" class="form-label">Wallet Type</label>
                    <select name="wallet_type" id="wallet_type" class="form-select" required>
                        <option value="">Select Wallet</option>
                        <?php foreach ($wallet_types as $key => $description): ?>
                            <option value="<?php echo htmlspecialchars($key); ?>">
                                <?php echo htmlspecialchars($description); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="wallet_address" class="form-label">Wallet Address</label>
                    <input type="text" name="wallet_address" id="wallet_address" 
                           class="form-control" required placeholder="Enter Wallet Address">
                    <small class="form-text text-muted">
                        Supported: Ethereum, Bitcoin, and other major cryptocurrencies
                    </small>
                </div>
                <div class="d-flex justify-content-between">
                    <a href="account_info.php" class="btn btn-secondary">Back to Account</a>
                    <button type="submit" class="btn btn-primary">Add Crypto Wallet</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>