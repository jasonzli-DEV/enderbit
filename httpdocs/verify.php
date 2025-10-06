<?php
session_start();
require_once __DIR__ . '/config.php';

// Get token from URL
$token = $_GET['token'] ?? '';
if (!$token) {
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
    header("Location: signup.php?msg=" . urlencode("Invalid or expired verification token") . "&type=error");
    exit;
}

$user = $tokens[$foundIndex];

// Mark as verified
$tokens[$foundIndex]['verified'] = true;

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

    // Remove from pending list
    array_splice($tokens, $foundIndex, 1);
    if (file_put_contents($tokensFile, json_encode($tokens, JSON_PRETTY_PRINT)) === false) {
        error_log("Failed to update tokens file after verification");
    }

    if ($result) {
        header("Location: signup.php?msg=" . urlencode("Email verified — account created successfully!") . "&type=success");
        exit;
    } else {
        header("Location: signup.php?msg=" . urlencode("Email verified, but user creation failed.") . "&type=error");
        exit;
    }
}

// Case 2: Admin approval required → Leave user pending
$tokens[$foundIndex]['approved'] = false;
if (file_put_contents($tokensFile, json_encode($tokens, JSON_PRETTY_PRINT)) === false) {
    error_log("Failed to update tokens file after verification");
    header("Location: signup.php?msg=" . urlencode("Verification failed - please try again") . "&type=error");
    exit;
}

header("Location: signup.php?msg=" . urlencode("Email verified — awaiting admin approval") . "&type=success");
exit;
?>