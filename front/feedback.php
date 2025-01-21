<?php
$page_title = 'Transaction Feedbacks';
include 'header.php';
include '../config/db.php';

// User authentication check
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$transaction_id = isset($_GET['transaction_id']) ? $_GET['transaction_id'] : null;
$error_message = '';
$success_message = '';

// Check if the request is for a specific transaction feedback
$is_specific_transaction = !empty($transaction_id);

// Fetch transaction details if a specific transaction is requested
if ($is_specific_transaction) {
    try {
        $transaction_stmt = $pdo->prepare("
            SELECT 
                tt.*, 
                c.name AS crypto_name, 
                c.symbol AS crypto_symbol
            FROM 
                trade_transactions tt
            JOIN 
                cryptos c ON tt.crypto_id = c.id
            WHERE 
                tt.id = :transaction_id AND tt.user_id = :user_id
        ");
        $transaction_stmt->bindParam(':transaction_id', $transaction_id);
        $transaction_stmt->bindParam(':user_id', $user_id);
        $transaction_stmt->execute();
        $transaction = $transaction_stmt->fetch(PDO::FETCH_ASSOC);

        if (!$transaction) {
            $error_message = "Transaction not found.";
        }
    } catch (PDOException $e) {
        $error_message = "Error fetching transaction details: " . $e->getMessage();
    }

    // Handle feedback submission for specific transaction
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            $rating = isset($_POST['rating']) ? intval($_POST['rating']) : null;
            $comment = isset($_POST['comment']) ? trim($_POST['comment']) : null;

            if ($rating === null || $rating < 1 || $rating > 5) {
                $error_message = "Please select a valid rating between 1 and 5.";
            } elseif (empty($comment)) {
                $error_message = "Please provide a comment.";
            } else {
                $feedback_stmt = $pdo->prepare("
                    INSERT INTO transaction_feedbacks 
                    (transaction_id, user_id, rating, comment) 
                    VALUES (:transaction_id, :user_id, :rating, :comment)
                ");
                $feedback_stmt->bindParam(':transaction_id', $transaction_id);
                $feedback_stmt->bindParam(':user_id', $user_id);
                $feedback_stmt->bindParam(':rating', $rating);
                $feedback_stmt->bindParam(':comment', $comment);
                $feedback_stmt->execute();

                $success_message = "Feedback submitted successfully!";
            }
        } catch (PDOException $e) {
            $error_message = "Error submitting feedback: " . $e->getMessage();
        }
    }
}

