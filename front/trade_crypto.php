<?php
$page_title = 'Trade Crypto';
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
$trade_success = false;
$pre_selected_crypto = null;
$pre_selected_trade_type = isset($_GET['trade_type']) && $_GET['trade_type'] === 'sell' ? 'sell' : 'buy';
$pre_selected_wallet_id = isset($_GET['wallet_id']) ? intval($_GET['wallet_id']) : null;
$conversion_rate = 56.00; // PHP conversion rate

// Fetch user's verified crypto wallets
try {
    $stmt = $pdo->prepare("
        SELECT * FROM crypto_wallets 
        WHERE user_id = ? AND is_verified = TRUE
    ");
    $stmt->execute([$user_id]);
    $crypto_wallets = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($crypto_wallets)) {
        $error_message = "Please add and verify a crypto wallet before trading.";
    }
} catch (PDOException $e) {
    $error_message = "Error checking crypto wallets: " . $e->getMessage();
}

// Check if user has verified payment methods
try {
    $stmt = $pdo->prepare("
        SELECT * FROM payment_methods 
        WHERE user_id = ? AND is_verified = TRUE
    ");
    $stmt->execute([$user_id]);
    $payment_methods = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($payment_methods)) {
        $error_message = "Please add and verify a payment method before trading.";
    }
} catch (PDOException $e) {
    $error_message = "Error checking payment methods: " . $e->getMessage();
}

// Fetch all available cryptos
try {
    $stmt = $pdo->prepare("SELECT * FROM cryptos ORDER BY name");
    $stmt->execute();
    $available_cryptos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Error fetching cryptocurrencies: " . $e->getMessage();
}

