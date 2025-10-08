<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/logger.php';

// Inputs
$first = trim($_POST['first'] ?? '');
$last  = trim($_POST['last'] ?? '');
$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$recaptcha = $_POST['g-recaptcha-response'] ?? '';

// Load settings
$settingsFile = __DIR__ . '/settings.json';
$settings = file_exists($settingsFile)
    ? json_decode(file_get_contents($settingsFile), true)
    : ['require_email_verify' => true, 'require_admin_approve' => false];

$requireEmail = !empty($settings['require_email_verify']) && $settings['require_email_verify'] !== false && $settings['require_email_verify'] !== 'false' && $settings['require_email_verify'] !== '';
$requireAdmin = !empty($settings['require_admin_approve']) && $settings['require_admin_approve'] !== false && $settings['require_admin_approve'] !== 'false' && $settings['require_admin_approve'] !== '';

// Debug logging
error_log("[REGISTER] Settings - requireEmail: " . ($requireEmail ? 'true' : 'false') . ", requireAdmin: " . ($requireAdmin ? 'true' : 'false'));
EnderBitLogger::logRegistration('REGISTRATION_ATTEMPT', $email, [
    'username' => $username,
    'require_email' => $requireEmail,
    'require_admin' => $requireAdmin
]);

// Validate input
if (!$first || !$last || !$username || !$email || !$password) {
    EnderBitLogger::logRegistration('REGISTRATION_FAILED', $email, ['reason' => 'Missing fields']);
    header("Location: signup.php?msg=" . urlencode("Missing fields") . "&type=error");
    exit;
}
if (!preg_match('/^[a-zA-Z0-9_\-\.]+$/', $username)) {
    EnderBitLogger::logRegistration('REGISTRATION_FAILED', $email, ['reason' => 'Invalid username format', 'username' => $username]);
    EnderBitLogger::logSecurity('INVALID_USERNAME_FORMAT', 'LOW', ['email' => $email, 'username' => $username]);
    header("Location: signup.php?msg=" . urlencode("Username can only contain letters, numbers, dots, hyphens and underscores") . "&type=error");
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    EnderBitLogger::logRegistration('REGISTRATION_FAILED', $email, ['reason' => 'Invalid email format']);
    EnderBitLogger::logSecurity('INVALID_EMAIL_FORMAT', 'LOW', ['email' => $email]);
    header("Location: signup.php?msg=" . urlencode("Invalid email") . "&type=error");
    exit;
}

// reCAPTCHA check (optional)
if (!empty($config['recaptcha_secret'])) {
    $resp = @file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=" . urlencode($config['recaptcha_secret']) . "&response=" . urlencode($recaptcha));
    $resp = $resp ? json_decode($resp, true) : null;
    if (!$resp || empty($resp['success'])) {
        EnderBitLogger::logRegistration('REGISTRATION_FAILED', $email, ['reason' => 'reCAPTCHA failed']);
        EnderBitLogger::logSecurity('RECAPTCHA_FAILED', 'MEDIUM', ['email' => $email]);
        header("Location: signup.php?msg=" . urlencode("Captcha failed") . "&type=error");
        exit;
    }
}

// Tokens setup
$tokensFile = __DIR__ . '/tokens.json';
if (!file_exists($tokensFile)) {
    if (file_put_contents($tokensFile, json_encode([])) === false) {
        error_log("Failed to create tokens.json file");
        header("Location: signup.php?msg=" . urlencode("System error - please try again") . "&type=error");
        exit;
    }
}
$tokens = json_decode(file_get_contents($tokensFile), true);
if (!is_array($tokens)) $tokens = [];

// Prevent duplicate pending email
foreach ($tokens as $t) {
    if (isset($t['email']) && strcasecmp($t['email'], $email) === 0) {
        header("Location: signup.php?msg=" . urlencode("Email already pending") . "&type=error");
        exit;
    }
}

// ====== CASE LOGIC START ======

// CASE 1: No email verification, no admin approval → create immediately
if (!$requireEmail && !$requireAdmin) {
    error_log("[REGISTER] CASE 1: Creating user immediately for {$email}");
    create_user_on_ptero([
        'first' => $first,
        'last'  => $last,
        'username' => $username,
        'email' => $email,
        'password' => $password
    ]);
    header("Location: signup.php?msg=" . urlencode("Account created successfully") . "&type=success");
    exit;
}

// Prepare token entry (for all other cases)
$token = bin2hex(random_bytes(16));
$entry = [
    'token' => $token,
    'first' => $first,
    'last' => $last,
    'username' => $username,
    'email' => $email,
    'password_plain' => base64_encode($password),
    'verified' => !$requireEmail,  // only true if email verify is off
    'approved' => !$requireAdmin,  // only true if admin approval is off
    'created_at' => time()
];
$tokens[] = $entry;
if (file_put_contents($tokensFile, json_encode($tokens, JSON_PRETTY_PRINT)) === false) {
    error_log("Failed to save token to tokens.json");
    header("Location: signup.php?msg=" . urlencode("Registration failed - please try again") . "&type=error");
    exit;
}

// CASE 2: Email verification required (regardless of admin toggle)
if ($requireEmail) {
    error_log("[REGISTER] CASE 2: Sending verification email to {$email}");
    // send verification email
    $verifyUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http')
        . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']) . '/verify.php?token=' . urlencode($token);

    $subject = "Verify your Enderbit account";
    $body = "<p>Hi {$first},</p><p>Click below to verify your email and finish registration:</p><p><a href=\"{$verifyUrl}\">{$verifyUrl}</a></p>";

    // Try sending email
    $mailSent = false;
    $errorMsg = '';
    
    // Attempt 1: Use SMTP if configured
    if (!empty($config['smtp']['host']) && !empty($config['smtp']['username'])) {
        try {
            $mailSent = send_smtp_email($email, $subject, $body, $config['smtp']);
        } catch (Exception $e) {
            $errorMsg = $e->getMessage();
            file_put_contents(__DIR__ . '/mail_error.log', "[" . date('Y-m-d H:i:s') . "] SMTP failed: {$errorMsg}\n", FILE_APPEND);
        }
    }
    
    // Attempt 2: Fallback to PHP mail() if SMTP failed
    if (!$mailSent) {
        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8\r\n";
        $headers .= "From: {$config['smtp']['from_name']} <{$config['smtp']['from_email']}>\r\n";
        $mailSent = @mail($email, $subject, $body, $headers);
        
        if (!$mailSent) {
            file_put_contents(__DIR__ . '/mail_error.log', "[" . date('Y-m-d H:i:s') . "] Both SMTP and mail() failed for {$email}\n", FILE_APPEND);
        }
    }

    if (!$mailSent) {
        // Remove the token since email failed
        array_pop($tokens);
        file_put_contents($tokensFile, json_encode($tokens, JSON_PRETTY_PRINT));
        header("Location: signup.php?msg=" . urlencode("Failed to send verification email. Please contact support.") . "&type=error");
        exit;
    }

    if ($requireAdmin) {
        $msg = "Verification sent — after verifying, awaiting admin approval";
    } else {
        $msg = "Verification sent — check your email to complete registration";
    }

    header("Location: signup.php?msg=" . urlencode($msg) . "&type=success");
    exit;
}

// CASE 3: Admin approval required but no email verification
if ($requireAdmin && !$requireEmail) {
    header("Location: signup.php?msg=" . urlencode("Registration submitted — awaiting admin approval") . "&type=success");
    exit;
}

// ====== END CASES ======
?>