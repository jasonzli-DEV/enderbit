<?php
// Configure session to close on browser close (unless remember me is used)
ini_set('session.cookie_lifetime', 0); // Session cookie (closes with browser)
ini_set('session.gc_maxlifetime', 86400); // 24 hours max session life on server

session_start();

// Handle remember me cookie for persistent login
if (isset($_COOKIE['admin_remember']) && $_COOKIE['admin_remember'] === 'true') {
    if (!isset($_SESSION['admin_logged_in'])) {
        $_SESSION['admin_logged_in'] = true;
    }
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/logger.php';

// Check admin authentication
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin.php");
    exit;
}

$msg = $_GET['msg'] ?? '';
$msgType = $_GET['type'] ?? 'success';

// Deny pending user
if (isset($_POST['deny_user'])) {
    $emailToDeny = $_POST['deny_user'];
    $denyReason = trim($_POST['deny_reason'] ?? '');
    
    EnderBitLogger::logAdmin('USER_DENIAL_INITIATED', 'DENY_USER', ['email' => $emailToDeny, 'reason' => $denyReason]);
    
    $tokensFile = __DIR__ . '/tokens.json';
    if (file_exists($tokensFile)) {
        $tokens = json_decode(file_get_contents($tokensFile), true);
        if (!is_array($tokens)) $tokens = [];

        for ($i = 0; $i < count($tokens); $i++) {
            if (strcasecmp($tokens[$i]['email'], $emailToDeny) === 0) {
                $userInfo = $tokens[$i];
                
                // Remove from pending list
                array_splice($tokens, $i, 1);
                if (file_put_contents($tokensFile, json_encode($tokens, JSON_PRETTY_PRINT)) === false) {
                    EnderBitLogger::logSystem('TOKENS_FILE_WRITE_FAILED', ['action' => 'user_denial', 'email' => $emailToDeny]);
                    header("Location: users_admin.php?msg=" . urlencode("Denial failed - please try again") . "&type=error");
                    exit;
                }
                
                // Send denial email
                $subject = "Registration Application Status - EnderBit Hosting";
                $body = "<div style='font-family:Arial,sans-serif;max-width:600px;margin:0 auto;padding:20px;'>";
                $body .= "<h2 style='color:#f85149;'>Registration Application Update</h2>";
                $body .= "<p>Hello " . htmlspecialchars($userInfo['first']) . ",</p>";
                $body .= "<p>Thank you for your interest in EnderBit Hosting. Unfortunately, we are unable to approve your registration application at this time.</p>";
                
                if (!empty($denyReason)) {
                    $body .= "<div style='background:#161b22;border-left:4px solid #f85149;padding:15px;margin:20px 0;'>";
                    $body .= "<p style='margin:0;'><strong>Reason:</strong></p>";
                    $body .= "<p style='margin:10px 0 0 0;'>" . nl2br(htmlspecialchars($denyReason)) . "</p>";
                    $body .= "</div>";
                }
                
                $body .= "<p>If you believe this is an error or would like to discuss further, please contact us at <a href='mailto:hello@enderbit.com' style='color:#58a6ff;'>hello@enderbit.com</a>.</p>";
                $body .= "<p style='margin-top:30px;color:#8b949e;font-size:12px;'>EnderBit Hosting Team</p>";
                $body .= "</div>";
                
                // Try sending email
                $mailSent = false;
                
                // Attempt SMTP if configured
                if (!empty($config['smtp']['host']) && !empty($config['smtp']['username'])) {
                    try {
                        $mailSent = send_smtp_email($emailToDeny, $subject, $body, $config['smtp']);
                        if ($mailSent) {
                            EnderBitLogger::logEmail('DENIAL_EMAIL_SENT', $emailToDeny, $subject, ['reason_provided' => !empty($denyReason)]);
                        }
                    } catch (Exception $e) {
                        EnderBitLogger::logEmail('DENIAL_EMAIL_FAILED', $emailToDeny, $subject, ['error' => $e->getMessage()]);
                    }
                }
                
                // Fallback to PHP mail() if SMTP failed
                if (!$mailSent) {
                    $headers  = "MIME-Version: 1.0\r\n";
                    $headers .= "Content-type:text/html;charset=UTF-8\r\n";
                    $headers .= "From: EnderBit Hosting <hello@enderbit.com>\r\n";
                    $mailSent = @mail($emailToDeny, $subject, $body, $headers);
                    
                    if ($mailSent) {
                        EnderBitLogger::logEmail('DENIAL_EMAIL_SENT_FALLBACK', $emailToDeny, $subject);
                    }
                }
                
                EnderBitLogger::logAdmin('USER_DENIAL_SUCCESS', 'DENY_USER', [
                    'email' => $emailToDeny, 
                    'reason' => $denyReason,
                    'email_sent' => $mailSent
                ]);
                EnderBitLogger::logRegistration('USER_DENIED', $emailToDeny, ['reason' => $denyReason]);
                
                $successMsg = $mailSent 
                    ? "User denied and notification email sent" 
                    : "User denied (email notification failed to send)";
                
                header("Location: users_admin.php?msg=" . urlencode($successMsg) . "&type=success");
                exit;
            }
        }
    }
    header("Location: users_admin.php?msg=" . urlencode("User not found.") . "&type=error");
    exit;
}

