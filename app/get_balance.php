<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/credits.php';

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$userId = $_SESSION['user_id'];
$balance = EnderBitCredits::getBalance($userId);

header('Content-Type: application/json');
echo json_encode([
    'balance' => $balance,
    'formatted' => EnderBitCredits::formatCredits($balance)
]);
