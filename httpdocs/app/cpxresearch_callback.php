<?php
/**
 * CPX Research Callback Handler
 * Receives and processes reward notifications from CPX Research
 * 
 * CPX Research sends callbacks with these parameters:
 * - status: 1 = completed, 2 = canceled
 * - trans_id: Unique transaction ID
 * - user_id: Your ext_user_id
 * - subid_1: Your subId1
 * - subid_2: Your subId2
 * - amount_local: Amount in your local currency
 * - amount_usd: Amount in USD
 * - type: "out", "complete", or "bonus"
 * - secure_hash: MD5 hash for validation: md5(trans_id-your_app_secure_hash)
 */

// Set response type
header('Content-Type: text/plain');

require_once __DIR__ . '/cpxresearch.php';

// Get callback parameters from CPX Research (using ACTUAL parameter names)
$status = $_GET['status'] ?? $_POST['status'] ?? '';
$transId = $_GET['trans_id'] ?? $_POST['trans_id'] ?? '';
$userId = $_GET['user_id'] ?? $_POST['user_id'] ?? '';
$subid1 = $_GET['subid_1'] ?? $_POST['subid_1'] ?? '';
$subid2 = $_GET['subid_2'] ?? $_POST['subid_2'] ?? '';
$amountLocal = $_GET['amount_local'] ?? $_POST['amount_local'] ?? 0;
$amountUsd = $_GET['amount_usd'] ?? $_POST['amount_usd'] ?? 0;
$type = $_GET['type'] ?? $_POST['type'] ?? '';
$secureHash = $_GET['secure_hash'] ?? $_POST['secure_hash'] ?? '';

// Log ALL incoming parameters for debugging
$logFile = __DIR__ . '/cpxresearch_callbacks.log';
$allParams = array_merge($_GET, $_POST);
$clientIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$logEntry = sprintf(
    "[%s] Callback received from IP: %s - Raw params: %s\n",
    date('Y-m-d H:i:s'),
    $clientIP,
    json_encode($allParams)
);
file_put_contents($logFile, $logEntry, FILE_APPEND);

// Optional: Check if request is from CPX Research IP (uncomment to enable)
// require_once __DIR__ . '/cpxresearch.php';
// if (!CPXResearch::isValidCPXIP()) {
//     file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] ERROR: Invalid IP: $clientIP\n", FILE_APPEND);
//     echo "error: invalid ip";
//     exit;
// }

// Validate required parameters
if (empty($userId) || empty($transId)) {
    $logEntry = sprintf(
        "[%s] ERROR: Missing required parameters - User: %s | TransID: %s\n",
        date('Y-m-d H:i:s'),
        $userId,
        $transId
    );
    file_put_contents($logFile, $logEntry, FILE_APPEND);
    
    echo "error: missing parameters";
    exit;
}

// Verify secure hash
if (!empty($secureHash)) {
    require_once __DIR__ . '/cpxresearch.php';
    if (!CPXResearch::verifyCallbackHash($transId, $secureHash)) {
        $logEntry = sprintf(
            "[%s] ERROR: Invalid secure hash - User: %s | TransID: %s\n",
            date('Y-m-d H:i:s'),
            $userId,
            $transId
        );
        file_put_contents($logFile, $logEntry, FILE_APPEND);
        
        echo "error: invalid hash";
        exit;
    }
}

// Check status: 1 = completed, 2 = canceled
if ($status == '2') {
    // Status 2 = canceled/fraud detected
    // TODO: You may want to REVERSE the credits here
    $logEntry = sprintf(
        "[%s] CANCELED: Transaction canceled/fraud - User: %s | TransID: %s | Type: %s\n",
        date('Y-m-d H:i:s'),
        $userId,
        $transId,
        $type
    );
    file_put_contents($logFile, $logEntry, FILE_APPEND);
    
    echo "ok"; // Still respond OK
    exit;
}

// Only process if status is 1 (completed) and type is "complete" or "bonus"
if ($status != '1' || ($type != 'complete' && $type != 'bonus')) {
    $logEntry = sprintf(
        "[%s] SKIPPED: Status=%s, Type=%s - User: %s | TransID: %s\n",
        date('Y-m-d H:i:s'),
        $status,
        $type,
        $userId,
        $transId
    );
    file_put_contents($logFile, $logEntry, FILE_APPEND);
    
    echo "ok"; // Still respond OK to CPX
    exit;
}

// Use amount_local (your currency)
$amount = $amountLocal;

// Process the reward
require_once __DIR__ . '/cpxresearch.php';
if (CPXResearch::processReward($userId, $amount, $transId, $type, $status)) {
    $logEntry = sprintf(
        "[%s] SUCCESS: Reward processed - User: %s | Amount: %s | TransID: %s | Type: %s | SubID1: %s | SubID2: %s\n",
        date('Y-m-d H:i:s'),
        $userId,
        $amount,
        $transId,
        $type,
        $subid1,
        $subid2
    );
    file_put_contents($logFile, $logEntry, FILE_APPEND);
    
    // CPX Research expects simple "ok" response
    echo "ok";
} else {
    $logEntry = sprintf(
        "[%s] ERROR: Failed to process reward - User: %s | Amount: %s | TransID: %s\n",
        date('Y-m-d H:i:s'),
        $userId,
        $amount,
        $transId
    );
    file_put_contents($logFile, $logEntry, FILE_APPEND);
    
    echo "error: processing failed";
}