// Approve pending user
if (isset($_POST['approve_user'])) {
    $emailToApprove = $_POST['approve_user'];
    EnderBitLogger::logAdmin('USER_APPROVAL_INITIATED', 'APPROVE_USER', ['email' => $emailToApprove]);
    
    $tokensFile = __DIR__ . '/tokens.json';
    if (file_exists($tokensFile)) {
        $tokens = json_decode(file_get_contents($tokensFile), true);
        if (!is_array($tokens)) $tokens = [];

        for ($i = 0; $i < count($tokens); $i++) {
            if (strcasecmp($tokens[$i]['email'], $emailToApprove) === 0) {
                $tokens[$i]['approved'] = true;

                $password = isset($tokens[$i]['password_plain']) ? base64_decode($tokens[$i]['password_plain']) : '';
                $result = create_user_on_ptero([
                    'first' => $tokens[$i]['first'],
                    'last'  => $tokens[$i]['last'],
                    'username' => isset($tokens[$i]['username']) ? $tokens[$i]['username'] : '',
                    'email' => $tokens[$i]['email'],
                    'password' => $password
                ]);

                array_splice($tokens, $i, 1);
                if (file_put_contents($tokensFile, json_encode($tokens, JSON_PRETTY_PRINT)) === false) {
                    error_log("Failed to update tokens file after approval");
                    EnderBitLogger::logSystem('TOKENS_FILE_WRITE_FAILED', ['action' => 'user_approval', 'email' => $emailToApprove]);
                    header("Location: users_admin.php?msg=" . urlencode("Approval failed - please try again") . "&type=error");
                    exit;
                }
                
                if ($result) {
                    EnderBitLogger::logAdmin('USER_APPROVAL_SUCCESS', 'APPROVE_USER', ['email' => $emailToApprove]);
                    EnderBitLogger::logRegistration('USER_APPROVED_AND_CREATED', $emailToApprove);
                } else {
                    EnderBitLogger::logAdmin('USER_APPROVAL_PTERODACTYL_FAILED', 'APPROVE_USER', ['email' => $emailToApprove]);
                }
                
                header("Location: users_admin.php?msg=" . urlencode("User approved and created!") . "&type=success");
                exit;
            }
        }
    }
    header("Location: users_admin.php?msg=" . urlencode("User not found.") . "&type=error");
    exit;
}

