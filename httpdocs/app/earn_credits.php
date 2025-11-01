<?php
session_start();
require_once __DIR__ . '/credits.php';
require_once __DIR__ . '/cpxresearch.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: https://enderbit.com/');
    exit;
}

$userId = $_SESSION['user_id'];
$balance = EnderBitCredits::getBalance($userId);
$surveyWallUrl = CPXResearch::getSurveyWallUrl($userId);
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Earn Credits - EnderBit</title>
    <link rel="stylesheet" href="/style.css">
    <style>
        .earn-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 24px;
            margin-bottom: 32px;
        }
        .earn-card {
            background: var(--card);
            border: 2px solid var(--border);
            border-radius: 12px;
            padding: 24px;
            text-align: center;
            transition: all 0.2s;
        }
        .earn-card:hover {
            border-color: var(--accent);
            transform: translateY(-2px);
        }
        .earn-icon {
            font-size: 48px;
            margin-bottom: 16px;
        }
        .earn-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 12px;
        }
        .earn-description {
            color: var(--muted);
            font-size: 14px;
            margin-bottom: 20px;
            line-height: 1.6;
        }
        .offerwall-container {
            background: var(--card);
            border-radius: 12px;
            padding: 24px;
            border: 1px solid var(--border);
        }
        .stat-highlight {
            background: linear-gradient(135deg, var(--accent) 0%, #0066cc 100%);
            color: white;
            padding: 32px;
            border-radius: 16px;
            text-align: center;
            margin-bottom: 32px;
        }
        .stat-highlight h2 {
            font-size: 48px;
            margin: 0 0 8px 0;
        }
        .stat-highlight p {
            margin: 0;
            opacity: 0.9;
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

        <h1>⚡ Earn Free Credits</h1>
        <p style="color: var(--muted); margin-bottom: 32px;">
            Complete surveys to earn credits for your servers!
        </p>

        <!-- Current Balance -->
        <div class="stat-highlight">
            <h2><?= EnderBitCredits::formatCredits($balance) ?></h2>
            <p>Your Current Balance</p>
        </div>

        <!-- Earning Methods -->
        <div class="earn-grid">
            <div class="earn-card">
                <div class="earn-icon">📝</div>
                <div class="earn-title">Take Surveys</div>
                <div class="earn-description">
                    Share your opinion in surveys and earn credits for your time
                </div>
                <div style="color: var(--accent); font-size: 18px; font-weight: 600; margin-bottom: 12px;">
                    Earn ⚡50-500 per survey
                </div>
                <a href="#surveywall" class="btn btn-primary">Start Survey</a>
            </div>

            <div class="earn-card">
                <div class="earn-icon">⚡</div>
                <div class="earn-title">Quick & Easy</div>
                <div class="earn-description">
                    Surveys are quick to complete and credits are awarded instantly
                </div>
                <div style="color: var(--accent); font-size: 18px; font-weight: 600; margin-bottom: 12px;">
                    Average: ⚡200 per survey
                </div>
                <a href="#surveywall" class="btn btn-primary">Get Started</a>
            </div>

            <div class="earn-card">
                <div class="earn-icon">🎯</div>
                <div class="earn-title">Unlimited Earnings</div>
                <div class="earn-description">
                    Complete as many surveys as you want - no daily limits!
                </div>
                <div style="color: var(--accent); font-size: 18px; font-weight: 600; margin-bottom: 12px;">
                    No Limits
                </div>
                <a href="#surveywall" class="btn btn-primary">Start Now</a>
            </div>
        </div>

        <!-- Survey Wall Section -->
        <div class="card" id="surveywall">
            <h2>📝 Survey Wall - Complete & Earn</h2>
            <p style="color: var(--muted); margin-bottom: 24px;">
                Browse available surveys below and start earning credits instantly. Credits are added to your account automatically!
            </p>

            <?php if ($surveyWallUrl): ?>
                <div class="offerwall-container">
                    <?= CPXResearch::getSurveyWallEmbed($userId, '100%', '700px') ?>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 60px 20px; background: var(--input-bg); border-radius: 12px;">
                    <div style="font-size: 48px; margin-bottom: 16px;">⚠️</div>
                    <h3 style="margin-bottom: 12px;">Survey Wall Currently Unavailable</h3>
                    <p style="color: var(--muted);">
                        The survey wall is currently being configured. Please check back soon!
                    </p>
                </div>
            <?php endif; ?>
        </div>

        <!-- How It Works -->
        <div class="card" style="margin-top: 32px;">
            <h2>ℹ️ How It Works</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 24px; margin-top: 20px;">
                <div>
                    <div style="font-size: 32px; margin-bottom: 12px;">1️⃣</div>
                    <h3 style="font-size: 16px; margin-bottom: 8px;">Choose a Survey</h3>
                    <p style="color: var(--muted); font-size: 14px;">
                        Browse the survey wall and select a survey that interests you
                    </p>
                </div>
                <div>
                    <div style="font-size: 32px; margin-bottom: 12px;">2️⃣</div>
                    <h3 style="font-size: 16px; margin-bottom: 8px;">Complete the Survey</h3>
                    <p style="color: var(--muted); font-size: 14px;">
                        Answer questions honestly and completely
                    </p>
                </div>
                <div>
                    <div style="font-size: 32px; margin-bottom: 12px;">3️⃣</div>
                    <h3 style="font-size: 16px; margin-bottom: 8px;">Get Credits Instantly</h3>
                    <p style="color: var(--muted); font-size: 14px;">
                        Credits are added to your account automatically within minutes
                    </p>
                </div>
                <div>
                    <div style="font-size: 32px; margin-bottom: 12px;">4️⃣</div>
                    <h3 style="font-size: 16px; margin-bottom: 8px;">Create Servers</h3>
                    <p style="color: var(--muted); font-size: 14px;">
                        Use your credits to create and run game servers
                    </p>
                </div>
            </div>
        </div>

        <!-- Tips -->
        <div class="card" style="margin-top: 32px; background: rgba(10, 132, 255, 0.1); border-color: var(--accent);">
            <h2>💡 Pro Tips</h2>
            <ul style="margin: 16px 0; padding-left: 24px; color: var(--muted); line-height: 1.8;">
                <li>Answer surveys honestly for better matching</li>
                <li>Check back daily for new surveys</li>
                <li>Complete your profile for more survey opportunities</li>
                <li>Credits typically appear within 5-15 minutes after completion</li>
                <li>Contact support if credits don't appear after 24 hours</li>
            </ul>
        </div>
    </div>

    <script>
        // Auto-refresh balance every 30 seconds to show new credits
        setInterval(function() {
            fetch('get_balance.php')
                .then(response => response.json())
                .then(data => {
                    if (data.balance !== undefined) {
                        location.reload(); // Reload to show updated balance
                    }
                });
        }, 30000);
    </script>
</body>
</html>
