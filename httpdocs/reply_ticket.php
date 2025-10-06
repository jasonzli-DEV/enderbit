<?php
session_start();
require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: support.php");
    exit;
}

$ticketId = trim($_POST['ticket_id'] ?? '');
$replyMessage = trim($_POST['reply_message'] ?? '');
$isAdmin = isset($_POST['is_admin']) && $_POST['is_admin'] === '1';
$closeTicket = isset($_POST['close_ticket']) && $_POST['close_ticket'] === '1';
$reopenTicket = isset($_POST['reopen_ticket']) && $_POST['reopen_ticket'] === '1';
$addInternalNote = isset($_POST['add_internal_note']) && $_POST['add_internal_note'] === '1';
$internalNote = trim($_POST['internal_note'] ?? '');

// Validate admin for admin actions
if (($isAdmin || $addInternalNote) && (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true)) {
    header("Location: admin.php");
    exit;
}

// Validate input (allow empty message if just closing/reopening ticket or adding internal note)
if (empty($ticketId) || (empty($replyMessage) && empty($internalNote) && !$closeTicket && !$reopenTicket)) {
    header("Location: /ticket/$ticketId&msg=" . urlencode("Message cannot be empty"));
    exit;
}

// Load tickets
$ticketsFile = __DIR__ . '/tickets.json';
if (!file_exists($ticketsFile)) {
    header("Location: admin.php?msg=" . urlencode("Ticket not found") . "&type=error");
    exit;
}

$tickets = json_decode(file_get_contents($ticketsFile), true);
if (!is_array($tickets)) {
    header("Location: admin.php?msg=" . urlencode("Ticket not found") . "&type=error");
    exit;
}

// Find and update ticket
$ticketFound = false;
$ticketEmail = '';
$ticketSubject = '';
$wasClosedNowReopened = false;
$wasOpenNowClosed = false;
$previousStatus = '';

for ($i = 0; $i < count($tickets); $i++) {
    if ($tickets[$i]['id'] === $ticketId) {
        $ticketFound = true;
        $ticketEmail = $tickets[$i]['email'];
        $ticketSubject = $tickets[$i]['subject'];
        $previousStatus = $tickets[$i]['status'];
        
        // Add internal note if provided (admin only, no email notifications)
        if ($addInternalNote && !empty($internalNote)) {
            if (!isset($tickets[$i]['internal_notes'])) {
                $tickets[$i]['internal_notes'] = [];
            }
            
            $tickets[$i]['internal_notes'][] = [
                'note' => $internalNote,
                'author' => 'Admin', // You can enhance this to track which admin
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            // Save and redirect immediately for internal notes (no email needed)
            if (file_put_contents($ticketsFile, json_encode($tickets, JSON_PRETTY_PRINT)) !== false) {
                header("Location: /ticket/$ticketId&msg=" . urlencode("Internal note added successfully"));
                exit;
            } else {
                error_log("Failed to save internal note");
                header("Location: /ticket/$ticketId&msg=" . urlencode("Failed to save note. Please try again."));
                exit;
            }
        }
        
        // Add reply if message provided
        if (!empty($replyMessage)) {
            if (!isset($tickets[$i]['replies'])) {
                $tickets[$i]['replies'] = [];
            }
            
            // Handle file attachment for reply
            $replyAttachmentPath = null;
            if (isset($_FILES['reply_attachment']) && $_FILES['reply_attachment']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/uploads/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf', 'text/plain', 'application/zip', 'application/x-zip-compressed'];
                $maxSize = 5 * 1024 * 1024; // 5MB
                
                $fileType = $_FILES['reply_attachment']['type'];
                $fileSize = $_FILES['reply_attachment']['size'];
                $fileName = $_FILES['reply_attachment']['name'];
                $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                
                if ((in_array($fileType, $allowedTypes) || in_array($fileExt, ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'txt', 'log', 'zip'])) && $fileSize <= $maxSize) {
                    $safeFileName = $ticketId . '_reply_' . time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $fileName);
                    $uploadPath = $uploadDir . $safeFileName;
                    
                    if (move_uploaded_file($_FILES['reply_attachment']['tmp_name'], $uploadPath)) {
                        $replyAttachmentPath = 'uploads/' . $safeFileName;
                    }
                }
            }
            
            $tickets[$i]['replies'][] = [
                'message' => $replyMessage,
                'is_admin' => $isAdmin,
                'created_at' => date('Y-m-d H:i:s'),
                'attachment' => $replyAttachmentPath
            ];
        }
        
        // Reopen ticket if requested (client or admin)
        if ($reopenTicket) {
            if ($tickets[$i]['status'] === 'closed') {
                $wasClosedNowReopened = true;
                $tickets[$i]['status'] = 'open';
                $tickets[$i]['reopened_at'] = date('Y-m-d H:i:s');
                unset($tickets[$i]['closed_at']);
            }
        }
        // Close ticket if requested (admin only)
        elseif ($closeTicket && $isAdmin) {
            if ($tickets[$i]['status'] === 'open') {
                $wasOpenNowClosed = true;
                $tickets[$i]['status'] = 'closed';
                $tickets[$i]['closed_at'] = date('Y-m-d H:i:s');
            }
        }
        // Admin reply to closed ticket automatically reopens it
        elseif ($isAdmin && !empty($replyMessage) && $tickets[$i]['status'] === 'closed') {
            $wasClosedNowReopened = true;
            $tickets[$i]['status'] = 'open';
            $tickets[$i]['reopened_at'] = date('Y-m-d H:i:s');
            unset($tickets[$i]['closed_at']);
        }
        
        break;
    }
}

