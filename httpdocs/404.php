<?php
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page Not Found | EnderBit</title>
    <link rel="icon" type="image/png" href="/icon.png" sizes="96x96">
    <style>
        :root {
            --bg:#0d1117;
            --card:#161b22;
            --accent:#58a6ff;
            --primary:#1f6feb;
            --muted:#8b949e;
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
            background: linear-gradient(135deg, var(--accent) 0%, var(--primary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1;
            margin-bottom: 20px;
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }

        .error-icon {
            font-size: 80px;
            margin-bottom: 20px;
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }

        h1 {
            color: var(--accent);
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

        .error-details code {
            color: var(--accent);
            font-family: 'Monaco', 'Courier New', monospace;
            font-size: 14px;
            word-break: break-all;
        }

        .error-details p {
            font-size: 14px;
            margin-bottom: 8px;
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

        .suggestions {
            margin-top: 40px;
            text-align: left;
            background: var(--card);
            border: 1px solid var(--input-border);
            border-radius: 12px;
            padding: 24px;
        }

        .suggestions h3 {
            color: var(--accent);
            font-size: 18px;
            margin-bottom: 16px;
        }

        .suggestions ul {
            list-style: none;
            padding: 0;
        }

        .suggestions li {
            padding: 8px 0;
            color: var(--muted);
            font-size: 14px;
        }

        .suggestions li:before {
            content: "→ ";
            color: var(--accent);
            font-weight: bold;
            margin-right: 8px;
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
        <div class="error-icon">🔍</div>
        <div class="error-code">404</div>
        <h1>Page Not Found</h1>
        <p>Sorry, the page you're looking for doesn't exist or has been moved.</p>

        <div class="error-details">
            <p><strong>Requested URL:</strong></p>
            <p><code><?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? '/') ?></code></p>
        </div>

        <div class="btn-group">
            <a href="/" class="btn btn-primary">🏠 Go Home</a>
            <a href="javascript:history.back()" class="btn btn-secondary">← Go Back</a>
        </div>

        <div class="suggestions">
            <h3>💡 Suggestions</h3>
            <ul>
                <li>Check the URL for typos</li>
                <li>Try searching from the home page</li>
                <li>Visit our <a href="/faq.php" style="color:var(--accent);">FAQ page</a> for common questions</li>
                <li>Contact <a href="/support.php" style="color:var(--accent);">support</a> if you need help</li>
            </ul>
        </div>
    </div>
</body>
</html>