// Check for pre-selected crypto from URL
if (isset($_GET['id'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM cryptos WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $pre_selected_crypto = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error_message = "Error fetching selected cryptocurrency: " . $e->getMessage();
    }
}

// Handle trade submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error_message)) {
    try {
        $crypto_id = $_POST['crypto_id'];
        $payment_method_id = $_POST['payment_method'];
        $wallet_id = $_POST['wallet_id'];
        $trade_type = $_POST['trade_type'];
        $trade_amount = floatval($_POST['trade_amount']);

        $pdo->beginTransaction();

        // Fetch crypto details
        $stmt = $pdo->prepare("SELECT * FROM cryptos WHERE id = ?");
        $stmt->execute([$crypto_id]);
        $crypto_data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$crypto_data) {
            throw new Exception("Cryptocurrency not found.");
        }

        // Fetch selected payment method
        $stmt = $pdo->prepare("SELECT * FROM payment_methods WHERE id = ? AND user_id = ? AND is_verified = TRUE");
        $stmt->execute([$payment_method_id, $user_id]);
        $selected_method = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$selected_method) {
            throw new Exception("Invalid or unverified payment method.");
        }

        // Fetch wallet
        $stmt = $pdo->prepare("
            SELECT 
                cw.id AS wallet_id,
                COALESCE(uch.quantity, 0) AS wallet_crypto_balance
            FROM crypto_wallets cw
            LEFT JOIN user_crypto_holdings uch ON uch.wallet_id = cw.id AND uch.crypto_id = ?
            WHERE cw.id = ? AND cw.user_id = ? AND cw.is_verified = TRUE
        ");

        $stmt->execute([$crypto_id, $wallet_id, $user_id]);
        $selected_wallet = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$selected_wallet) {
            throw new Exception("Invalid wallet selected.");
        }

        // Generate a unique trade transaction ID (UUID)
        $trade_transaction_id = bin2hex(random_bytes(16));

        if ($trade_type === 'buy') {
            $total_cost = $crypto_data['current_marketprice'] * $trade_amount;
            $total_cost_php = $total_cost * $conversion_rate;

            if ($total_cost_php > $selected_method['balance']) {
                throw new Exception("Insufficient funds in the payment method.");
            }

            if ($trade_amount > $crypto_data['available_supply']) {
                throw new Exception("Insufficient supply of {$crypto_data['name']}.");
            }

            // Update crypto supply
            $stmt = $pdo->prepare("UPDATE cryptos SET available_supply = available_supply - ? WHERE id = ?");
            $stmt->execute([$trade_amount, $crypto_id]);

            // Update payment method balance
            $stmt = $pdo->prepare("UPDATE payment_methods SET balance = balance - ? WHERE id = ?");
            $stmt->execute([$total_cost_php, $payment_method_id]);

            // Update wallet holdings
            $stmt = $pdo->prepare("
                INSERT INTO user_crypto_holdings (user_id, crypto_id, wallet_id, quantity) 
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE quantity = quantity + ?
            ");
            $stmt->execute([$user_id, $crypto_id, $wallet_id, $trade_amount, $trade_amount]);

        } elseif ($trade_type === 'sell') {
            $total_sale = $crypto_data['current_marketprice'] * $trade_amount;
            $total_sale_php = $total_sale * $conversion_rate;

            if ($trade_amount > $selected_wallet['wallet_crypto_balance']) {
                throw new Exception("Not enough crypto in the wallet.");
            }

            // Update crypto supply
            $stmt = $pdo->prepare("UPDATE cryptos SET available_supply = available_supply + ? WHERE id = ?");
            $stmt->execute([$trade_amount, $crypto_id]);

            // Update payment method balance
            $stmt = $pdo->prepare("UPDATE payment_methods SET balance = balance + ? WHERE id = ?");
            $stmt->execute([$total_sale_php, $payment_method_id]);

            // Update wallet holdings
            $stmt = $pdo->prepare("
                UPDATE user_crypto_holdings 
                SET quantity = quantity - ? 
                WHERE wallet_id = ? AND crypto_id = ?
            ");
            $stmt->execute([$trade_amount, $wallet_id, $crypto_id]);
        }

        // Insert trade transaction record
        $stmt = $pdo->prepare("
            INSERT INTO trade_transactions 
            (id, user_id, crypto_id, wallet_id, payment_method_id, trade_type, amount, crypto_price, total_cost_usd, total_cost_php) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $trade_transaction_id,
            $user_id,
            $crypto_id,
            $wallet_id,
            $payment_method_id,
            $trade_type,
            $trade_amount,
            $crypto_data['current_marketprice'],
            $total_cost ?? $total_sale, // Use appropriate variable based on trade type
            $total_cost_php ?? $total_sale_php
        ]);

        $pdo->commit();

        // Store trade details in session for displaying in modal
        $_SESSION['last_trade_details'] = [
            'id' => $trade_transaction_id,
            'crypto_name' => $crypto_data['name'],
            'crypto_symbol' => $crypto_data['symbol'],
            'trade_type' => $trade_type,
            'amount' => $trade_amount,
            'price' => $crypto_data['current_marketprice'],
            'total_usd' => $total_cost ?? $total_sale,
            'total_php' => $total_cost_php ?? $total_sale_php
        ];

        $trade_success = true;

        if ($trade_success) {
            echo "<script>window.location.href = 'trade_crypto.php?success=1';</script>";
            exit();
        }
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $error_message = $e->getMessage();
    }
}

// Handle success message after redirection
if (isset($_GET['success']) && $_GET['success'] == 1) {
    $success_message = "Trade completed successfully!";
}
?>

