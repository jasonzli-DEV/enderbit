<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/credits.php';

// Check if user is logged in (simple check - you may want to integrate with main site auth)
if (!isset($_SESSION['user_id'])) {
    header('Location: /httpdocs/index.php');
    exit;
}

$userId = $_SESSION['user_id'];
$balance = EnderBitCredits::getBalance($userId);

// Load user's servers
$config = require __DIR__ . '/config.php';
$serversFile = $config['servers_file'];
$allServers = json_decode(file_get_contents($serversFile), true) ?? [];
$userServers = array_filter($allServers, function($s) use ($userId) {
    return $s['user_id'] === $userId;
});

// Get recent transactions
$recentTransactions = EnderBitCredits::getTransactions($userId, 10);
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - EnderBit Client Portal</title>
    <link rel="stylesheet" href="/httpdocs/style.css">
    <style>
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 24px;
            margin-bottom: 32px;
        }
        .stat-card {
            background: var(--card);
            padding: 24px;
            border-radius: 12px;
            border: 1px solid var(--border);
        }
        .stat-value {
            font-size: 36px;
            font-weight: 700;
            color: var(--accent);
            margin: 12px 0;
        }
        .stat-label {
            font-size: 14px;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .server-card {
            background: var(--card);
            padding: 20px;
            border-radius: 12px;
            border: 1px solid var(--border);
            margin-bottom: 16px;
        }
        .server-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }
        .server-name {
            font-size: 18px;
            font-weight: 600;
        }
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-active {
            background: rgba(76, 217, 100, 0.2);
            color: var(--green);
        }
        .status-suspended {
            background: rgba(255, 204, 0, 0.2);
            color: var(--yellow);
        }
        .server-info {
            display: flex;
            gap: 24px;
            font-size: 14px;
            color: var(--muted);
            margin-bottom: 16px;
        }
        .server-actions {
            display: flex;
            gap: 12px;
        }
        .btn-sm {
            padding: 8px 16px;
            font-size: 13px;
        }
        .transaction-item {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid var(--border);
        }
        .transaction-amount-credit {
            color: var(--green);
            font-weight: 600;
        }
        .transaction-amount-debit {
            color: var(--red);
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container" style="max-width: 1400px; margin: 40px auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px;">
            <h1>🎮 Client Portal</h1>
            <div style="display: flex; gap: 16px; align-items: center;">
                <a href="earn_credits.php" class="btn btn-secondary">⚡ Earn Free Credits</a>
                <a href="../httpdocs/admin.php" class="btn">🏠 Main Site</a>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="dashboard-grid">
            <div class="stat-card">
                <div class="stat-label">💰 Credit Balance</div>
                <div class="stat-value"><?= EnderBitCredits::formatCredits($balance) ?></div>
                <a href="earn_credits.php" style="font-size: 13px; color: var(--accent);">Earn more credits →</a>
            </div>
            <div class="stat-card">
                <div class="stat-label">🖥️ Active Servers</div>
                <div class="stat-value"><?= count(array_filter($userServers, function($s) { return $s['status'] === 'active'; })) ?></div>
                <a href="create_server.php" style="font-size: 13px; color: var(--accent);">Create new server →</a>
            </div>
            <div class="stat-card">
                <div class="stat-label">📊 Total Servers</div>
                <div class="stat-value"><?= count($userServers) ?></div>
                <a href="#servers" style="font-size: 13px; color: var(--accent);">View all servers →</a>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card" style="margin-bottom: 32px;">
            <h2>⚡ Quick Actions</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-top: 20px;">
                <a href="create_server.php" class="btn btn-primary" style="text-align: center;">
                    🚀 Create Server
                </a>
                <a href="earn_credits.php" class="btn btn-secondary" style="text-align: center;">
                    ⚡ Earn Credits
                </a>
                <a href="transactions.php" class="btn" style="text-align: center;">
                    📊 View Transactions
                </a>
                <a href="https://panel.enderbit.com" target="_blank" class="btn" style="text-align: center;">
                    🎮 Manage in Panel
                </a>
            </div>
        </div>

        <!-- Servers Section -->
        <div class="card" id="servers" style="margin-bottom: 32px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                <h2>🖥️ Your Servers</h2>
                <a href="create_server.php" class="btn btn-primary">+ Create Server</a>
            </div>
            
            <?php if (empty($userServers)): ?>
                <div style="text-align: center; padding: 60px 20px; color: var(--muted);">
                    <div style="font-size: 48px; margin-bottom: 16px;">🎮</div>
                    <h3 style="margin-bottom: 12px;">No servers yet</h3>
                    <p style="margin-bottom: 24px;">Create your first game server to get started!</p>
                    <a href="create_server.php" class="btn btn-primary">Create Your First Server</a>
                </div>
            <?php else: ?>
                <?php foreach ($userServers as $server): ?>
                    <div class="server-card">
                        <div class="server-header">
                            <div class="server-name">
                                <?= htmlspecialchars($server['name']) ?>
                            </div>
                            <span class="status-badge status-<?= $server['status'] ?>">
                                <?= $server['status'] ?>
                            </span>
                        </div>
                        <div class="server-info">
                            <span>🎮 <?= ucfirst($server['game']) ?></span>
                            <span>📦 <?= ucfirst($server['plan']) ?></span>
                            <span>⚡ <?= $server['cost_per_hour'] ?> credits/hour</span>
                            <span>📅 Created <?= date('M j, Y', $server['created_at']) ?></span>
                        </div>
                        <?php if ($server['status'] === 'suspended'): ?>
                            <div style="background: rgba(255, 204, 0, 0.1); padding: 12px; border-radius: 8px; margin-bottom: 16px; font-size: 14px;">
                                ⚠️ <?= htmlspecialchars($server['suspended_reason'] ?? 'Server suspended') ?>
                            </div>
                        <?php endif; ?>
                        <div class="server-actions">
                            <?php if ($server['status'] === 'suspended'): ?>
                                <form method="post" action="unsuspend_server.php" style="display: inline;">
                                    <input type="hidden" name="server_id" value="<?= htmlspecialchars($server['id']) ?>">
                                    <button type="submit" class="btn btn-sm btn-primary">▶️ Unsuspend</button>
                                </form>
                            <?php endif; ?>
                            <a href="https://panel.enderbit.com/server/<?= htmlspecialchars($server['pterodactyl_id']) ?>" target="_blank" class="btn btn-sm">
                                🎮 Manage
                            </a>
                            <a href="server_details.php?id=<?= htmlspecialchars($server['id']) ?>" class="btn btn-sm">
                                📊 Details
                            </a>
                            <button onclick="deleteServer('<?= htmlspecialchars($server['id']) ?>')" class="btn btn-sm" style="background: var(--red);">
                                🗑️ Delete
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Recent Transactions -->
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                <h2>💳 Recent Transactions</h2>
                <a href="transactions.php" style="font-size: 14px; color: var(--accent);">View all →</a>
            </div>
            
            <?php if (empty($recentTransactions)): ?>
                <p style="text-align: center; color: var(--muted); padding: 40px;">No transactions yet</p>
            <?php else: ?>
                <?php foreach ($recentTransactions as $txn): ?>
                    <div class="transaction-item">
                        <div>
                            <div style="font-weight: 500;"><?= htmlspecialchars($txn['description']) ?></div>
                            <div style="font-size: 13px; color: var(--muted); margin-top: 4px;">
                                <?= date('M j, Y g:i A', $txn['timestamp']) ?>
                            </div>
                        </div>
                        <div class="transaction-amount-<?= $txn['type'] ?>">
                            <?= $txn['amount'] > 0 ? '+' : '' ?><?= EnderBitCredits::formatCredits($txn['amount']) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function deleteServer(serverId) {
            if (confirm('Are you sure you want to delete this server? This action cannot be undone.')) {
                window.location.href = 'delete_server.php?id=' + serverId;
            }
        }
    </script>
</body>
</html>
