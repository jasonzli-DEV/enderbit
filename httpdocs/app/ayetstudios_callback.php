<?php
/**
 * AyeT Studios Callback Handler
 * Receives and processes reward notifications from AyeT Studios
 */

header('Content-Type: application/json');

require_once __DIR__ . '/ayetstudios.php';

// Get callback parameters
$userId = $_GET['user_id'] ?? $_POST['user_id'] ?? '';
$amount = $_GET['amount'] ?? $_POST['amount'] ?? 0;
$transactionId = $_GET['transaction_id'] ?? $_POST['transaction_id'] ?? '';
$signature = $_GET['signature'] ?? $_POST['signature'] ?? '';
$offerName = $_GET['offer_name'] ?? $_POST['offer_name'] ?? 'Offer';

// Log incoming callback for debugging
$logFile = __DIR__ . '/ayetstudios_callbacks.log';
$logEntry = sprintf(
    "[%s] Callback received - User: %s | Amount: %s | TxnID: %s | Offer: %s\n",
    date('Y-m-d H:i:s'),
    $userId,
    $amount,
    $transactionId,
    $offerName
);
file_put_contents($logFile, $logEntry, FILE_APPEND);

// Validate required parameters
if (empty($userId) || empty($amount) || empty($transactionId) || empty($signature)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Missing required parameters'
    ]);
    exit;
}

// Verify signature
if (!AyeTStudios::verifyCallback($userId, $amount, $transactionId, $signature)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid signature'
    ]);
    exit;
}

// Process the reward
if (AyeTStudios::processReward($userId, $amount, $transactionId, $offerName)) {
    echo json_encode([
        'status' => 'success',
        'message' => 'Reward processed'
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to process reward'
    ]);
}
