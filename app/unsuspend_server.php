<?php
session_start();
require_once __DIR__ . '/billing.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /httpdocs/index.php');
    exit;
}

$userId = $_SESSION['user_id'];
$serverId = $_GET['id'] ?? '';

if (empty($serverId)) {
    header('Location: index.php');
    exit;
}

$result = EnderBitBilling::unsuspendServer($serverId, $userId);

if ($result['success']) {
    header('Location: index.php?msg=' . urlencode($result['message']) . '&msgtype=success');
} else {
    header('Location: index.php?msg=' . urlencode($result['error']) . '&msgtype=error');
}
