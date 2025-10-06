<?php
session_start();
require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: support.php");
    exit;
}

$email = trim($_POST['email'] ?? '');
$category = trim($_POST['category'] ?? 'other');
$priority = trim($_POST['priority'] ?? 'medium');
$subject = trim($_POST['subject'] ?? '');
$description = trim($_POST['description'] ?? '');

// Validate input
if (empty($email) || empty($subject) || empty($description) || empty($category) || empty($priority)) {
    header("Location: support.php?msg=" . urlencode("All fields are required") . "&type=error");
    exit;
}

// Validate priority
if (!in_array($priority, ['low', 'medium', 'high', 'urgent'])) {
    $priority = 'medium';
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: support.php?msg=" . urlencode("Invalid email address") . "&type=error");
    exit;
}

// Load or create tickets file
$ticketsFile = __DIR__ . '/tickets.json';
if (!file_exists($ticketsFile)) {
    file_put_contents($ticketsFile, json_encode([], JSON_PRETTY_PRINT));
}

$tickets = json_decode(file_get_contents($ticketsFile), true);
if (!is_array($tickets)) {
    $tickets = [];
}

// Generate unique ticket ID
$ticketId = 'TICKET-' . strtoupper(bin2hex(random_bytes(4)));

// Handle file attachment
$attachmentPath = null;
if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = __DIR__ . '/uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf', 'text/plain', 'application/zip', 'application/x-zip-compressed'];
    $maxSize = 5 * 1024 * 1024; // 5MB
    
    $fileType = $_FILES['attachment']['type'];
    $fileSize = $_FILES['attachment']['size'];
    $fileName = $_FILES['attachment']['name'];
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    
    if (!in_array($fileType, $allowedTypes) && !in_array($fileExt, ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'txt', 'log', 'zip'])) {
        header("Location: support.php?msg=" . urlencode("Invalid file type. Allowed: JPG, PNG, GIF, PDF, TXT, LOG, ZIP") . "&type=error");
        exit;
    }
    
    if ($fileSize > $maxSize) {
        header("Location: support.php?msg=" . urlencode("File too large. Maximum size is 5MB") . "&type=error");
        exit;
    }
    
    $safeFileName = $ticketId . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $fileName);
    $uploadPath = $uploadDir . $safeFileName;
    
    if (move_uploaded_file($_FILES['attachment']['tmp_name'], $uploadPath)) {
        $attachmentPath = 'uploads/' . $safeFileName;
    }
}

// Capture user IP and timezone
$userIp = get_client_ip();
$userTimezone = get_timezone_from_ip($userIp);

// Create new ticket
$newTicket = [
    'id' => $ticketId,
    'email' => $email,
    'category' => $category,
    'priority' => $priority,
    'subject' => $subject,
    'description' => $description,
    'status' => 'open',
    'created_at' => date('Y-m-d H:i:s'),
    'user_ip' => $userIp,
    'user_timezone' => $userTimezone,
    'attachment' => $attachmentPath,
    'replies' => []
];

$tickets[] = $newTicket;

// Save tickets
if (file_put_contents($ticketsFile, json_encode($tickets, JSON_PRETTY_PRINT)) === false) {
    error_log("Failed to save ticket");
    header("Location: support.php?msg=" . urlencode("Failed to create ticket. Please try again.") . "&type=error");
    exit;
}

