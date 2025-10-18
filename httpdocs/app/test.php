<?php
/**
 * Test page to verify app is working
 * Access at: enderbit.com/app/test.php
 */
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>App Test - EnderBit</title>
    <link rel="stylesheet" href="/style.css">
    <style>
        .test-container {
            max-width: 800px;
            margin: 80px auto;
            padding: 40px;
            background: var(--card);
            border-radius: 12px;
            border: 2px solid var(--border);
        }
        .status-ok {
            color: var(--green);
            font-size: 48px;
            margin-bottom: 20px;
        }
        .test-info {
            background: var(--input-bg);
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            font-family: monospace;
            font-size: 14px;
        }
        .btn-group {
            display: flex;
            gap: 12px;
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <div class="test-container">
        <div class="status-ok">✅</div>
        <h1>App Portal is Working!</h1>
        <p style="color: var(--muted); margin-bottom: 30px;">
            The EnderBit app portal is successfully running at <code>enderbit.com/app/</code>
        </p>

        <div class="test-info">
            <strong>PHP Version:</strong> <?= phpversion() ?><br>
            <strong>Server Time:</strong> <?= date('Y-m-d H:i:s') ?><br>
            <strong>Session Status:</strong> <?= session_status() === PHP_SESSION_ACTIVE ? 'Active' : 'Inactive' ?><br>
            <strong>App Directory:</strong> <?= __DIR__ ?>
        </div>

        <h3>Configuration Status:</h3>
        <div class="test-info">
            <?php
            $configFile = __DIR__ . '/config.php';
            if (file_exists($configFile)) {
                echo "✅ <strong>config.php:</strong> Found<br>";
                $config = require $configFile;
                echo "✅ <strong>Credits file:</strong> " . $config['credits_file'] . "<br>";
                echo "✅ <strong>Servers file:</strong> " . $config['servers_file'] . "<br>";
                echo "✅ <strong>AyeT Studios:</strong> " . ($config['ayetstudios']['enabled'] ? 'Enabled' : 'Disabled') . "<br>";
            } else {
                echo "❌ <strong>config.php:</strong> Not found!";
            }
            ?>
        </div>

        <h3>Required Files:</h3>
        <div class="test-info">
            <?php
            $files = ['index.php', 'credits.php', 'billing.php', 'pterodactyl_api.php', 'ayetstudios.php'];
            foreach ($files as $file) {
                $exists = file_exists(__DIR__ . '/' . $file);
                echo ($exists ? '✅' : '❌') . " <strong>$file:</strong> " . ($exists ? 'Found' : 'Missing') . "<br>";
            }
            ?>
        </div>

        <div class="btn-group">
            <a href="index.php" class="btn btn-primary">Go to Dashboard</a>
            <a href="/" class="btn">Main Site</a>
            <a href="test.php" class="btn btn-secondary">Refresh Test</a>
        </div>

        <p style="margin-top: 30px; font-size: 14px; color: var(--muted);">
            <strong>Note:</strong> The dashboard requires login. You'll be redirected to the main site to authenticate.
        </p>
    </div>
</body>
</html>
