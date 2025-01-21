<?php
$page_title = 'Transaction History';
include 'header.php';
include '../config/db.php';

// User authentication check
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$error_message = '';

// Pagination setup
$results_per_page = 10;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $results_per_page;

// Filter options
$trade_type_filter = isset($_GET['trade_type']) ? $_GET['trade_type'] : null;
$crypto_filter = isset($_GET['crypto_id']) ? intval($_GET['crypto_id']) : null;
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : null;
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : null;

try {
    // Prepare base query to fetch transactions
    $query = "
        SELECT 
            tt.*, 
            c.name AS crypto_name, 
            c.symbol AS crypto_symbol,
            pm.method_type AS payment_method,
            cw.wallet_type AS wallet_type
        FROM 
            trade_transactions tt
        JOIN 
            cryptos c ON tt.crypto_id = c.id
        JOIN 
            payment_methods pm ON tt.payment_method_id = pm.id
        JOIN 
            crypto_wallets cw ON tt.wallet_id = cw.id
        WHERE 
            tt.user_id = :user_id
    ";

    // Apply filters
    $conditions = [];
    $params = ['user_id' => $user_id];

    // Add filter conditions (reuse existing filter logic from the original code)
    if ($trade_type_filter) {
        $conditions[] = "tt.trade_type = :trade_type";
        $params['trade_type'] = $trade_type_filter;
    }

    if ($crypto_filter) {
        $conditions[] = "tt.crypto_id = :crypto_id";
        $params['crypto_id'] = $crypto_filter;
    }

    if ($date_from) {
        $conditions[] = "tt.created_at >= :date_from";
        $params['date_from'] = $date_from . ' 00:00:00';
    }

    if ($date_to) {
        $conditions[] = "tt.created_at <= :date_to";
        $params['date_to'] = $date_to . ' 23:59:59';
    }

    // Add conditions to the query if any filters are applied
    if (!empty($conditions)) {
        $query .= " AND " . implode(" AND ", $conditions);
    }

    // Add sorting and pagination
    $query .= " ORDER BY tt.created_at DESC LIMIT :limit OFFSET :offset";

    // Prepare the statement
    $stmt = $pdo->prepare($query);

    // Bind all existing parameters
    foreach ($params as $key => $value) {
        $stmt->bindValue(':' . $key, $value);
    }

    // Bind LIMIT and OFFSET separately
    $stmt->bindValue(':limit', $results_per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

    // Execute the statement
    $stmt->execute();
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Prepare count query
    $count_query = "
        SELECT COUNT(*) AS total
        FROM 
            trade_transactions tt
        JOIN 
            cryptos c ON tt.crypto_id = c.id
        JOIN 
            payment_methods pm ON tt.payment_method_id = pm.id
        JOIN 
            crypto_wallets cw ON tt.wallet_id = cw.id
        WHERE 
            tt.user_id = :user_id
    ";

    // Add filter conditions to count query
    if (!empty($conditions)) {
        $count_query .= " AND " . implode(" AND ", $conditions);
    }

    // Prepare count statement
    $count_stmt = $pdo->prepare($count_query);

    // Bind parameters for count query
    foreach ($params as $key => $value) {
        $count_stmt->bindValue(':' . $key, $value);
    }

    // Execute count query
    $count_stmt->execute();
    $total_transactions = $count_stmt->fetchColumn();
    $total_pages = ceil($total_transactions / $results_per_page);

} catch (PDOException $e) {
    $error_message = "Error fetching transactions: " . $e->getMessage();
}

// Add this at the top of the file, after session start
if (!isset($_SESSION['flash_message'])) {
    $_SESSION['flash_message'] = '';
}

// In the delete transaction handling section
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!isset($_SESSION['user_id'])) {
        echo '<script>window.location.href = "login.php";</script>';
        exit();
    }

    $user_id = $_SESSION['user_id'];

    try {
        if ($_POST['action'] === 'delete_transaction' && isset($_POST['transaction_id'])) {
            $transaction_id = $_POST['transaction_id'];
            
            // Delete specific transaction
            $delete_stmt = $pdo->prepare("DELETE FROM trade_transactions WHERE id = :transaction_id AND user_id = :user_id");
            $delete_stmt->bindParam(':transaction_id', $transaction_id);
            $delete_stmt->bindParam(':user_id', $user_id);
            $delete_stmt->execute();

            // Set success message
            $_SESSION['flash_message'] = 'Transaction successfully deleted.';
        }

        if ($_POST['action'] === 'delete_all_transactions') {
            // Delete all transactions for the user
            $delete_all_stmt = $pdo->prepare("DELETE FROM trade_transactions WHERE user_id = :user_id");
            $delete_all_stmt->bindParam(':user_id', $user_id);
            $delete_all_stmt->execute();

            // Set success message
            $_SESSION['flash_message'] = 'All transactions successfully deleted.';
        }

        // JavaScript redirect
        echo '<script>window.location.href = "transactions.php";</script>';
        exit();
    } catch (PDOException $e) {
        $error_message = "Error deleting transactions: " . $e->getMessage();
    }
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
    <div class="row mb-4">
        <div class="col-md-12">
            <h2>
                <i class="bi bi-clock-history me-2"></i>Transaction History
            </h2>
        </div>
    </div>

    <?php if (!empty($error_message)): ?>
    <div class="alert alert-danger" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <?php echo htmlspecialchars($error_message); ?>
    </div>
    <?php endif; ?>

    <?php 
    // Display flash message if exists
    if (!empty($_SESSION['flash_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>
            <?php 
            echo htmlspecialchars($_SESSION['flash_message']); 
            // Clear the flash message after displaying
            $_SESSION['flash_message'] = '';
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php else: ?>
        <div class="row mb-3">
            <div class="col-md-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="bi bi-filter me-2"></i>Transaction Filters
                            </h5>
                            <span class="badge bg-light text-dark">
                                <?php echo $total_transactions; ?> Total Transactions
                            </span>
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label">Trade Type</label>
                                    <select name="trade_type" class="form-select">
                                        <option value="">All Types</option>
                                        <option value="buy" <?php echo $trade_type_filter === 'buy' ? 'selected' : ''; ?>>Buy</option>
                                        <option value="sell" <?php echo $trade_type_filter === 'sell' ? 'selected' : ''; ?>>Sell</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Cryptocurrency</label>
                                    <select name="crypto_id" class="form-select">
                                        <option value="">All Cryptocurrencies</option>
                                        <?php foreach ($available_cryptos as $crypto): ?>
                                            <option value="<?php echo $crypto['id']; ?>" 
                                                <?php echo $crypto_filter === $crypto['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($crypto['name'] . ' (' . $crypto['symbol'] . ')'); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Date From</label>
                                    <input type="date" name="date_from" class="form-control" 
                                           value="<?php echo htmlspecialchars($date_from ?? ''); ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Date To</label>
                                    <input type="date" name="date_to" class="form-control" 
                                           value="<?php echo htmlspecialchars($date_to ?? ''); ?>">
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="bi bi-filter-circle me-2"></i>Apply Filters
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-success text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="bi bi-list-ul me-2"></i>Transaction List
                            </h5>
                            <div>
                                <button id="deleteAllTransactions" class="btn btn-danger btn-sm">
                                    <i class="bi bi-trash me-1"></i>Delete All Transactions
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Transaction ID</th>
                                    <th>Date</th>
                                    <th>Crypto</th>
                                    <th>Type</th>
                                    <th>Amount</th>
                                    <th>Price</th>
                                    <th>Total (USD)</th>
                                    <th>Total (PHP)</th>
                                    <th>Wallet</th>
                                    <th>Payment Method</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($transactions as $transaction): ?>
                                    <tr class="<?php echo $transaction['trade_type'] === 'buy' ? 'table-success' : 'table-danger'; ?>">
                                        <td>
                                            <code><?php echo htmlspecialchars(substr($transaction['id'], 0, 8)); ?></code>
                                        </td>
                                        
                                        <td><?php echo date('Y-m-d H:i', strtotime($transaction['created_at'])); ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($transaction['crypto_name']); ?> 
                                            (<?php echo htmlspecialchars($transaction['crypto_symbol']); ?>)
                                        </td>
                                        <td>
                                            <?php 
                                            echo $transaction['trade_type'] === 'buy' 
                                                ? '<span class="badge bg-success">Buy</span>' 
                                                : '<span class="badge bg-danger">Sell</span>'; 
                                            ?>
                                        </td>
                                        <td><?php echo number_format($transaction['amount'], 8); ?></td>
                                        <td>$<?php echo number_format($transaction['crypto_price'], 2); ?></td>
                                        <td>$<?php echo number_format($transaction['total_cost_usd'], 2); ?></td>
                                        <td>₱<?php echo number_format($transaction['total_cost_php'], 2); ?></td>
                                        <td>
                                            <?php 
                                            echo match($transaction['wallet_type']) {
                                                'hardware' => '🔒 Hardware',
                                                'software' => '💻 Software',
                                                'mobile' => '📱 Mobile',
                                                'web' => '🌐 Web',
                                                default => ucfirst($transaction['wallet_type'])
                                            }; 
                                            ?>
                                        </td>
                                        <td><?php 
                                            echo match($transaction['payment_method']) {
                                                'bank' => '🏦 Bank Transfer',
                                                'credit' => '💳 Credit Card',
                                                'debit' => '💳 Debit Card',
                                                'paypal' => '💸 PayPal',
                                                default => ucfirst($transaction['payment_method'])
                                            }; 
                                            ?>
                                        </td>
                                        <td>
                                            <a href="feedback.php?transaction_id=<?php echo urlencode($transaction['id']); ?>" class="btn btn-sm btn-info">
                                                <i class="bi bi-chat-left-text"></i> Submit Feedback
                                            </a>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-danger delete-transaction" 
                                                    data-transaction-id="<?php echo $transaction['id']; ?>">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <div class="card-footer">
                        <nav aria-label="Transaction history navigation">
                            <ul class="pagination justify-content-center">
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; 
                                            echo $trade_type_filter ? '&trade_type=' . urlencode($trade_type_filter) : '';
                                            echo $crypto_filter ? '&crypto_id=' . intval($crypto_filter) : '';
                                            echo $date_from ? '&date_from=' . urlencode($date_from) : '';
                                            echo $date_to ? '&date_to=' . urlencode($date_to) : '';
                                        ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                            </ul>
                        </nav>
                        <div class="text-center mt-2">
                            <small class="text-muted">
                                Showing <?php echo ($offset + 1) . ' - ' . min($offset + $results_per_page, $total_transactions); ?> 
                                of <?php echo $total_transactions; ?> transactions
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Individual Transaction Delete
    document.querySelectorAll('.delete-transaction').forEach(button => {
        button.addEventListener('click', function() {
            const transactionId = this.getAttribute('data-transaction-id');
            
            if (confirm('Are you sure you want to delete this transaction?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '';

                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'delete_transaction';
                form.appendChild(actionInput);

                const transactionInput = document.createElement('input');
                transactionInput.type = 'hidden';
                transactionInput.name = 'transaction_id';
                transactionInput.value = transactionId;
                form.appendChild(transactionInput);

                document.body.appendChild(form);
                form.submit();
            }
        });
    });

    // Delete All Transactions
    const deleteAllButton = document.getElementById('deleteAllTransactions');
    if (deleteAllButton) {
        deleteAllButton.addEventListener('click', function() {
            if (confirm('Are you absolutely sure you want to delete ALL transactions? This action cannot be undone.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '';

                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'delete_all_transactions';
                form.appendChild(actionInput);

                document.body.appendChild(form);
                form.submit();
            }
        });
    }
});
</script>

<?php include 'footer.php'; ?>