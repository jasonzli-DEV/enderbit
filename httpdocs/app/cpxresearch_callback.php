<?php
/**
 * CPX Research Callback Handler
 * Receives and processes reward notifications from CPX Research
 * 
 * CPX Research sends callbacks with these parameters:
 * - user_id: Your ext_user_id
 * - transaction_id: Unique transaction ID
 * - currency_amount: Amount in your local currency
 * - payout: Payout amount
 * - type: Survey type (survey, video, offer, etc.)
 * - status: Transaction status (active, completed, reversed)
 */

// Set response type
header('Content-Type: text/plain');

require_once __DIR__ . '/cpxresearch.php';

// Get callback parameters from CPX Research
// They can send via GET or POST
$userId = $_GET['user_id'] ?? $_POST['user_id'] ?? '';
$transactionId = $_GET['transaction_id'] ?? $_POST['transaction_id'] ?? '';
$currencyAmount = $_GET['currency_amount'] ?? $_POST['currency_amount'] ?? 0;
$payout = $_GET['payout'] ?? $_POST['payout'] ?? 0;
$type = $_GET['type'] ?? $_POST['type'] ?? 'survey';
$status = $_GET['status'] ?? $_POST['status'] ?? 'completed';

// Log ALL incoming parameters for debugging
$logFile = __DIR__ . '/cpxresearch_callbacks.log';
$allParams = array_merge($_GET, $_POST);
$logEntry = sprintf(
    "[%s] Callback received - Raw params: %s\n",
    date('Y-m-d H:i:s'),
    json_encode($allParams)
);
file_put_contents($logFile, $logEntry, FILE_APPEND);

// Validate required parameters
if (empty($userId) || empty($transactionId)) {
    $logEntry = sprintf(
        "[%s] ERROR: Missing required parameters - User: %s | TxnID: %s\n",
        date('Y-m-d H:i:s'),
        $userId,
        $transactionId
    );
    file_put_contents($logFile, $logEntry, FILE_APPEND);
    
    echo "error: missing parameters";
    exit;
}

// Verify callback (basic validation)
$params = [
    'user_id' => $userId,
    'transaction_id' => $transactionId,
    'currency_amount' => $currencyAmount,
    'payout' => $payout,
    'type' => $type,
    'status' => $status,
];

if (!CPXResearch::verifyCallback($params)) {
    $logEntry = sprintf(
        "[%s] ERROR: Callback validation failed - User: %s | TxnID: %s\n",
        date('Y-m-d H:i:s'),
        $userId,
        $transactionId
    );
    file_put_contents($logFile, $logEntry, FILE_APPEND);
    
    echo "error: validation failed";
    exit;
}

// Only process if status is completed or active
if ($status !== 'completed' && $status !== 'active') {
    $logEntry = sprintf(
        "[%s] SKIPPED: Status is %s (not completed/active) - User: %s | TxnID: %s\n",
        date('Y-m-d H:i:s'),
        $status,
        $userId,
        $transactionId
    );
    file_put_contents($logFile, $logEntry, FILE_APPEND);
    
    echo "ok"; // Still respond OK to CPX
    exit;
}

// Use payout amount (this is what user earned)
$amount = !empty($payout) ? $payout : $currencyAmount;

// Process the reward
if (CPXResearch::processReward($userId, $amount, $transactionId, $type, $status)) {
    $logEntry = sprintf(
        "[%s] SUCCESS: Reward processed - User: %s | Amount: %s | TxnID: %s | Type: %s\n",
        date('Y-m-d H:i:s'),
        $userId,
        $amount,
        $transactionId,
        $type
    );
    file_put_contents($logFile, $logEntry, FILE_APPEND);
    
    // CPX Research expects simple "ok" response
    echo "ok";
} else {
    $logEntry = sprintf(
        "[%s] ERROR: Failed to process reward - User: %s | Amount: %s | TxnID: %s\n",
        date('Y-m-d H:i:s'),
        $userId,
        $amount,
        $transactionId
    );
    file_put_contents($logFile, $logEntry, FILE_APPEND);
    
    echo "error: processing failed";
}

