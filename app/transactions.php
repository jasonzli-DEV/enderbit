<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/credits.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /httpdocs/index.php');
    exit;
}

$userId = $_SESSION['user_id'];
$balance = EnderBitCredits::getBalance($userId);
$transactions = EnderBitCredits::getTransactions($userId, 100);
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transactions - EnderBit</title>
    <link rel="stylesheet" href="/httpdocs/style.css">
    <style>
        .transaction-table {
            width: 100%;
            border-collapse: collapse;
        }
        .transaction-table th {
            background: var(--input-bg);
            padding: 12px;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid var(--border);
        }
        .transaction-table td {
            padding: 12px;
            border-bottom: 1px solid var(--border);
        }
        .transaction-table tr:hover {
            background: var(--input-bg);
        }
        .amount-credit {
            color: var(--green);
            font-weight: 600;
        }
        .amount-debit {
            color: var(--red);
            font-weight: 600;
        }
        .source-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        .source-purchase {
            background: rgba(76, 217, 100, 0.2);
            color: var(--green);
        }
        .source-ayetstudios {
            background: rgba(10, 132, 255, 0.2);
            color: var(--accent);
        }
        .source-server_billing {
            background: rgba(255, 69, 58, 0.2);
            color: var(--red);
        }
        .source-signup_bonus {
            background: rgba(255, 204, 0, 0.2);
            color: var(--yellow);
        }
        .filter-buttons {
            display: flex;
            gap: 12px;
            margin-bottom: 24px;
        }
        .filter-btn {
            padding: 8px 16px;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .filter-btn:hover {
            border-color: var(--accent);
        }
        .filter-btn.active {
            background: var(--accent);
            border-color: var(--accent);
            color: white;
        }
    </style>
</head>
<body>
    <div class="container" style="max-width: 1200px; margin: 40px auto;">
        <div style="margin-bottom: 32px;">
            <a href="index.php" style="color: var(--accent); text-decoration: none; font-size: 14px;">
                ← Back to Dashboard
            </a>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px;">
            <div>
                <h1>💳 Transaction History</h1>
                <p style="color: var(--muted); margin-top: 8px;">
                    Complete history of all credit transactions
                </p>
            </div>
            <div style="text-align: right;">
                <div style="font-size: 14px; color: var(--muted); margin-bottom: 4px;">Current Balance</div>
                <div style="font-size: 32px; font-weight: 700; color: var(--accent);">
                    <?= EnderBitCredits::formatCredits($balance) ?>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filter-buttons">
            <button class="filter-btn active" data-filter="all">All Transactions</button>
            <button class="filter-btn" data-filter="credit">Credits Added</button>
            <button class="filter-btn" data-filter="debit">Debits</button>
            <button class="filter-btn" data-filter="ayetstudios">AyeT Studios</button>
            <button class="filter-btn" data-filter="server_billing">Server Billing</button>
        </div>

        <!-- Transactions Table -->
        <div class="card">
            <?php if (empty($transactions)): ?>
                <div style="text-align: center; padding: 60px 20px; color: var(--muted);">
                    <div style="font-size: 48px; margin-bottom: 16px;">📊</div>
                    <h3 style="margin-bottom: 12px;">No transactions yet</h3>
                    <p style="margin-bottom: 24px;">Your transaction history will appear here</p>
                    <a href="earn_credits.php" class="btn btn-primary">Earn Your First Credits</a>
                </div>
            <?php else: ?>
                <div style="overflow-x: auto;">
                    <table class="transaction-table">
                        <thead>
                            <tr>
                                <th>Date & Time</th>
                                <th>Description</th>
                                <th>Source</th>
                                <th>Amount</th>
                                <th>Balance After</th>
                            </tr>
                        </thead>
                        <tbody id="transactionTableBody">
                            <?php foreach ($transactions as $txn): ?>
                                <tr data-type="<?= htmlspecialchars($txn['type']) ?>" 
                                    data-source="<?= htmlspecialchars($txn['source']) ?>">
                                    <td style="white-space: nowrap;">
                                        <?= date('M j, Y', $txn['timestamp']) ?><br>
                                        <span style="font-size: 13px; color: var(--muted);">
                                            <?= date('g:i A', $txn['timestamp']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div style="font-weight: 500;">
                                            <?= htmlspecialchars($txn['description']) ?>
                                        </div>
                                        <?php if (!empty($txn['id'])): ?>
                                            <div style="font-size: 12px; color: var(--muted); margin-top: 4px;">
                                                ID: <?= htmlspecialchars($txn['id']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="source-badge source-<?= htmlspecialchars($txn['source']) ?>">
                                            <?= ucfirst(str_replace('_', ' ', htmlspecialchars($txn['source']))) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="amount-<?= htmlspecialchars($txn['type']) ?>">
                                            <?= $txn['amount'] > 0 ? '+' : '' ?><?= EnderBitCredits::formatCredits($txn['amount']) ?>
                                        </span>
                                    </td>
                                    <td style="color: var(--accent); font-weight: 600;">
                                        <?= EnderBitCredits::formatCredits($txn['balance_after']) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Summary Stats -->
        <?php if (!empty($transactions)): ?>
            <?php
                $totalEarned = 0;
                $totalSpent = 0;
                foreach ($transactions as $txn) {
                    if ($txn['type'] === 'credit') {
                        $totalEarned += $txn['amount'];
                    } else {
                        $totalSpent += abs($txn['amount']);
                    }
                }
            ?>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 24px; margin-top: 24px;">
                <div class="card" style="text-align: center;">
                    <div style="font-size: 14px; color: var(--muted); margin-bottom: 8px;">Total Earned</div>
                    <div style="font-size: 32px; font-weight: 700; color: var(--green);">
                        +<?= EnderBitCredits::formatCredits($totalEarned) ?>
                    </div>
                </div>
                <div class="card" style="text-align: center;">
                    <div style="font-size: 14px; color: var(--muted); margin-bottom: 8px;">Total Spent</div>
                    <div style="font-size: 32px; font-weight: 700; color: var(--red);">
                        -<?= EnderBitCredits::formatCredits($totalSpent) ?>
                    </div>
                </div>
                <div class="card" style="text-align: center;">
                    <div style="font-size: 14px; color: var(--muted); margin-bottom: 8px;">Net Balance</div>
                    <div style="font-size: 32px; font-weight: 700; color: var(--accent);">
                        <?= EnderBitCredits::formatCredits($totalEarned - $totalSpent) ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        const filterBtns = document.querySelectorAll('.filter-btn');
        const tableRows = document.querySelectorAll('#transactionTableBody tr');

        filterBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                // Update active button
                filterBtns.forEach(b => b.classList.remove('active'));
                this.classList.add('active');

                const filter = this.dataset.filter;

                // Filter rows
                tableRows.forEach(row => {
                    if (filter === 'all') {
                        row.style.display = '';
                    } else if (filter === 'credit' || filter === 'debit') {
                        row.style.display = row.dataset.type === filter ? '' : 'none';
                    } else {
                        row.style.display = row.dataset.source === filter ? '' : 'none';
                    }
                });
            });
        });
    </script>
</body>
</html>
