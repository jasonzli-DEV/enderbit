<?php
require_once 'config.php';

function send_verification_email($to, $token) {
    global $config;
    $subject = "Verify your Enderbit account";
    // Generate dynamic URL
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'enderbit.com';
    $verifyLink = $protocol . '://' . $host . '/verify.php?token=' . urlencode($token);

    $message = "
    <html>
    <body style='font-family: Arial, sans-serif;'>
        <h2>Verify Your Account</h2>
        <p>Click below to verify your Enderbit account:</p>
        <p><a href='$verifyLink'>$verifyLink</a></p>
    </body>
    </html>";

    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8\r\n";
    $headers .= "From: ".$config['smtp']['from_name']." <".$config['smtp']['from_email'].">\r\n";

    return mail($to, $subject, $message, $headers);
}