if (!$ticketFound) {
    header("Location: support.php?msg=" . urlencode("Ticket not found") . "&type=error");
    exit;
}

// Save tickets
if (file_put_contents($ticketsFile, json_encode($tickets, JSON_PRETTY_PRINT)) === false) {
    error_log("Failed to save ticket reply");
    header("Location: /ticket/$ticketId&msg=" . urlencode("Failed to save reply. Please try again."));
    exit;
}

// Prepare email notifications
$customerEmail = $ticketEmail;
$adminEmail = $config['smtp']['from_email'];

// Build email subject and body based on action
if ($wasOpenNowClosed) {
    // Ticket was closed - notify customer
    $emailSubject = "Ticket Closed: {$ticketSubject} [#{$ticketId}]";
    $sendToCustomer = true;
    $sendToAdmin = false;
} elseif ($wasClosedNowReopened) {
    // Ticket was reopened - notify both customer and admin
    $emailSubject = $isAdmin 
        ? "Ticket Reopened by Support: {$ticketSubject} [#{$ticketId}]"
        : "You Reopened Ticket: {$ticketSubject} [#{$ticketId}]";
    $sendToCustomer = true;
    $sendToAdmin = true;
} elseif ($isAdmin && !empty($replyMessage)) {
    // Admin replied - notify customer and admin
    $emailSubject = "New Reply on Your Ticket: {$ticketSubject} [#{$ticketId}]";
    $sendToCustomer = true;
    $sendToAdmin = true;
} elseif (!$isAdmin && !empty($replyMessage)) {
    // Customer replied - notify admin only
    $emailSubject = "Customer Reply on Ticket: {$ticketSubject} [#{$ticketId}]";
    $sendToCustomer = false;
    $sendToAdmin = true;
} else {
    $sendToCustomer = false;
    $sendToAdmin = false;
}

