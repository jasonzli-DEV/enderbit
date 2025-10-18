<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/credits.php';
require_once __DIR__ . '/pterodactyl_api.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /httpdocs/index.php');
    exit;
}

$userId = $_SESSION['user_id'];
$balance = EnderBitCredits::getBalance($userId);
$config = require __DIR__ . '/config.php';

$error = '';
$success = '';

// Handle server creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_server'])) {
    $serverName = trim($_POST['server_name'] ?? '');
    $game = $_POST['game'] ?? '';
    $plan = $_POST['plan'] ?? '';
    
    if (empty($serverName) || empty($game) || empty($plan)) {
        $error = 'Please fill in all fields';
    } elseif (!isset($config['server_pricing'][$game][$plan])) {
        $error = 'Invalid game or plan selected';
    } else {
        // Check if user has enough credits for at least 1 hour
        $costPerHour = $config['server_pricing'][$game][$plan]['cost_per_hour'];
        $minimumCredits = $costPerHour * 2; // Require 2 hours minimum
        
        if ($balance < $minimumCredits) {
            $error = "Insufficient credits. You need at least {$minimumCredits} credits ({$costPerHour} credits/hour × 2 hours minimum). <a href='earn_credits.php'>Earn more credits</a>";
        } else {
            // Create the server
            $result = PterodactylAPI::createServer($userId, $serverName, $game, $plan);
            
            if ($result['success']) {
                $success = "Server created successfully! Redirecting to dashboard...";
                header('Refresh: 2; URL=index.php');
            } else {
                $error = 'Failed to create server: ' . ($result['error'] ?? 'Unknown error');
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Server - EnderBit</title>
    <link rel="stylesheet" href="/httpdocs/style.css">
    <style>
        .plan-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .plan-card {
            background: var(--card);
            border: 2px solid var(--border);
            border-radius: 12px;
            padding: 24px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .plan-card:hover {
            border-color: var(--accent);
            transform: translateY(-2px);
        }
        .plan-card.selected {
            border-color: var(--accent);
            background: rgba(10, 132, 255, 0.1);
        }
        .plan-name {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 12px;
        }
        .plan-price {
            font-size: 28px;
            font-weight: 700;
            color: var(--accent);
            margin-bottom: 16px;
        }
        .plan-specs {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .plan-specs li {
            padding: 8px 0;
            border-bottom: 1px solid var(--border);
            font-size: 14px;
            color: var(--muted);
        }
        .plan-specs li:last-child {
            border-bottom: none;
        }
        .game-option {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px;
            background: var(--card);
            border: 2px solid var(--border);
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.2s;
            margin-bottom: 12px;
        }
        .game-option:hover {
            border-color: var(--accent);
        }
        .game-option.selected {
            border-color: var(--accent);
            background: rgba(10, 132, 255, 0.1);
        }
        .game-icon {
            font-size: 32px;
        }
        .game-name {
            font-weight: 600;
            font-size: 16px;
        }
    </style>
</head>
<body>
    <div class="container" style="max-width: 900px; margin: 40px auto;">
        <div style="margin-bottom: 32px;">
            <a href="index.php" style="color: var(--accent); text-decoration: none; font-size: 14px;">
                ← Back to Dashboard
            </a>
        </div>

        <h1>🚀 Create New Server</h1>
        <p style="color: var(--muted); margin-bottom: 32px;">
            Your current balance: <strong style="color: var(--accent); font-size: 18px;"><?= EnderBitCredits::formatCredits($balance) ?></strong>
        </p>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>

        <form method="post" id="createServerForm">
            <!-- Server Name -->
            <div class="card" style="margin-bottom: 24px;">
                <h2>📝 Server Name</h2>
                <div class="form-group">
                    <label for="server_name">Give your server a name</label>
                    <input type="text" id="server_name" name="server_name" required 
                           placeholder="e.g., My Awesome Server" 
                           value="<?= htmlspecialchars($_POST['server_name'] ?? '') ?>">
                </div>
            </div>

            <!-- Game Selection -->
            <div class="card" style="margin-bottom: 24px;">
                <h2>🎮 Select Game</h2>
                <div id="gameSelection">
                    <?php foreach ($config['games'] as $gameKey => $gameName): ?>
                        <?php if (isset($config['server_pricing'][$gameKey])): ?>
                            <label class="game-option" data-game="<?= $gameKey ?>">
                                <input type="radio" name="game" value="<?= $gameKey ?>" 
                                       style="display: none;" required
                                       <?= ($_POST['game'] ?? '') === $gameKey ? 'checked' : '' ?>>
                                <div class="game-icon">🎮</div>
                                <div class="game-name"><?= htmlspecialchars($gameName) ?></div>
                            </label>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Plan Selection -->
            <div class="card" style="margin-bottom: 32px;">
                <h2>📦 Select Plan</h2>
                <div id="planSelection" class="plan-grid">
                    <!-- Plans will be populated by JavaScript based on game selection -->
                </div>
                <input type="hidden" name="plan" id="selectedPlan">
            </div>

            <div style="display: flex; gap: 16px; justify-content: flex-end;">
                <a href="index.php" class="btn">Cancel</a>
                <button type="submit" name="create_server" class="btn btn-primary" id="createBtn" disabled>
                    🚀 Create Server
                </button>
            </div>
        </form>
    </div>

    <script>
        const pricing = <?= json_encode($config['server_pricing']) ?>;
        const balance = <?= $balance ?>;
        
        const gameOptions = document.querySelectorAll('.game-option');
        const planSelection = document.getElementById('planSelection');
        const selectedPlanInput = document.getElementById('selectedPlan');
        const createBtn = document.getElementById('createBtn');
        
        let selectedGame = '<?= $_POST['game'] ?? '' ?>';
        let selectedPlan = '<?= $_POST['plan'] ?? '' ?>';
        
        // Game selection
        gameOptions.forEach(option => {
            option.addEventListener('click', function() {
                gameOptions.forEach(opt => opt.classList.remove('selected'));
                this.classList.add('selected');
                selectedGame = this.dataset.game;
                this.querySelector('input').checked = true;
                loadPlans(selectedGame);
                checkFormValid();
            });
            
            if (option.dataset.game === selectedGame) {
                option.classList.add('selected');
                loadPlans(selectedGame);
            }
        });
        
        function loadPlans(game) {
            if (!pricing[game]) return;
            
            planSelection.innerHTML = '';
            const plans = pricing[game];
            
            for (const [planKey, plan] of Object.entries(plans)) {
                const canAfford = balance >= (plan.cost_per_hour * 2);
                const planCard = document.createElement('div');
                planCard.className = 'plan-card' + (planKey === selectedPlan ? ' selected' : '');
                planCard.dataset.plan = planKey;
                planCard.style.opacity = canAfford ? '1' : '0.5';
                planCard.style.cursor = canAfford ? 'pointer' : 'not-allowed';
                
                planCard.innerHTML = `
                    <div class="plan-name">${plan.name.split('(')[0]}</div>
                    <div class="plan-price">⚡${plan.cost_per_hour}/hr</div>
                    <ul class="plan-specs">
                        <li>💾 RAM: ${(plan.ram / 1024).toFixed(1)}GB</li>
                        <li>⚙️ CPU: ${plan.cpu}%</li>
                        <li>💿 Disk: ${(plan.disk / 1000).toFixed(1)}GB</li>
                        <li>⏱️ Min: ${plan.cost_per_hour * 2} credits (2h)</li>
                    </ul>
                    ${!canAfford ? '<div style="color: var(--red); font-size: 12px; margin-top: 12px; text-align: center;">Insufficient credits</div>' : ''}
                `;
                
                if (canAfford) {
                    planCard.addEventListener('click', function() {
                        document.querySelectorAll('.plan-card').forEach(card => card.classList.remove('selected'));
                        this.classList.add('selected');
                        selectedPlan = this.dataset.plan;
                        selectedPlanInput.value = selectedPlan;
                        checkFormValid();
                    });
                }
                
                planSelection.appendChild(planCard);
            }
        }
        
        function checkFormValid() {
            const serverName = document.getElementById('server_name').value.trim();
            createBtn.disabled = !(serverName && selectedGame && selectedPlan);
        }
        
        document.getElementById('server_name').addEventListener('input', checkFormValid);
        
        // Initial check
        if (selectedPlan) {
            selectedPlanInput.value = selectedPlan;
        }
        checkFormValid();
    </script>
</body>
</html>