// Get pending users
$tokensFile = __DIR__ . '/tokens.json';
$pendingUsers = [];
$hasPendingUsers = false;
if (file_exists($tokensFile)) {
    $tokens = json_decode(file_get_contents($tokensFile), true);
    if (is_array($tokens)) {
        $pendingUsers = $tokens;
        $hasPendingUsers = count($tokens) > 0;
    }
}
?>
<!doctype html>
<html lang="en" data-theme="dark">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>User Management — EnderBit Admin</title>
<link rel="icon" type="image/png" href="/icon.png" sizes="96x96">
<style>
  :root {
    --bg:#0d1117; --card:#161b22; --accent:#58a6ff; --primary:#1f6feb;
    --muted:#8b949e; --green:#238636; --red:#f85149; --yellow:#f0883e;
    --text:#e6eef8; --input-bg:#0e1418; --input-border:#232629;
  }
  html,body{height:100%;margin:0;font-family:Inter,Arial,sans-serif;background:var(--bg);color:var(--text);}
  .page{min-height:100%;padding:28px;box-sizing:border-box;}
  .container{max-width:1400px;margin:0 auto;}
  .card{background:var(--card);border:1px solid var(--input-border);border-radius:12px;padding:24px;box-shadow:0 4px 12px rgba(0,0,0,.3);}
  h1{margin:0 0 24px;color:var(--accent);font-size:32px;}
  .page-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:32px;}
  .page-header h1{margin:0;}
  .btn{display:inline-block;padding:12px 24px;border-radius:8px;font-weight:600;font-size:14px;text-align:center;cursor:pointer;border:0;text-decoration:none;transition:all .2s;box-sizing:border-box;}
  .btn-secondary{background:var(--input-bg);color:var(--text);border:1px solid var(--input-border);}
  .btn-success{background:var(--green);color:#fff;}
  .btn-danger{background:var(--red);color:#fff;}
  .btn-secondary:hover,.btn-success:hover,.btn-danger:hover{opacity:.9;transform:translateY(-1px);}
  .btn-small{padding:8px 16px;font-size:13px;}
  .btn-group{display:flex;gap:8px;}
  
  table{width:100%;border-collapse:collapse;}
  table th,table td{border-bottom:1px solid var(--input-border);padding:14px 12px;text-align:left;}
  table th{background:var(--input-bg);color:var(--accent);font-weight:600;font-size:13px;text-transform:uppercase;letter-spacing:0.5px;}
  table tr:last-child td{border-bottom:none;}
  table tr:hover{background:var(--input-bg);}
  
  .stat-card{background:var(--card);border:1px solid var(--input-border);border-radius:12px;padding:24px;text-align:center;max-width:300px;margin:0 auto 32px;}
  .stat-value{font-size:36px;font-weight:700;color:var(--yellow);margin:12px 0;}
  .stat-label{font-size:13px;color:var(--muted);text-transform:uppercase;letter-spacing:1px;font-weight:600;}
  .stat-icon{font-size:28px;margin-bottom:8px;}
  
  .empty-state{text-align:center;padding:60px 20px;color:var(--muted);}
  .empty-state h3{color:var(--text);margin-bottom:8px;}
  
  .banner {
    position:fixed;
    left:-500px;
    top:20px;
    padding:14px 20px;
    border-radius:10px;
    min-width:280px;
    max-width:400px;
    box-shadow:0 8px 30px rgba(0,0,0,.5);
    display:flex;
    justify-content:space-between;
    align-items:center;
    transition:left .45s cubic-bezier(0.4, 0.0, 0.2, 1);
    z-index:2200;
  }
  .banner.show{ left:20px; }
  .banner.hide{ left:-500px !important; }
  .banner.success{ background:var(--green); color:#fff; }
  .banner.error{ background:var(--red); color:#fff; }
  .banner .close{ cursor:pointer; font-weight:700; color:#fff; padding-left:12px; opacity:0.8; }
  .banner .close:hover{ opacity:1; }
  
  /* Deny Modal */
  .modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.8);
    z-index: 9999;
    align-items: center;
    justify-content: center;
  }
  .modal-content {
    background: var(--card);
    border: 1px solid var(--input-border);
    border-radius: 12px;
    padding: 32px;
    max-width: 500px;
    width: 90%;
    max-height: 80vh;
    overflow-y: auto;
  }
  .modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
  }
  .modal-header h2 {
    margin: 0;
    color: var(--red);
    font-size: 24px;
  }
  .modal-close {
    background: none;
    border: none;
    color: var(--text);
    font-size: 24px;
    cursor: pointer;
    padding: 0;
    width: 32px;
    height: 32px;
  }
  .modal-close:hover {
    color: var(--accent);
  }
  .form-group {
    margin-bottom: 20px;
  }
  .form-group label {
    display: block;
    color: var(--text);
    font-weight: 600;
    margin-bottom: 8px;
    font-size: 14px;
  }
  .form-group textarea {
    width: 100%;
    padding: 12px;
    border-radius: 8px;
    border: 1px solid var(--input-border);
    background: var(--input-bg);
    color: var(--text);
    font-family: inherit;
    resize: vertical;
    min-height: 100px;
    box-sizing: border-box;
  }
  .form-group textarea:focus {
    outline: none;
    border-color: var(--accent);
  }
  .modal-actions {
    display: flex;
    gap: 12px;
    margin-top: 24px;
  }
  .modal-actions .btn {
    flex: 1;
  }
</style>
</head>
<body>
  <?php if ($msg): ?>
    <div id="banner" class="banner <?= htmlspecialchars($msgType) ?> show">
      <span><?= htmlspecialchars($msg) ?></span>
      <span class="close" onclick="hideBanner()">×</span>
    </div>
  <?php endif; ?>

  <div class="page">
    <div class="container">
      <div class="page-header">
        <h1>👥 User Management</h1>
        <a href="/admin.php" class="btn btn-secondary">← Back to Admin Panel</a>
      </div>

      <div class="stat-card">
        <div class="stat-icon">👥</div>
        <div class="stat-label">Pending Users</div>
        <div class="stat-value"><?= count($pendingUsers) ?></div>
      </div>

      <div class="card">
        <?php if ($hasPendingUsers): ?>
          <table>
            <tr><th>Email</th><th>Status</th><th>Action</th></tr>
            <?php foreach ($pendingUsers as $user):
                if (!empty($user['verified']) && empty($user['approved'])): ?>
                  <tr>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                    <td><span style='color:var(--green);'>✓ Verified, Awaiting Approval</span></td>
                    <td>
                      <div class='btn-group'>
                        <form method='post' style='margin:0'>
                          <button type='submit' name='approve_user' value='<?= htmlspecialchars($user['email']) ?>' class='btn btn-success btn-small'>✓ Approve</button>
                        </form>
                        <button type='button' onclick='openDenyModal("<?= htmlspecialchars($user['email'], ENT_QUOTES) ?>")' class='btn btn-danger btn-small'>✕ Deny</button>
                      </div>
                    </td>
                  </tr>
                <?php elseif (empty($user['verified'])): ?>
                  <tr>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                    <td><span style='color:var(--yellow);'>⏳ Pending Email Verification</span></td>
                    <td>
                      <button type='button' onclick='openDenyModal("<?= htmlspecialchars($user['email'], ENT_QUOTES) ?>")' class='btn btn-danger btn-small'>✕ Deny</button>
                    </td>
                  </tr>
                <?php endif;
              endforeach; ?>
          </table>
        <?php else: ?>
          <div class="empty-state">
            <h3>No Pending Users</h3>
            <p>All users have been processed.</p>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

<!-- Deny User Modal -->
<div id="denyModal" class="modal">
  <div class="modal-content">
    <div class="modal-header">
      <h2>🚫 Deny User Registration</h2>
      <button class="modal-close" onclick="closeDenyModal()">×</button>
    </div>
    <form method="post">
      <input type="hidden" name="deny_user" id="denyUserEmail" value="">
      <p style="color:var(--muted);margin-bottom:20px;">
        You are about to deny the registration for <strong id="denyUserEmailDisplay"></strong>.
      </p>
      <div class="form-group">
        <label for="deny_reason">Reason (Optional)</label>
        <textarea 
          name="deny_reason" 
          id="deny_reason" 
          placeholder="Optionally provide a reason for denial. This will be included in the email sent to the user."
        ></textarea>
      </div>
      <div class="modal-actions">
        <button type="button" onclick="closeDenyModal()" class="btn btn-secondary">
          Cancel
        </button>
        <button type="submit" class="btn btn-danger">
          🚫 Confirm Denial
        </button>
      </div>
    </form>
  </div>
</div>

<script>
function hideBanner(){
  const b = document.getElementById('banner');
  if (!b) return;
  b.classList.add('hide');
  b.classList.remove('show');
}

function openDenyModal(email) {
  document.getElementById('denyUserEmail').value = email;
  document.getElementById('denyUserEmailDisplay').textContent = email;
  document.getElementById('deny_reason').value = '';
  document.getElementById('denyModal').style.display = 'flex';
}

function closeDenyModal() {
  document.getElementById('denyModal').style.display = 'none';
}

// Close modal when clicking outside
window.addEventListener('click', function(e) {
  const modal = document.getElementById('denyModal');
  if (e.target === modal) {
    closeDenyModal();
  }
});

window.addEventListener('load', ()=>{
  const b = document.getElementById('banner');
  if (!b) return;
  b.classList.remove('hide');
  setTimeout(()=> b.classList.add('show'), 120);
  setTimeout(()=> hideBanner(), 5000);
});
</script>
</body>
</html>
