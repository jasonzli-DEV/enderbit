<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/logger.php';
require_once __DIR__ . '/security.php';

// Set security headers
EnderBitSecurity::setSecurityHeaders();

$error = '';
$success = '';

// Check if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: /app/');
    exit;
}

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password';
    } else {
        // Load users from Pterodactyl panel (or local storage)
        // For now, we'll authenticate against the email
        // In production, you'd validate against Pterodactyl API
        
        // Simple validation - check if user exists in app credits
        require_once __DIR__ . '/app/credits.php';
        $balance = EnderBitCredits::getBalance($email);
        
        if ($balance >= 0) {
            // User exists in system
            $_SESSION['user_id'] = $email;
            $_SESSION['user_email'] = $email;
            $_SESSION['user_username'] = explode('@', $email)[0];
            
            EnderBitLogger::logSystem('USER_LOGIN_SUCCESS', ['email' => $email]);
            
            // Redirect to app portal
            header('Location: /app/');
            exit;
        } else {
            $error = 'Invalid email or password';
            EnderBitLogger::logSecurity('LOGIN_FAILED', 'MEDIUM', ['email' => $email, 'reason' => 'User not found']);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - EnderBit</title>
    <link rel="stylesheet" href="/style.css">
    <style>
        .login-container {
            max-width: 450px;
            margin: 80px auto;
            padding: 40px;
            background: var(--card);
            border-radius: 16px;
            border: 1px solid var(--border);
        }
        .login-header {
            text-align: center;
            margin-bottom: 32px;
        }
        .login-header h1 {
            font-size: 32px;
            margin-bottom: 8px;
            background: linear-gradient(135deg, var(--accent), #a78bfa);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .login-header p {
            color: var(--muted);
            font-size: 14px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text);
        }
        .form-group input {
            width: 100%;
            padding: 12px 16px;
            background: var(--input-bg);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--text);
            font-size: 14px;
        }
        .form-group input:focus {
            outline: none;
            border-color: var(--accent);
        }
        .error-message {
            background: rgba(255, 69, 58, 0.15);
            color: var(--red);
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .success-message {
            background: rgba(76, 217, 100, 0.15);
            color: var(--green);
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .btn-login {
            width: 100%;
            padding: 14px;
            background: var(--accent);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: opacity 0.2s;
        }
        .btn-login:hover {
            opacity: 0.9;
        }
        .login-footer {
            text-align: center;
            margin-top: 24px;
            padding-top: 24px;
            border-top: 1px solid var(--border);
        }
        .login-footer a {
            color: var(--accent);
            text-decoration: none;
        }
        .login-footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>Welcome Back! 🎮</h1>
            <p>Log in to access your client portal</p>
        </div>

        <?php if ($error): ?>
            <div class="error-message">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success-message">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    required 
                    autocomplete="email"
                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                >
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    required 
                    autocomplete="current-password"
                >
            </div>

            <button type="submit" class="btn-login">
                Log In
            </button>
        </form>

        <div class="login-footer">
            <p style="font-size: 14px; color: var(--muted); margin-bottom: 12px;">
                Don't have an account? <a href="/signup.php">Sign up for free</a>
            </p>
            <p style="font-size: 14px; color: var(--muted);">
                <a href="/">← Back to home</a>
            </p>
        </div>
    </div>
</body>
</html>
