<?php
http_response_code(500);
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 - Internal Server Error | EnderBit</title>
    <link rel="icon" type="image/png" href="/icon.png" sizes="96x96">
    <style>
        :root {
            --bg:#0d1117;
            --card:#161b22;
            --accent:#58a6ff;
            --primary:#1f6feb;
            --muted:#8b949e;
            --red:#f85149;
            --text:#e6eef8;
            --input-border:#232629;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .error-container {
            text-align: center;
            max-width: 600px;
            width: 100%;
        }

        .error-code {
            font-size: 120px;
            font-weight: 900;
            background: linear-gradient(135deg, var(--red) 0%, #dc2626 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1;
            margin-bottom: 20px;
            animation: glitch 1s infinite;
        }

        @keyframes glitch {
            0%, 100% { transform: translate(0); }
            20% { transform: translate(-2px, 2px); }
            40% { transform: translate(-2px, -2px); }
            60% { transform: translate(2px, 2px); }
            80% { transform: translate(2px, -2px); }
        }

        .error-icon {
            font-size: 80px;
            margin-bottom: 20px;
            animation: shake 0.5s infinite;
        }

        @keyframes shake {
            0%, 100% { transform: rotate(0deg); }
            25% { transform: rotate(-5deg); }
            75% { transform: rotate(5deg); }
        }

        h1 {
            color: var(--red);
            font-size: 32px;
            margin-bottom: 16px;
        }

        p {
            color: var(--muted);
            font-size: 18px;
            line-height: 1.6;
            margin-bottom: 32px;
        }

        .error-details {
            background: var(--card);
            border: 1px solid var(--input-border);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 32px;
            text-align: left;
        }

        .error-details h3 {
            color: var(--accent);
            font-size: 16px;
            margin-bottom: 12px;
        }

        .error-details p {
            font-size: 14px;
            margin-bottom: 8px;
            color: var(--muted);
        }

        .error-details p:last-child {
            margin-bottom: 0;
        }

        .btn-group {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-block;
            padding: 14px 28px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            text-decoration: none;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
        }

        .btn-primary {
            background: var(--primary);
            color: #fff;
        }

        .btn-secondary {
            background: var(--card);
            color: var(--text);
            border: 1px solid var(--input-border);
        }

        .btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }

        .support-info {
            margin-top: 40px;
            text-align: left;
            background: var(--card);
            border: 1px solid var(--input-border);
            border-radius: 12px;
            padding: 24px;
        }

        .support-info h3 {
            color: var(--accent);
            font-size: 18px;
            margin-bottom: 16px;
        }

        .support-info p {
            font-size: 14px;
            margin-bottom: 12px;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            background: rgba(248, 81, 73, 0.1);
            border: 1px solid var(--red);
            border-radius: 6px;
            color: var(--red);
            font-size: 12px;
            font-weight: 600;
            margin-top: 8px;
        }

        @media (max-width: 768px) {
            .error-code {
                font-size: 80px;
            }

            .error-icon {
                font-size: 60px;
            }

            h1 {
                font-size: 24px;
            }

            p {
                font-size: 16px;
            }

            .btn {
                padding: 12px 24px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">⚠️</div>
        <div class="error-code">500</div>
        <h1>Internal Server Error</h1>
        <p>Oops! Something went wrong on our end. Our team has been notified and we're working to fix it.</p>

        <div class="error-details">
            <h3>What happened?</h3>
            <p>The server encountered an unexpected condition that prevented it from fulfilling your request.</p>
            <p><span class="status-badge">⚠️ Server Error</span></p>
        </div>

        <div class="btn-group">
            <a href="/" class="btn btn-primary">🏠 Go Home</a>
            <a href="javascript:location.reload()" class="btn btn-secondary">🔄 Try Again</a>
        </div>

        <div class="support-info">
            <h3>💬 Need Help?</h3>
            <p>If this problem persists, please contact our support team:</p>
            <p>
                <a href="/support.php" class="btn btn-secondary" style="display:inline-block; margin-top:12px;">
                    📧 Contact Support
                </a>
            </p>
            <p style="font-size:12px; color:var(--muted); margin-top:16px;">
                Error Time: <?= date('Y-m-d H:i:s T') ?><br>
                Request ID: <?= substr(md5(uniqid()), 0, 8) ?>
            </p>
        </div>
    </div>
</body>
</html>
