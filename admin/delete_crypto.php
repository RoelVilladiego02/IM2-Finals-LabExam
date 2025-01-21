<?php
session_start();
if (!isset($_SESSION['admin_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

include '../config/db.php';
$error = '';
$success = '';

// Delete cryptocurrency if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $cryptoId = $_POST['crypto_id']; // Get crypto ID from form submission

    try {
        // Prepare the delete statement
        $stmt = $pdo->prepare("DELETE FROM cryptos WHERE id = ?");
        
        // Execute the delete statement
        if ($stmt->execute([$cryptoId])) {
            $success = "Cryptocurrency deleted successfully!";
        } else {
            $error = "Failed to delete cryptocurrency.";
        }
    } catch (PDOException $e) {
        $error = "An error occurred: " . htmlspecialchars($e->getMessage());
    }
}

// Fetch all cryptocurrencies for the list
$stmt = $pdo->query("SELECT * FROM cryptos");
$cryptos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include '../admin/header.php'; ?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body">
                    <h2 class="card-title text-center mb-0">
                        <i class="bi bi-trash text-danger me-2"></i>Delete Cryptocurrencies
                    </h2>
                </div>
            </div>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php elseif ($success): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($success); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (empty($cryptos)): ?>
        <div class="row">
            <div class="col-12">
                <div class="card text-center border-0 shadow-hover">
                    <div class="card-body py-5">
                        <i class="bi bi-box-seam text-muted fs-1 mb-3"></i>
                        <h4 class="card-title mb-3">No Cryptocurrencies Found</h4>
                        <p class="card-text text-muted mb-4">It seems there are no cryptocurrencies in your system.</p>
                        <a href="add_crypto.php" class="btn btn-primary">
                            <i class="bi bi-plus-circle me-2"></i>Add Cryptocurrency
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Crypto ID</th>
                                <th>Symbol</th>
                                <th>Name</th>
                                <th>Available Supply</th>
                                <th>Current Market Price</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cryptos as $crypto): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($crypto['id']); ?></td>
                                <td><?php echo htmlspecialchars($crypto['symbol']); ?></td>
                                <td><?php echo htmlspecialchars($crypto['name']); ?></td>
                                <td><?php echo htmlspecialchars($crypto['available_supply'] ?? 'N/A'); ?></td>
                                <td>$<?php echo number_format(floatval($crypto['current_marketprice']), 2); ?></td>
                                <td>
                                    <form action="delete_crypto.php" method="POST" onsubmit="return confirmDelete();">
                                        <input type="hidden" name="crypto_id" value="<?php echo htmlspecialchars($crypto['id']); ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-trash me-1"></i>Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
    /* Previous styles remain the same */
</style>

<script>
function confirmDelete() {
    return confirm("Are you sure you want to delete this cryptocurrency?");
}
</script>
</body>
</html>