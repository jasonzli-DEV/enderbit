<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/pterodactyl_api.php';

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

// Load servers
$config = require __DIR__ . '/config.php';
$serversFile = $config['servers_file'];
$servers = json_decode(file_get_contents($serversFile), true) ?? [];

// Find and delete server
$deleted = false;
foreach ($servers as $index => $server) {
    if ($server['id'] === $serverId && $server['user_id'] === $userId) {
        // Delete from Pterodactyl
        PterodactylAPI::deleteServer($server['pterodactyl_id']);
        
        // Remove from local database
        unset($servers[$index]);
        $deleted = true;
        break;
    }
}

if ($deleted) {
    file_put_contents($serversFile, json_encode(array_values($servers), JSON_PRETTY_PRINT));
    header('Location: index.php?msg=' . urlencode('Server deleted successfully') . '&msgtype=success');
} else {
    header('Location: index.php?msg=' . urlencode('Server not found') . '&msgtype=error');
}
