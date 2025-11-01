<?php
/**
 * CPX Research Callback Handler
 * Receives and processes reward notifications from CPX Research
 */

header('Content-Type: application/json');

require_once __DIR__ . '/cpxresearch.php';

// Get callback parameters (CPX Research uses different parameter names)
$userId = $_GET['user_id'] ?? $_POST['user_id'] ?? '';
$amount = $_GET['reward_amount'] ?? $_POST['reward_amount'] ?? 0;
$transactionId = $_GET['transaction_id'] ?? $_POST['transaction_id'] ?? '';
$hash = $_GET['hash'] ?? $_POST['hash'] ?? '';
$surveyName = $_GET['survey_name'] ?? $_POST['survey_name'] ?? 'Survey';

// Log incoming callback for debugging
$logFile = __DIR__ . '/cpxresearch_callbacks.log';
$logEntry = sprintf(
    "[%s] Callback received - User: %s | Amount: %s | TxnID: %s | Survey: %s\n",
    date('Y-m-d H:i:s'),
    $userId,
    $amount,
    $transactionId,
    $surveyName
);
file_put_contents($logFile, $logEntry, FILE_APPEND);

// Validate required parameters
if (empty($userId) || empty($amount) || empty($transactionId) || empty($hash)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Missing required parameters'
    ]);
    exit;
}

// Verify hash
if (!CPXResearch::verifyCallback($userId, $amount, $transactionId, $hash)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid hash'
    ]);
    exit;
}

// Process the reward
if (CPXResearch::processReward($userId, $amount, $transactionId, $surveyName)) {
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