<style>
body { background: linear-gradient(135deg, #f6d365 0%, #fda085 100%); font-family: 'Inter', 'Arial', sans-serif; }
.content-container {
            max-width: 2000px;
            margin: 2rem auto;
            padding: 2 15px;
        }
</style>

<div class="content-container">
    <div class="row">
        <div class="col-md-8">
            <h2 class="mb-4">
                <i class="bi bi-currency-exchange me-2"></i>Crypto Trading Center
            </h2>
        </div>
        
    </div>

    <?php if (!empty($error_message)): ?>
    <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-3 fs-4"></i>
        <div class="flex-grow-1">
            <?php echo htmlspecialchars($error_message); ?>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    
    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-arrow-left-right me-2"></i>Trade Transaction
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label for="crypto_id" class="form-label">Select Cryptocurrency</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-coin"></i></span>
                                    <select name="crypto_id" id="crypto_id" class="form-select" required>
                                    <?php foreach ($available_cryptos as $crypto): ?>
                                        <option value="<?php echo $crypto['id']; ?>" 
                                            <?php 
                                            if ($pre_selected_crypto && $crypto['id'] == $pre_selected_crypto['id']) {
                                                echo 'selected';
                                            } 
                                            ?>>
                                            <?php echo htmlspecialchars($crypto['name'] . ' (' . $crypto['symbol'] . ')'); ?> 
                                            - $<?php echo number_format($crypto['current_marketprice'], 2); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label for="wallet_id">Trading Wallet</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-safe2"></i></span>
                                    <select name="wallet_id" id="wallet_id" class="form-select" required>
                                    <?php foreach ($crypto_wallets as $wallet): ?>
                                        <option value="<?php echo $wallet['id']; ?>"
                                            <?php 
                                            if ($pre_selected_wallet_id && $wallet['id'] == $pre_selected_wallet_id) {
                                                echo 'selected';
                                            }
                                            ?>>
                                            <?php 
                                            switch($wallet['wallet_type']) {
                                                case 'hardware':
                                                    echo "🔒 Secure Hardware Wallet";
                                                    break;
                                                case 'software':
                                                    echo "💻 Desktop Software Wallet";
                                                    break;
                                                case 'mobile':
                                                    echo "📱 Mobile Wallet";
                                                    break;
                                                case 'web':
                                                    echo "🌐 Web Wallet";
                                                    break;
                                                default:
                                                    echo "💼 " . ucfirst($wallet['wallet_type']) ."";
                                            }
                                            ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label for="payment_method">Payment Method</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-credit-card"></i></span>
                                    <select name="payment_method" id="payment_method" class="form-select" required>
                                    <?php foreach ($payment_methods as $method): ?>
                                        <option value="<?php echo $method['id']; ?>">
                                            <?php 
                                            switch($method['method_type']) {
                                                case 'bank':
                                                    echo "🏦 Bank Transfer";
                                                    break;
                                                case 'credit':
                                                    echo "💳 Credit Card";
                                                    break;
                                                case 'debit':
                                                    echo "💳 Debit Card";
                                                    break;
                                                case 'paypal':
                                                    echo "💸 PayPal";
                                                    break;
                                                default:
                                                    echo ucfirst($method['method_type']);
                                            }
                                            ?> 
                                            - Balance: ₱<?php echo number_format($method['balance'], 2); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label for="trade_type">Trade Type</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-shuffle"></i></span>
                                    <select name="trade_type" id="trade_type" class="form-select" required>
                                        <option value="buy" <?php echo $pre_selected_trade_type === 'buy' ? 'selected' : ''; ?>>🟢 Buy</option>
                                        <option value="sell" <?php echo $pre_selected_trade_type === 'sell' ? 'selected' : ''; ?>>🔴 Sell</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-12">
                                <label for="trade_amount" class="form-label">Trade Amount</label>
                                <div class="input-group">
                                    <span class="input-group-text">₿</span>
                                    <input type="number" name="trade_amount" id="trade_amount" 
                                           class="form-control" step="0.00000001" min="0.00000001" 
                                           placeholder="Enter amount to trade" required>
                                </div>
                                <div class="form-text">
                                    Minimum trade amount: 0.00000001 cryptocurrency units
                                </div>
                            </div>

                            <div class="col-md-12">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-check-circle me-2"></i>Execute Trade
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Trade Confirmation Modal -->
            <div class="modal fade" id="tradeConfirmationModal" tabindex="-1" aria-labelledby="tradeConfirmationModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="tradeConfirmationModalLabel">Confirm Trade</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-6">
                                    <strong>Cryptocurrency:</strong>
                                    <p id="confirmCryptoName"></p>
                                </div>
                                <div class="col-6">
                                    <strong>Trade Type:</strong>
                                    <p id="confirmTradeType"></p>
                                </div>
                                <div class="col-6">
                                    <strong>Amount:</strong>
                                    <p id="confirmTradeAmount"></p>
                                </div>
                                <div class="col-6">
                                    <strong>Crypto Price:</strong>
                                    <p id="confirmCryptoPrice"></p>
                                </div>
                                <div class="col-6">
                                    <strong>Total Cost (USD):</strong>
                                    <p id="confirmTotalCostUSD"></p>
                                </div>
                                <div class="col-6">
                                    <strong>Total Cost (PHP):</strong>
                                    <p id="confirmTotalCostPHP"></p>
                                </div>
                                <div class="col-6">
                                    <strong>Wallet:</strong>
                                    <p id="confirmWallet"></p>
                                </div>
                                <div class="col-6">
                                    <strong>Payment Method:</strong>
                                    <p id="confirmPaymentMethod"></p>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" id="confirmTradeBtn">Confirm Trade</button>
                        </div>
                    </div>
                </div>
            </div>

        <!-- Sidebar with additional information -->
        <div class="col-md-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-warning">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-info-circle me-2"></i>Your Wallet
                    </h6>
                </div>
                <div class="card-body">
                <ul class="list-unstyled">
                    <?php foreach ($crypto_wallets as $wallet): ?>
                        <li class="mb-3">
                            <strong>
                                <?php 
                                echo match($wallet['wallet_type']) {
                                    'MetaMask' => '🦊 MetaMask',
                                    'Ledger Nano X' => '🔒 Ledger Nano X',
                                    'Trezor Model T' => '🔒 Trezor Model T',
                                    'Exodus' => '🌐 Exodus',
                                    'Trust Wallet' => '📱 Trust Wallet',
                                    'Coinbase Wallet' => '💰 Coinbase Wallet',
                                    'Electrum' => '💻 Electrum',
                                    'Mycelium' => '📱 Mycelium',
                                    'Samourai Wallet' => '🔐 Samourai Wallet',
                                    'BitPay Wallet' => '💳 BitPay Wallet',
                                    default => '💼 Wallet'
                                };
                                ?>
                            </strong>
                            <p class="text-muted small">
                                <?php 
                                echo match($wallet['wallet_type']) {
                                    'MetaMask' => 'Browser-based Ethereum wallet with built-in token support.',
                                    'Ledger Nano X' => 'Secure hardware wallet supporting multiple cryptocurrencies.',
                                    'Trezor Model T' => 'Advanced hardware wallet with touchscreen and robust security.',
                                    'Exodus' => 'Multi-cryptocurrency desktop and mobile wallet with exchange integration.',
                                    'Trust Wallet' => 'Mobile wallet with support for multiple blockchain networks.',
                                    'Coinbase Wallet' => 'Mobile and web wallet with decentralized storage.',
                                    'Electrum' => 'Lightweight Bitcoin wallet focused on speed and simplicity.',
                                    'Mycelium' => 'Mobile Bitcoin wallet with advanced privacy features.',
                                    'Samourai Wallet' => 'Privacy-focused mobile Bitcoin wallet.',
                                    'BitPay Wallet' => 'Secure multi-signature wallet with merchant services.',
                                    default => 'Cryptocurrency wallet with secure storage capabilities.'
                                };
                                ?>
                            </p>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            </div>
            <div id="crypto-details-card" class="card shadow-sm <?php echo $pre_selected_crypto ? '' : 'd-none'; ?>">
                <div class="card-header bg-success text-white">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-bar-chart me-2"></i><span id="crypto-name"><?php echo $pre_selected_crypto ? htmlspecialchars($pre_selected_crypto['name']) : 'Cryptocurrency'; ?> Details</span>
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Current Price:</span>
                        <strong id="current-price">$<?php echo $pre_selected_crypto ? number_format($pre_selected_crypto['current_marketprice'], 2) : '0.00'; ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Available Supply:</span>
                        <strong id="available-supply"><?php echo $pre_selected_crypto ? number_format($pre_selected_crypto['available_supply']) : '0'; ?></strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Highest Market Price:</span>
                        <strong id="highest-price">$<?php echo $pre_selected_crypto ? number_format($pre_selected_crypto['highest_marketprice'], 2) : '0.00'; ?></strong>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <?php if (isset($_SESSION['last_trade_details'])): ?>
        <div class="modal fade" id="tradeSuccessModal" tabindex="-1" aria-labelledby="tradeSuccessModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title" id="tradeSuccessModalLabel">
                            <i class="bi bi-check-circle me-2"></i>Trade Successful
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <div class="alert alert-info" role="alert">
                                    <strong>Transaction ID:</strong> 
                                    <code><?php echo $_SESSION['last_trade_details']['id']; ?></code>
                                </div>
                            </div>
                            <div class="col-6">
                                <strong>Cryptocurrency:</strong>
                                <p><?php echo $_SESSION['last_trade_details']['crypto_name'] . ' (' . $_SESSION['last_trade_details']['crypto_symbol'] . ')'; ?></p>
                            </div>
                            <div class="col-6">
                                <strong>Trade Type:</strong>
                                <p><?php 
                                    echo $_SESSION['last_trade_details']['trade_type'] === 'buy' 
                                        ? '<span class="text-success">🟢 Buy</span>' 
                                        : '<span class="text-danger">🔴 Sell</span>'; 
                                ?></p>
                            </div>
                            <div class="col-6">
                                <strong>Amount:</strong>
                                <p><?php echo number_format($_SESSION['last_trade_details']['amount'], 8) . ' ' . $_SESSION['last_trade_details']['crypto_symbol']; ?></p>
                            </div>
                            <div class="col-6">
                                <strong>Price per Unit:</strong>
                                <p>$<?php echo number_format($_SESSION['last_trade_details']['price'], 2); ?></p>
                            </div>
                            <div class="col-6">
                                <strong>Total Value (USD):</strong>
                                <p>$<?php echo number_format($_SESSION['last_trade_details']['total_usd'], 2); ?></p>
                            </div>
                            <div class="col-6">
                                <strong>Total Value (PHP):</strong>
                                <p>₱<?php echo number_format($_SESSION['last_trade_details']['total_php'], 2); ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

</div>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const cryptoSelect = document.getElementById('crypto_id');
                const cryptoDetailsCard = document.getElementById('crypto-details-card');
                const currentPriceEl = document.getElementById('current-price');
                const availableSupplyEl = document.getElementById('available-supply');
                const highestPriceEl = document.getElementById('highest-price');
                const cryptoNameEl = document.getElementById('crypto-name');

                const cryptoData = <?php echo json_encode($available_cryptos); ?>;

                function updateCryptoDetails() {
                    const selectedCryptoId = cryptoSelect.value;
                    const selectedCrypto = cryptoData.find(crypto => crypto.id == selectedCryptoId);

                    if (selectedCrypto) {
                        cryptoDetailsCard.classList.remove('d-none');
                        cryptoNameEl.textContent = selectedCrypto.name;
                        currentPriceEl.textContent = '$' + parseFloat(selectedCrypto.current_marketprice).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                        availableSupplyEl.textContent = parseInt(selectedCrypto.available_supply).toLocaleString('en-US');
                        highestPriceEl.textContent = '$' + parseFloat(selectedCrypto.highest_marketprice).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                    } else {
                        cryptoDetailsCard.classList.add('d-none');
                    }
                }

                // Initial setup
                updateCryptoDetails();

                // Update on change
                cryptoSelect.addEventListener('change', updateCryptoDetails);
            });
        </script> 
        
        <script>
           document.addEventListener('DOMContentLoaded', function() {
            const tradeForm = document.querySelector('form');
            const tradeConfirmationModal = document.getElementById('tradeConfirmationModal');
            const confirmTradeBtn = document.getElementById('confirmTradeBtn');

            const CONVERSION_RATE = 56.00;

            // Add custom style to prevent backdrop
            const style = document.createElement('style');
            style.textContent = `
                .modal-backdrop {
                    display: none !important;
                }
                .modal-open {
                    overflow: visible !important;
                    padding-right: 0 !important;
                }
            `;
            document.head.appendChild(style);

            tradeForm.addEventListener('submit', function(e) {
                e.preventDefault();

                const cryptoSelect = document.getElementById('crypto_id');
                const selectedOption = cryptoSelect.options[cryptoSelect.selectedIndex];
                
                // Extract the exact price from the selected option's text
                const priceMatch = selectedOption.text.match(/\$([0-9,]+(\.[0-9]+)?)/);
                const cryptoPrice = priceMatch ? parseFloat(priceMatch[1].replace(/,/g, '')) : 0;
                
                const tradeAmount = document.getElementById('trade_amount').value;
                const tradeType = document.getElementById('trade_type').value;
                const walletSelect = document.getElementById('wallet_id');
                const paymentMethodSelect = document.getElementById('payment_method');
                const selectedPaymentMethod = paymentMethodSelect.options[paymentMethodSelect.selectedIndex];

                const cryptoSymbol = selectedOption.text.match(/\(([^)]+)\)/)[1];
                const cryptoName = selectedOption.text.split(' (')[0];

                const totalCostUSD = (tradeAmount * cryptoPrice).toFixed(2);
                const totalCostPHP = (totalCostUSD * CONVERSION_RATE).toFixed(2);

                // Extract current balance from the payment method option text
                const balanceMatch = selectedPaymentMethod.text.match(/Balance: ₱([0-9,]+(\.[0-9]+)?)/);
                const currentBalance = balanceMatch ? parseFloat(balanceMatch[1].replace(/,/g, '')) : 0;

                // Calculate new balance based on transaction type
                let newBalance, balanceChangeSymbol, balanceChangeClass;
                if (tradeType === 'buy') {
                    newBalance = (currentBalance - parseFloat(totalCostPHP)).toFixed(2);
                    balanceChangeSymbol = '-';
                    balanceChangeClass = 'text-danger';
                } else {
                    newBalance = (currentBalance + parseFloat(totalCostPHP)).toFixed(2);
                    balanceChangeSymbol = '+';
                    balanceChangeClass = 'text-success';
                }

                document.getElementById('confirmCryptoName').textContent = cryptoName;
                document.getElementById('confirmTradeType').textContent = 
                    tradeType === 'buy' ? '🟢 Buy' : '🔴 Sell';
                document.getElementById('confirmTradeAmount').textContent = 
                    `${tradeAmount} ${cryptoSymbol}`;
                document.getElementById('confirmCryptoPrice').textContent = `$${cryptoPrice.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
                document.getElementById('confirmTotalCostUSD').textContent = `$${totalCostUSD}`;
                document.getElementById('confirmTotalCostPHP').textContent = `₱${totalCostPHP.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
                document.getElementById('confirmWallet').textContent = 
                    walletSelect.options[walletSelect.selectedIndex].text;
                
                // Update payment method display with potential balance change
                document.getElementById('confirmPaymentMethod').innerHTML = 
                    `${selectedPaymentMethod.text.split(' - Balance:')[0]} - Balance: <span class="${balanceChangeClass}">₱${currentBalance.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})} ${balanceChangeSymbol} ₱${totalCostPHP.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})} = ₱${newBalance.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>`;

                // Use native JavaScript to show the modal
                const modal = new bootstrap.Modal(tradeConfirmationModal, {
                    backdrop: false,
                    keyboard: false
                });
                modal.show();

                confirmTradeBtn.onclick = function() {
                    tradeForm.submit();
                };
            });
        });
        </script>

        <script>
                document.addEventListener('DOMContentLoaded', function() {
                    var tradeSuccessModal = new bootstrap.Modal(document.getElementById('tradeSuccessModal'));
                    tradeSuccessModal.show();

                    // Clear the session data to prevent modal from showing on page refresh
                    <?php unset($_SESSION['last_trade_details']); ?>
                });
        </script>

<?php include 'footer.php'; ?>