// Generate email body based on action
if ($wasOpenNowClosed) {
    $emailBody = "
<!DOCTYPE html>
<html>
<head>
<style>
body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
.container { max-width: 600px; margin: 0 auto; padding: 20px; }
.header { background: #dc2626; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
.content { background: #f9f9f9; padding: 30px; border: 1px solid #ddd; border-radius: 0 0 8px 8px; }
.ticket-info { background: white; padding: 20px; border-left: 4px solid #dc2626; margin: 20px 0; }
.reply-box { background: #fff5f5; padding: 20px; border-left: 4px solid #dc2626; margin: 20px 0; border-radius: 6px; }
.footer { text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; color: #666; font-size: 14px; }
.btn { display: inline-block; padding: 12px 24px; background: #1f6feb; color: white; text-decoration: none; border-radius: 6px; margin: 15px 0; }
</style>
</head>
<body>
<div class='container'>
  <div class='header'>
    <h1>🔒 Ticket Closed</h1>
  </div>
  <div class='content'>
    <p>Hello,</p>
    <p>Your support ticket has been closed by our team.</p>
    
    <div class='ticket-info'>
      <p><strong>Ticket ID:</strong> {$ticketId}</p>
      <p><strong>Subject:</strong> {$ticketSubject}</p>
      <p><strong>Status:</strong> Closed</p>
      <p><strong>Closed:</strong> " . date('F j, Y, g:i a') . "</p>
    </div>
    " . (!empty($replyMessage) ? "
    <div class='reply-box'>
      <p><strong>👨‍💼 Final Message from Support Team:</strong></p>
      <p style='margin-top: 12px;'>" . nl2br(htmlspecialchars($replyMessage)) . "</p>
    </div>
    " : "") . "
    <p>If you need further assistance, you can reopen this ticket by visiting the ticket page and clicking the 'Reopen Ticket' button.</p>
    <a href='https://" . $_SERVER['HTTP_HOST'] . "//ticket/$ticketId' class='btn'>View Ticket</a>
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
} elseif ($wasClosedNowReopened) {
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
.footer { text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; color: #666; font-size: 14px; }
.btn { display: inline-block; padding: 12px 24px; background: #1f6feb; color: white; text-decoration: none; border-radius: 6px; margin: 15px 0; }
</style>
</head>
<body>
<div class='container'>
  <div class='header'>
    <h1>🔓 Ticket Reopened</h1>
  </div>
  <div class='content'>
    <p>Hello,</p>
    <p>Your support ticket has been reopened and our team will assist you.</p>
    
    <div class='ticket-info'>
      <p><strong>Ticket ID:</strong> {$ticketId}</p>
      <p><strong>Subject:</strong> {$ticketSubject}</p>
      <p><strong>Status:</strong> Open</p>
      <p><strong>Reopened:</strong> " . date('F j, Y, g:i a') . "</p>
    </div>
    <a href='https://" . $_SERVER['HTTP_HOST'] . "//ticket/$ticketId' class='btn'>View Ticket</a>
    <p style='margin-top: 20px;'>Our support team will respond shortly.</p>
  </div>
  <div class='footer'>
    <p>&copy; 2025 EnderBit. All rights reserved.</p>
    <p>Email: <a href='mailto:support@enderbit.com'>support@enderbit.com</a></p>
  </div>
</div>
</body>
</html>
";
} else {
    $emailBody = "
<!DOCTYPE html>
<html>
<head>
<style>
body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
.container { max-width: 600px; margin: 0 auto; padding: 20px; }
.header { background: #1f6feb; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
.content { background: #f9f9f9; padding: 30px; border: 1px solid #ddd; border-radius: 0 0 8px 8px; }
.ticket-info { background: white; padding: 20px; border-left: 4px solid #f0883e; margin: 20px 0; }
.reply-box { background: #fffbf5; padding: 20px; border-left: 4px solid #f0883e; margin: 20px 0; border-radius: 6px; }
.footer { text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; color: #666; font-size: 14px; }
.btn { display: inline-block; padding: 12px 24px; background: #1f6feb; color: white; text-decoration: none; border-radius: 6px; margin: 15px 0; }
.important { background: #fff3cd; border: 1px solid #ffc107; padding: 15px; border-radius: 6px; margin: 20px 0; }
</style>
</head>
<body>
<div class='container'>
  <div class='header'>
    <h1>💬 New Reply on Your Support Ticket</h1>
  </div>
  <div class='content'>
    <p>Hello,</p>
    <p>There's a new update on your support ticket.</p>
    
    <div class='ticket-info'>
      <p><strong>Ticket ID:</strong> {$ticketId}</p>
      <p><strong>Subject:</strong> {$ticketSubject}</p>
      <p><strong>Status:</strong> Open</p>
    </div>

    <div class='reply-box'>
      <p><strong>" . ($isAdmin ? '👨‍💼 Support Team' : '👤 Customer') . " replied:</strong></p>
      <p style='margin-top: 12px;'>" . nl2br(htmlspecialchars($replyMessage)) . "</p>
      <p style='margin-top: 12px; font-size: 13px; color: #666;'><em>" . date('F j, Y, g:i a') . "</em></p>
    </div>

    <div class='important'>
      <p style='margin: 0;'><strong>💬 Want to respond?</strong> View the full ticket online to see all updates and continue the conversation with our support team.</p>
    </div>

    <a href='https://" . $_SERVER['HTTP_HOST'] . "//ticket/$ticketId' class='btn'>View Full Ticket</a>

    <p style='margin-top: 20px;'>Thank you for your patience!</p>
  </div>
  <div class='footer'>
    <p>&copy; 2025 EnderBit. All rights reserved.</p>
    <p>Email: <a href='mailto:support@enderbit.com'>support@enderbit.com</a></p>
  </div>
</div>
</body>
</html>
";
}

// Send email to customer if needed
if ($sendToCustomer) {
    sendEmail($customerEmail, $emailSubject, $emailBody, $config);
}

// Send email to admin if needed (use mail() function for support@enderbit.com)
if ($sendToAdmin) {
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: EnderBit Support <{$adminEmail}>\r\n";
    $headers .= "Reply-To: {$adminEmail}\r\n";
    @mail($adminEmail, $emailSubject, $emailBody, $headers);
}

// Redirect with success message
if ($isAdmin) {
    if ($wasOpenNowClosed) {
        header("Location: admin.php?msg=" . urlencode("Ticket closed successfully") . "&type=success");
    } elseif ($wasClosedNowReopened) {
        header("Location: /ticket/$ticketId&msg=" . urlencode("Ticket reopened successfully") . "&type=success");
    } else {
        header("Location: /ticket/$ticketId&msg=" . urlencode("Reply sent successfully") . "&type=success");
    }
} else {
    if ($wasClosedNowReopened) {
        header("Location: /ticket/$ticketId&msg=" . urlencode("Ticket reopened successfully. Our team will respond shortly.") . "&type=success");
    } else {
        header("Location: /ticket/$ticketId&msg=" . urlencode("Reply sent successfully. Our team will respond shortly.") . "&type=success");
    }
}
exit;

function sendEmail($to, $subject, $body, $config) {
    $from = $config['smtp']['from_email'];
    $fromName = $config['smtp']['from_name'];
    
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: {$fromName} <{$from}>\r\n";
    $headers .= "Reply-To: {$from}\r\n";
    
    // Try SMTP first
    if ($config['smtp']['enabled']) {
        $smtp = $config['smtp'];
        $socket = @fsockopen($smtp['host'], $smtp['port'], $errno, $errstr, 10);
        
        if ($socket) {
            $response = fgets($socket);
            fwrite($socket, "EHLO " . $smtp['host'] . "\r\n");
            $response = fgets($socket);
            
            if ($smtp['port'] == 587) {
                fwrite($socket, "STARTTLS\r\n");
                $response = fgets($socket);
                stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                fwrite($socket, "EHLO " . $smtp['host'] . "\r\n");
                $response = fgets($socket);
            }
            
            fwrite($socket, "AUTH LOGIN\r\n");
            $response = fgets($socket);
            fwrite($socket, base64_encode($smtp['username']) . "\r\n");
            $response = fgets($socket);
            fwrite($socket, base64_encode($smtp['password']) . "\r\n");
            $response = fgets($socket);
            
            fwrite($socket, "MAIL FROM: <{$from}>\r\n");
            $response = fgets($socket);
            fwrite($socket, "RCPT TO: <{$to}>\r\n");
            $response = fgets($socket);
            fwrite($socket, "DATA\r\n");
            $response = fgets($socket);
            
            $emailContent = "From: {$fromName} <{$from}>\r\n";
            $emailContent .= "To: {$to}\r\n";
            $emailContent .= "Subject: {$subject}\r\n";
            $emailContent .= "MIME-Version: 1.0\r\n";
            $emailContent .= "Content-Type: text/html; charset=UTF-8\r\n";
            $emailContent .= "\r\n";
            $emailContent .= $body;
            $emailContent .= "\r\n.\r\n";
            
            fwrite($socket, $emailContent);
            $response = fgets($socket);
            
            fwrite($socket, "QUIT\r\n");
            fclose($socket);
            return true;
        }
    }
    
    // Fallback to mail()
    return @mail($to, $subject, $body, $headers);
}
?>
