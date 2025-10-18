<?php
session_start();
require_once __DIR__ . '/logger.php';

// Log the logout
if (isset($_SESSION['user_email'])) {
    EnderBitLogger::logSystem('USER_LOGOUT', ['email' => $_SESSION['user_email']]);
}

// Destroy session
session_destroy();

// Redirect to home
header('Location: /?msg=' . urlencode('You have been logged out') . '&type=success');
exit;
