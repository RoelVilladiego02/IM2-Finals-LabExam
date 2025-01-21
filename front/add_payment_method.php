<?php
$page_title = 'Add Payment Method';
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

// Fetch user's existing payment methods
try {
    $stmt = $pdo->prepare("SELECT * FROM payment_methods WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $existing_methods = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Error fetching payment methods.";
}

// Handle payment method submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $method_type = $_POST['method_type'];
    $account_name = $_POST['account_name'];
    $account_number = $_POST['account_number'];

    try {
        $stmt = $pdo->prepare("
            INSERT INTO payment_methods 
            (user_id, method_type, account_name, account_number) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $user_id, 
            $method_type, 
            $account_name, 
            $account_number
        ]);

        $success_message = "Payment method added successfully.";
    } catch (PDOException $e) {
        $error_message = "Error adding payment method. It may already exist.";
    }
}
?>

<style>
body { background: linear-gradient(135deg, #f6d365 0%, #fda085 100%); font-family: 'Inter', 'Arial', sans-serif; }
.content-container { max-width: 1000px; margin: 2rem auto; padding: 2 15px;}
</style>

<div class="content-container">
    <h2>Add Payment Method</h2>

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
            <h5 class="card-title">Existing Payment Methods</h5>
            <?php if (empty($existing_methods)): ?>
                <p>No payment methods added yet.</p>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Method</th>
                            <th>Account Name</th>
                            <th>Account Number</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($existing_methods as $method): ?>
                            <tr>
                                <td><?php echo ucfirst(htmlspecialchars($method['method_type'])); ?></td>
                                <td><?php echo htmlspecialchars($method['account_name']); ?></td>
                                <td><?php echo htmlspecialchars($method['account_number']); ?></td>
                                <td>
                                    <?php echo $method['is_verified'] ? 'Verified' : 'Pending Verification'; ?>
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
            <h5 class="card-title">Add New Payment Method</h5>
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="method_type" class="form-label">Payment Method</label>
                    <select name="method_type" id="method_type" class="form-select" required>
                        <option value="gcash">GCash</option>
                        <option value="paymaya">PayMaya</option>
                        <option value="credit_card">Credit Card</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="account_name" class="form-label">Account Name</label>
                    <input type="text" name="account_name" id="account_name" 
                           class="form-control" required placeholder="Name on Account">
                </div>
                <div class="mb-3">
                    <label for="account_number" class="form-label">Account Number</label>
                    <input type="text" name="account_number" id="account_number" 
                           class="form-control" required placeholder="Account Number">
                </div>

                <div class="d-flex justify-content-between">
                    <a href="account_info.php" class="btn btn-secondary">Back to Account</a>
                    <button type="submit" class="btn btn-primary">Add Payment Method</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>