// Fetch feedbacks (all or for specific transaction)
try {
    if ($is_specific_transaction) {
        $feedbacks_stmt = $pdo->prepare("
            SELECT 
                tf.*, 
                u.name AS user_name
            FROM 
                transaction_feedbacks tf
            JOIN 
                users u ON tf.user_id = u.id
            WHERE 
                tf.transaction_id = :transaction_id
            ORDER BY 
                tf.created_at DESC
        ");
        $feedbacks_stmt->bindParam(':transaction_id', $transaction_id);
    } else {
        $feedbacks_stmt = $pdo->prepare("
            SELECT 
                tf.*,
                u.name AS user_name,
                tt.amount AS transaction_amount,
                c.name AS crypto_name,
                c.symbol AS crypto_symbol,
                tt.created_at AS transaction_date
            FROM 
                transaction_feedbacks tf
            JOIN 
                users u ON tf.user_id = u.id
            JOIN 
                trade_transactions tt ON tf.transaction_id = tt.id
            JOIN 
                cryptos c ON tt.crypto_id = c.id
            ORDER BY 
                tf.created_at DESC
        ");
    }
    
    $feedbacks_stmt->execute();
    $feedbacks = $feedbacks_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Error fetching feedbacks: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <style>
    body {
        background: linear-gradient(135deg, #f6d365 0%, #fda085 100%);
        font-family: 'Inter', 'Arial', sans-serif;
        background-attachment: fixed;
        min-height: 100vh;
        line-height: 1.6;
    }
    .main-content {
        padding: 3rem 0;
    }
    .card {
        border-radius: 16px;
        box-shadow: 0 12px 24px rgba(0, 0, 0, 0.1);
        margin-bottom: 1.5rem;
        overflow: hidden;
    }
    .card-header {
        padding: 1.25rem 1.5rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    .card-header i {
        font-size: 1.25rem;
    }
    .card-body {
        padding: 1.5rem;
    }
    .transaction-details {
        background-color: #f8f9fa;
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 1.5rem;
    }
    .rating-stars {
        display: flex;
        gap: 0.5rem;
    }
    .rating-stars .star-rating i {
        cursor: pointer;
        transition: transform 0.2s ease;
    }
    .rating-stars .star-rating i:hover {
        transform: scale(1.2);
    }
    .list-group-item {
        transition: all 0.3s ease;
    }
    .list-group-item:hover {
        background-color: #f8f9fa;
        transform: translateY(-3px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.05);
    }
    .no-feedbacks {
        text-align: center;
        padding: 3rem 1rem;
        color: #6c757d;
    }
    .btn-primary {
        transition: all 0.3s ease;
    }
    .btn-primary:hover {
        transform: translateY(-2px);
    }
    </style>
</head>
<body>
    <div class="main-content container-fluid">
        <div class="row justify-content-center">
            <div class="col-12 col-md-10 col-lg-8 col-xl-6">
                <?php if ($error_message): ?>
                    <div class="alert alert-danger alert-dismissible mb-4" role="alert">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-exclamation-triangle-fill me-3 fs-4"></i>
                            <div>
                                <?php echo htmlspecialchars($error_message); ?>
                            </div>
                            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($success_message): ?>
                    <div class="alert alert-success alert-dismissible mb-4" role="alert">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-check-circle-fill me-3 fs-4"></i>
                            <div>
                                <?php echo htmlspecialchars($success_message); ?>
                            </div>
                            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($is_specific_transaction && $transaction): ?>
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white d-flex align-items-center">
                            <i class="bi bi-chat-left-text me-2"></i>
                            <h5 class="mb-0">Submit Transaction Feedback</h5>
                        </div>
                        <div class="card-body">
                            <div class="transaction-details mb-3">
                                <div class="row">
                                    <div class="col-6">
                                        <small class="text-muted">Transaction ID</small>
                                        <p class="mb-1"><?php echo htmlspecialchars(substr($transaction_id, 0, 8)); ?></p>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">Crypto</small>
                                        <p class="mb-1">
                                            <?php echo htmlspecialchars($transaction['crypto_name'] . ' (' . $transaction['crypto_symbol'] . ')'); ?>
                                        </p>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">Amount</small>
                                        <p class="mb-1"><?php echo number_format($transaction['amount'], 8); ?></p>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">Date</small>
                                        <p class="mb-1"><?php echo date('Y-m-d H:i', strtotime($transaction['created_at'])); ?></p>
                                    </div>
                                </div>
                            </div>

                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label">Rating</label>
                                    <div class="rating-stars h4">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <input type="radio" class="d-none" id="rating-<?php echo $i; ?>" name="rating" value="<?php echo $i; ?>" required>
                                            <label for="rating-<?php echo $i; ?>" class="star-rating">
                                                <i class="bi bi-star<?php echo isset($_POST['rating']) && $_POST['rating'] >= $i ? '-fill' : ''; ?>"></i>
                                            </label>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Comment</label>
                                    <textarea class="form-control" name="comment" rows="3" required><?php echo htmlspecialchars($_POST['comment'] ?? ''); ?></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-send me-2"></i>Submit Feedback
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="card shadow-sm">
                    <div class="card-header bg-success text-white d-flex align-items-center">
                        <i class="bi bi-chat-text me-2"></i>
                        <h5 class="mb-0">
                            <?php echo $is_specific_transaction ? 'Transaction Feedbacks' : 'All Transaction Feedbacks'; ?>
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($feedbacks)): ?>
                            <div class="no-feedbacks">
                                <i class="bi bi-box-seam text-muted" style="font-size: 3rem;"></i>
                                <p class="text-muted mt-2">
                                    <?php echo $is_specific_transaction ? 'No feedbacks for this transaction yet.' : 'No feedbacks have been submitted yet.'; ?>
                                </p>
                            </div>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($feedbacks as $feedback): ?>
                                    <div class="list-group-item py-3 feedback-card">
                                        <div class="d-flex w-100 justify-content-between mb-2">
                                            <div class="d-flex align-items-center">
                                                <i class="bi bi-person-circle me-2 text-muted"></i>
                                                <h6 class="mb-0"><?php echo htmlspecialchars($feedback['user_name']); ?></h6>
                                            </div>
                                            <div class="rating-stars h6 mb-0">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <i class="bi bi-star<?php echo $i <= $feedback['rating'] ? '-fill' : ''; ?>"></i>
                                                <?php endfor; ?>
                                            </div>
                                        </div>
                                        <p class="mb-2"><?php echo htmlspecialchars($feedback['comment']); ?></p>
                                        
                                        <?php if (!$is_specific_transaction): ?>
                                            <div class="text-muted small">
                                                <strong><?php echo htmlspecialchars($feedback['crypto_name'] . ' (' . $feedback['crypto_symbol'] . ')'); ?></strong>
                                                - <?php echo number_format($feedback['transaction_amount'], 8); ?> 
                                                | <?php echo date('Y-m-d H:i', strtotime($feedback['transaction_date'])); ?>
                                            </div>
                                        <?php else: ?>
                                            <small class="text-muted">
                                                <?php echo date('Y-m-d H:i', strtotime($feedback['created_at'])); ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const starRatings = document.querySelectorAll('.star-rating');
        if (starRatings.length > 0) {
            starRatings.forEach(star => {
                star.addEventListener('click', function() {
                    const rating = this.getAttribute('for').split('-')[1];
                    const radioButton = document.getElementById('rating-' + rating);
                    
                    if (radioButton) {
                        radioButton.checked = true;
                        
                        starRatings.forEach((s, index) => {
                            const icon = s.querySelector('i');
                            icon.classList.toggle('bi-star-fill', index < rating);
                            icon.classList.toggle('bi-star', index >= rating);
                        });
                    }
                });

                // Add hover effect to stars
                star.addEventListener('mouseenter', function() {
                    const rating = this.getAttribute('for').split('-')[1];
                    starRatings.forEach((s, index) => {
                        const icon = s.querySelector('i');
                        icon.classList.toggle('text-warning', index < rating);
                    });
                });

                star.addEventListener('mouseleave', function() {
                    starRatings.forEach(s => {
                        const icon = s.querySelector('i');
                        icon.classList.remove('text-warning');
                    });
                });
            });
        }
    });
    </script>
</body>
</html>

<?php include 'footer.php'; ?>