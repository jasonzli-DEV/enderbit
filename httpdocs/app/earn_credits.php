<?php
session_start();
require_once __DIR__ . '/credits.php';
require_once __DIR__ . '/ayetstudios.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: https://enderbit.com/');
    exit;
}

$userId = $_SESSION['user_id'];
$balance = EnderBitCredits::getBalance($userId);
$offerwallUrl = AyeTStudios::getOfferwallUrl($userId);
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
            Complete offers, watch videos, and participate in surveys to earn credits for your servers!
        </p>

        <!-- Current Balance -->
        <div class="stat-highlight">
            <h2><?= EnderBitCredits::formatCredits($balance) ?></h2>
            <p>Your Current Balance</p>
        </div>

        <!-- Earning Methods -->
        <div class="earn-grid">
            <div class="earn-card">
                <div class="earn-icon">🎁</div>
                <div class="earn-title">Complete Offers</div>
                <div class="earn-description">
                    Download apps, sign up for services, and complete simple tasks to earn credits
                </div>
                <div style="color: var(--accent); font-size: 18px; font-weight: 600; margin-bottom: 12px;">
                    Earn up to ⚡1,000 per offer
                </div>
                <a href="#offerwall" class="btn btn-primary">Start Earning</a>
            </div>

            <div class="earn-card">
                <div class="earn-icon">📺</div>
                <div class="earn-title">Watch Videos</div>
                <div class="earn-description">
                    Watch short video ads and earn credits instantly. Quick and easy!
                </div>
                <div style="color: var(--accent); font-size: 18px; font-weight: 600; margin-bottom: 12px;">
                    Earn ⚡5-20 per video
                </div>
                <a href="#offerwall" class="btn btn-primary">Watch Now</a>
            </div>

            <div class="earn-card">
                <div class="earn-icon">📝</div>
                <div class="earn-title">Take Surveys</div>
                <div class="earn-description">
                    Share your opinion in surveys and earn credits for your time
                </div>
                <div style="color: var(--accent); font-size: 18px; font-weight: 600; margin-bottom: 12px;">
                    Earn ⚡50-500 per survey
                </div>
                <a href="#offerwall" class="btn btn-primary">Start Survey</a>
            </div>
        </div>

        <!-- Offerwall Section -->
        <div class="card" id="offerwall">
            <h2>🎯 Offerwall - Complete & Earn</h2>
            <p style="color: var(--muted); margin-bottom: 24px;">
                Browse available offers below and start earning credits instantly. Credits are added to your account automatically!
            </p>

            <?php if ($offerwallUrl): ?>
                <div class="offerwall-container">
                    <?= AyeTStudios::getOfferwallEmbed($userId, '100%', '700px') ?>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 60px 20px; background: var(--input-bg); border-radius: 12px;">
                    <div style="font-size: 48px; margin-bottom: 16px;">⚠️</div>
                    <h3 style="margin-bottom: 12px;">Offerwall Currently Unavailable</h3>
                    <p style="color: var(--muted);">
                        The offerwall is currently being configured. Please check back soon!
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
                    <h3 style="font-size: 16px; margin-bottom: 8px;">Choose an Offer</h3>
                    <p style="color: var(--muted); font-size: 14px;">
                        Browse the offerwall and select an offer that interests you
                    </p>
                </div>
                <div>
                    <div style="font-size: 32px; margin-bottom: 12px;">2️⃣</div>
                    <h3 style="font-size: 16px; margin-bottom: 8px;">Complete the Task</h3>
                    <p style="color: var(--muted); font-size: 14px;">
                        Follow the instructions to complete the offer requirements
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
                <li>Complete high-value offers for maximum credits</li>
                <li>Check back daily for new offers</li>
                <li>Read offer requirements carefully before starting</li>
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