// Send confirmation email
$emailSubject = "Ticket Created: {$subject} [#{$ticketId}]";
$emailBody = "
<!DOCTYPE html>
<html>
<head>
<style>
body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
.container { max-width: 600px; margin: 0 auto; padding: 20px; }
.header { background: #16a34a; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
.content { background: #f9f9f9; padding: 30px; border: 1px solid #ddd; border-radius: 0 0 8px 8px; }
.ticket-info { background: white; padding: 20px; border-left: 4px solid #16a34a; margin: 20px 0; }
.message-box { background: #f0fdf4; padding: 20px; border-left: 4px solid #16a34a; margin: 20px 0; border-radius: 6px; }
.footer { text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; color: #666; font-size: 14px; }
.btn { display: inline-block; padding: 12px 24px; background: #1f6feb; color: white; text-decoration: none; border-radius: 6px; margin: 15px 0; }
</style>
</head>
<body>
<div class='container'>
  <div class='header'>
    <h1>✅ Ticket Created Successfully</h1>
  </div>
  <div class='content'>
    <p>Hello,</p>
    <p>Your support ticket has been created and our team has been notified. We'll review your request and respond as soon as possible.</p>
    
    <div class='ticket-info'>
      <p><strong>Ticket ID:</strong> {$ticketId}</p>
      <p><strong>Subject:</strong> {$subject}</p>
      <p><strong>Priority:</strong> " . ucfirst($priority) . "</p>
      <p><strong>Category:</strong> " . ucfirst($category) . "</p>
      <p><strong>Status:</strong> Open</p>
      <p><strong>Created:</strong> " . date('F j, Y, g:i a') . "</p>
    </div>

    <div class='message-box'>
      <p><strong>📝 Your Message:</strong></p>
      <p style='margin-top: 12px;'>" . nl2br(htmlspecialchars($description)) . "</p>
    </div>

    <p><strong>💬 What happens next?</strong></p>
    <p>Our support team will review your ticket and respond via email. You can track all updates and add additional information by viewing your ticket online.</p>

    <a href='https://" . $_SERVER['HTTP_HOST'] . "/view_ticket.php?id={$ticketId}' class='btn'>View & Track Your Ticket</a>

    <p style='margin-top: 20px;'>Thank you for contacting EnderBit support!</p>
  </div>
  <div class='footer'>
    <p>&copy; 2025 EnderBit. All rights reserved.</p>
    <p>Email: <a href='mailto:support@enderbit.com'>support@enderbit.com</a></p>
  </div>
</div>
</body>
</html>
";
try {
    // Try SMTP first
    send_smtp_email($email, $emailSubject, $emailBody, $config['smtp']);
} catch (Exception $e) {
    // Fallback to mail()
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . $config['smtp']['from_name'] . " <" . $config['smtp']['from_email'] . ">\r\n";
    
    if (!mail($email, $emailSubject, $emailBody, $headers)) {
        error_log("Failed to send ticket confirmation email to: {$email}");
        // Don't fail the ticket creation if email fails
    }
}

// Send notification to admin using SMTP
$adminEmail = $config['smtp']['from_email'];
$adminEmailSubject = "New Ticket Created: {$subject} [#{$ticketId}]";
$adminEmailBody = "
<!DOCTYPE html>
<html>
<head>
<style>
body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
.container { max-width: 600px; margin: 0 auto; padding: 20px; }
.header { background: #f0883e; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
.content { background: #f9f9f9; padding: 30px; border: 1px solid #ddd; border-radius: 0 0 8px 8px; }
.ticket-info { background: white; padding: 20px; border-left: 4px solid #f0883e; margin: 20px 0; }
.message-box { background: #fff5f0; padding: 20px; border-left: 4px solid #f0883e; margin: 20px 0; border-radius: 6px; }
.footer { text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; color: #666; font-size: 14px; }
.btn { display: inline-block; padding: 12px 24px; background: #1f6feb; color: white; text-decoration: none; border-radius: 6px; margin: 15px 0; }
</style>
</head>
<body>
<div class='container'>
  <div class='header'>
    <h1>🆕 New Support Ticket</h1>
  </div>
  <div class='content'>
    <p>A new support ticket has been created and requires your attention.</p>
    
    <div class='ticket-info'>
      <p><strong>Ticket ID:</strong> {$ticketId}</p>
      <p><strong>Customer Email:</strong> {$email}</p>
      <p><strong>Subject:</strong> {$subject}</p>
      <p><strong>Priority:</strong> " . ucfirst($priority) . "</p>
      <p><strong>Category:</strong> " . ucfirst($category) . "</p>
      <p><strong>Status:</strong> Open</p>
      <p><strong>Created:</strong> " . date('F j, Y, g:i a') . "</p>
    </div>

    <div class='message-box'>
      <p><strong>📝 Customer Message:</strong></p>
      <p style='margin-top: 12px;'>" . nl2br(htmlspecialchars($description)) . "</p>
    </div>

    <a href='https://" . $_SERVER['HTTP_HOST'] . "/view_ticket.php?id={$ticketId}' class='btn'>View & Reply to Ticket</a>
    
    <p style='margin-top: 20px;'>Please respond to this ticket as soon as possible.</p>
  </div>
  <div class='footer'>
    <p>&copy; 2025 EnderBit Support System</p>
  </div>
</div>
</body>
</html>
";

try {
    // Try SMTP first for admin notification
    send_smtp_email($adminEmail, $adminEmailSubject, $adminEmailBody, $config['smtp']);
} catch (Exception $e) {
    // Fallback to mail() for admin
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: EnderBit Support <" . $adminEmail . ">\r\n";
    @mail($adminEmail, $adminEmailSubject, $adminEmailBody, $headers);
}

// Redirect to success page
header("Location: view_ticket.php?id={$ticketId}&created=1");
exit;
