<?php
session_start();
include '../config/db.php';

header('Content-Type: application/json');

// Response function
function sendResponse($success, $message) {
    echo json_encode([
        'success' => $success,
        'message' => $message
    ]);
    exit();
}

// Check authentication
if (!isset($_SESSION['user_id'])) {
    sendResponse(false, "Unauthorized access.");
}

$user_id = $_SESSION['user_id'];

$wallet_types = [
    'MetaMask', 'Ledger Nano X', 'Trezor Model T', 
    'Exodus', 'Trust Wallet', 'Coinbase Wallet', 
    'Electrum', 'Mycelium', 'Samourai Wallet', 'BitPay Wallet'
];

// Wallet addition logic
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $wallet_type = $_POST['wallet_type'] ?? '';
    $wallet_address = trim($_POST['wallet_address'] ?? '');

    // Validation patterns
    $validation_patterns = [
        'ethereum' => '/^0x[a-fA-F0-9]{40}$/',
        'bitcoin' => '/^(bc1|[13])[a-zA-HJ-NP-Z0-9]{25,39}$/',
        'bitcoin_cash' => '/^(bitcoincash:)?(q|p)[a-z0-9]{41}$/',
        'litecoin' => '/^[LM3][a-km-zA-HJ-NP-Z1-9]{26,33}$/'
    ];

    // Validate wallet type and address
    if (!in_array($wallet_type, $wallet_types)) {
        sendResponse(false, "Invalid wallet type selected.");
    }

    $is_valid_address = false;
    foreach ($validation_patterns as $pattern) {
        if (preg_match($pattern, $wallet_address)) {
            $is_valid_address = true;
            break;
        }
    }

    if (!$is_valid_address) {
        sendResponse(false, "Invalid wallet address format.");
    }

    try {
        // Check for existing wallet
        $stmt = $pdo->prepare("
            SELECT id FROM crypto_wallets 
            WHERE wallet_address = ? AND user_id = ?
        ");
        $stmt->execute([$wallet_address, $user_id]);
        
        if ($stmt->fetch()) {
            sendResponse(false, "This wallet is already registered.");
        }

        // Insert new wallet
        $stmt = $pdo->prepare("
            INSERT INTO crypto_wallets 
            (user_id, wallet_type, wallet_address, is_verified, verification_status) 
            VALUES (?, ?, ?, 0, 'Pending')
        ");
        $stmt->execute([$user_id, $wallet_type, $wallet_address]);
        
        sendResponse(true, "Wallet added successfully. It will be verified by admin.");

    } catch (PDOException $e) {
        error_log("Wallet addition error: " . $e->getMessage());
        sendResponse(false, "An error occurred while adding the wallet.");
    }
} else {
    sendResponse(false, "Invalid request method.");
}
?>