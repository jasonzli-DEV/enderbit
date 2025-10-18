<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/logger.php';

// Get token from URL
$token = $_GET['token'] ?? '';
if (!$token) {
    EnderBitLogger::logSecurity('INVALID_VERIFICATION_LINK', 'LOW', ['reason' => 'No token provided']);
    header("Location: signup.php?msg=" . urlencode("Invalid verification link") . "&type=error");
    exit;
}

$tokensFile = __DIR__ . '/tokens.json';
if (!file_exists($tokensFile)) {
    header("Location: signup.php?msg=" . urlencode("Token not found") . "&type=error");
    exit;
}
$tokens = json_decode(file_get_contents($tokensFile), true);
if (!is_array($tokens)) $tokens = [];

// Load settings
$settingsFile = __DIR__ . '/settings.json';
$settings = file_exists($settingsFile) ? json_decode(file_get_contents($settingsFile), true) : [
    'require_email_verify' => true,
    'require_admin_approve' => false
];

$requireEmail = !empty($settings['require_email_verify']) && $settings['require_email_verify'] !== false && $settings['require_email_verify'] !== 'false' && $settings['require_email_verify'] !== '';
$requireAdmin = !empty($settings['require_admin_approve']) && $settings['require_admin_approve'] !== false && $settings['require_admin_approve'] !== 'false' && $settings['require_admin_approve'] !== '';

// Search for matching token
$foundIndex = -1;
for ($i = 0; $i < count($tokens); $i++) {
    if (isset($tokens[$i]['token']) && hash_equals($tokens[$i]['token'], $token)) {
        $foundIndex = $i;
        break;
    }
}

if ($foundIndex === -1) {
    EnderBitLogger::logSecurity('INVALID_VERIFICATION_TOKEN', 'MEDIUM', ['token' => substr($token, 0, 8) . '...']);
    header("Location: signup.php?msg=" . urlencode("Invalid or expired verification token") . "&type=error");
    exit;
}

$user = $tokens[$foundIndex];

// Mark as verified
$tokens[$foundIndex]['verified'] = true;

EnderBitLogger::logRegistration('EMAIL_VERIFIED', $user['email'], [
    'username' => $user['username'] ?? 'N/A',
    'require_admin_approval' => $requireAdmin
]);

// Case 1: No admin approval required → Create user immediately
if (!$requireAdmin) {
    // Decode stored password
    $password = isset($user['password_plain']) ? base64_decode($user['password_plain']) : '';

    // Create user on Pterodactyl
    $result = create_user_on_ptero([
        'first' => $user['first'],
        'last'  => $user['last'],
        'username' => isset($user['username']) ? $user['username'] : '',
        'email' => $user['email'],
        'password' => $password
    ]);

    // Create app portal account and grant signup credits
    require_once __DIR__ . '/../app/credits.php';
    $userId = $user['email'];
    EnderBitCredits::grantSignupBonus($userId);
    
    // Log them in automatically
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_username'] = $user['username'] ?? '';

    // Remove from pending list
    array_splice($tokens, $foundIndex, 1);
    if (file_put_contents($tokensFile, json_encode($tokens, JSON_PRETTY_PRINT)) === false) {
        EnderBitLogger::logSystem('TOKENS_FILE_WRITE_FAILED', ['action' => 'email_verification', 'email' => $user['email']]);
    }

    if ($result) {
        EnderBitLogger::logRegistration('USER_CREATED_AFTER_VERIFICATION', $user['email'], ['username' => $user['username'] ?? 'N/A']);
        header("Location: signup.php?msg=" . urlencode("Email verified — account created successfully! You received 100 free credits!") . "&type=success");
        exit;
    } else {
        EnderBitLogger::logSystem('USER_CREATION_FAILED_AFTER_VERIFICATION', ['email' => $user['email']]);
        header("Location: signup.php?msg=" . urlencode("Email verified, but user creation failed.") . "&type=error");
        exit;
    }
}

// Case 2: Admin approval required → Leave user pending
$tokens[$foundIndex]['approved'] = false;
if (file_put_contents($tokensFile, json_encode($tokens, JSON_PRETTY_PRINT)) === false) {
    EnderBitLogger::logSystem('TOKENS_FILE_WRITE_FAILED', ['action' => 'email_verification_admin_approval', 'email' => $user['email']]);
    header("Location: signup.php?msg=" . urlencode("Verification failed - please try again") . "&type=error");
    exit;
}

EnderBitLogger::logRegistration('EMAIL_VERIFIED_AWAITING_APPROVAL', $user['email'], ['username' => $user['username'] ?? 'N/A']);
header("Location: signup.php?msg=" . urlencode("Email verified — awaiting admin approval") . "&type=success");
exit;
?>