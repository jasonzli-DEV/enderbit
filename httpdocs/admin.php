<?php
session_start();
require_once __DIR__ . '/config.php';

// Function to check if updates are available
function checkForUpdates() {
    $versionFile = __DIR__ . '/version.json';
    $currentVersion = 'unknown';
    
    if (file_exists($versionFile)) {
        $versionData = json_decode(file_get_contents($versionFile), true);
        $currentVersion = $versionData['version'] ?? 'unknown';
    }
    
    $apiUrl = "https://api.github.com/repos/jasonzli-DEV/enderbit/commits/main";
    $context = stream_context_create([
        'http' => [
            'header' => "User-Agent: EnderBit-Updater\r\n",
            'timeout' => 3
        ]
    ]);
    
    $commitData = @file_get_contents($apiUrl, false, $context);
    if ($commitData === false) return false;
    
    $commit = json_decode($commitData, true);
    $latestVersion = substr($commit['sha'], 0, 7);
    
    return ($currentVersion !== $latestVersion && $currentVersion !== 'unknown');
}

$settingsFile = __DIR__ . '/settings.json';
if (!file_exists($settingsFile)) {
    file_put_contents($settingsFile, json_encode([
        'require_email_verify' => true,
        'require_admin_approve' => false
    ], JSON_PRETTY_PRINT));
}
$settings = json_decode(file_get_contents($settingsFile), true);

// Save and Exit (does NOT log out)
if (isset($_POST['save_exit'])) {
    $settings['require_email_verify'] = !empty($_POST['require_email_verify']);
    $settings['require_admin_approve'] = !empty($_POST['require_admin_approve']);
    file_put_contents($settingsFile, json_encode($settings, JSON_PRETTY_PRINT));
    header("Location: index.php?msg=" . urlencode("Settings saved") . "&type=success");
    exit;
}

// Logout (saves + logs out)
if (isset($_POST['logout'])) {
    $settings['require_email_verify'] = !empty($_POST['require_email_verify']);
    $settings['require_admin_approve'] = !empty($_POST['require_admin_approve']);
    file_put_contents($settingsFile, json_encode($settings, JSON_PRETTY_PRINT));
    session_destroy();
    header("Location: admin.php?msg=Logged+out&type=success");
    exit;
}

// Handle login
if (!isset($_SESSION['admin_logged_in'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_password'])) {
        if ($_POST['admin_password'] === $config['admin_password']) {
            $_SESSION['admin_logged_in'] = true;
            header("Location: admin.php");
            exit;
        } else {
            $msg = "Invalid password.";
            $type = "error";
        }
    }
}

// Approve pending user
if (isset($_POST['approve_user'])) {
    $emailToApprove = $_POST['approve_user'];
    $tokensFile = __DIR__ . '/tokens.json';
    if (file_exists($tokensFile)) {
        $tokens = json_decode(file_get_contents($tokensFile), true);
        if (!is_array($tokens)) $tokens = [];

        for ($i = 0; $i < count($tokens); $i++) {
            if (strcasecmp($tokens[$i]['email'], $emailToApprove) === 0) {
                $tokens[$i]['approved'] = true;

                $password = isset($tokens[$i]['password_plain']) ? base64_decode($tokens[$i]['password_plain']) : '';
                create_user_on_ptero([
                    'first' => $tokens[$i]['first'],
                    'last'  => $tokens[$i]['last'],
                    'username' => isset($tokens[$i]['username']) ? $tokens[$i]['username'] : '',
                    'email' => $tokens[$i]['email'],
                    'password' => $password
                ]);

                array_splice($tokens, $i, 1);
                if (file_put_contents($tokensFile, json_encode($tokens, JSON_PRETTY_PRINT)) === false) {
                    error_log("Failed to update tokens file after approval");
                    header("Location: admin.php?msg=" . urlencode("Approval failed - please try again") . "&type=error");
                    exit;
                }
                header("Location: admin.php?msg=" . urlencode("User approved and created!") . "&type=success");
                exit;
            }
        }
    }
    header("Location: admin.php?msg=" . urlencode("User not found.") . "&type=error");
    exit;
}
?>
<!doctype html>
<html lang="en" data-theme="dark">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Admin Panel — Enderbit</title>
<link rel="icon" type="image/png" href="/icon.png" sizes="96x96">
<style>
  :root {
    --bg:#0d1117; --card:#161b22; --accent:#58a6ff; --primary:#1f6feb;
    --muted:#8b949e; --green:#238636; --red:#f85149;
    --text:#e6eef8; --input-bg:#0e1418; --input-border:#232629;
  }
  html,body{height:100%;margin:0;font-family:Inter,Arial,sans-serif;background:linear-gradient(180deg,var(--bg),#07101a);color:var(--text);}
  .page{min-height:100%;display:flex;align-items:center;justify-content:center;padding:28px;box-sizing:border-box;}
  .card{background:var(--card);border-radius:14px;padding:36px;max-width:820px;width:100%;box-shadow:0 18px 50px rgba(0,0,0,.6);display:flex;flex-direction:column;gap:20px;}
  h1,h2{margin:0;color:var(--accent);text-align:center;}
  input[type=password]{width:100%;padding:14px;margin:10px 0;border-radius:10px;border:1px solid var(--input-border);background:var(--input-bg);color:var(--text);font-size:15px;}
  input[type=checkbox]{width:18px;height:18px;margin-right:6px;}
  label{display:flex;align-items:center;margin:8px 0;color:var(--text);}
  .btn{display:block;width:100%;padding:13px;border-radius:10px;font-weight:700;font-size:15px;text-align:center;cursor:pointer;border:0;box-sizing:border-box;margin-top:10px;}
  .btn-primary{background:var(--primary);color:#fff;}
  .btn-secondary{background:#202428;color:#fff;}
  .btn-danger{background:var(--red);color:#fff;}
  .btn-primary:hover,.btn-secondary:hover,.btn-danger:hover{opacity:.9;}
  table{width:100%;border-collapse:collapse;margin-top:10px;}
  table th,table td{border:1px solid var(--input-border);padding:8px;text-align:left;}
  table th{background:#222;color:#fff;}
  .ticket-section{margin-top:40px;}
  .ticket-card{background:#1a1f26;border:1px solid var(--input-border);border-radius:10px;padding:20px;margin-bottom:20px;}
  .ticket-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;padding-bottom:12px;border-bottom:1px solid var(--input-border);}
  .ticket-title{font-size:18px;font-weight:600;color:var(--accent);}
  .ticket-meta{font-size:13px;color:var(--muted);margin-bottom:12px;}
  .ticket-description{background:#0e1418;padding:14px;border-radius:8px;margin:12px 0;line-height:1.6;white-space:pre-wrap;}
  .ticket-replies{margin-top:16px;padding-top:16px;border-top:1px solid var(--input-border);}
  .reply-item{background:#0e1418;padding:12px;border-radius:8px;margin:10px 0;border-left:3px solid #f0883e;}
  .reply-meta{font-size:12px;color:var(--muted);margin-bottom:6px;}
  .reply-form textarea{width:100%;padding:12px;border-radius:8px;border:1px solid var(--input-border);background:var(--input-bg);color:var(--text);font-family:inherit;resize:vertical;min-height:80px;margin-top:10px;}
  .status-badge{display:inline-block;padding:6px 12px;border-radius:14px;font-size:12px;font-weight:600;border:2px solid;}
  .status-open{background:rgba(34,197,94,.15);color:#22c55e;border-color:#22c55e;}
  .status-closed{background:rgba(239,68,68,.15);color:#ef4444;border-color:#ef4444;}
  .btn-small{padding:8px 16px;font-size:13px;}
  .view-ticket-btn{display:inline-block;padding:8px 16px;background:var(--accent);color:#fff;text-decoration:none;border-radius:6px;font-size:13px;font-weight:600;margin-top:10px;}
  .view-ticket-btn:hover{opacity:.9;}
  .stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;margin:20px 0;}
  .stat-card{background:var(--input-bg);border:1px solid var(--input-border);border-radius:10px;padding:20px;text-align:center;}
  .stat-value{font-size:32px;font-weight:700;color:var(--accent);margin:8px 0;}
  .stat-label{font-size:13px;color:var(--muted);text-transform:uppercase;letter-spacing:1px;}
  .update-badge{background:#ef4444;color:#fff;font-size:10px;padding:3px 8px;border-radius:10px;margin-left:8px;font-weight:700;animation:pulse 2s infinite;}
  @keyframes pulse{0%,100%{opacity:1}50%{opacity:.6}}
  .section-header{display:flex;justify-content:space-between;align-items:center;margin:30px 0 15px;}
  .section-header h2{margin:0;}
</style>
</head>
<body>
<div class="page">
  <div class="card">
    <?php if (!isset($_SESSION['admin_logged_in'])): ?>
      <h1>Admin Login</h1>
      <?php if (!empty($msg)): ?><p style="color:red;"><?= htmlspecialchars($msg) ?></p><?php endif; ?>
      <form method="post">
        <input type="password" name="admin_password" placeholder="Enter Password" required>
        <button type="submit" class="btn btn-primary">Login</button>
      </form>
    <?php else: ?>
      <h1>Admin Panel</h1>

      <?php if (isset($_GET['msg'])): ?>
        <p style="color:<?= $_GET['type'] === 'error' ? 'var(--red)' : 'var(--green)' ?>;">
          <?= htmlspecialchars($_GET['msg']) ?>
        </p>
      <?php endif; ?>

      <!-- Dashboard Statistics -->
      <?php
      $tokensFile = __DIR__ . '/tokens.json';
      $ticketsFile = __DIR__ . '/tickets.json';
      
      $totalUsers = 0;
      $pendingUsers = 0;
      if (file_exists($tokensFile)) {
          $tokens = json_decode(file_get_contents($tokensFile), true);
          if (is_array($tokens)) {
              $pendingUsers = count($tokens);
          }
      }
      
      $totalTickets = 0;
      $openTickets = 0;
      $closedTickets = 0;
      if (file_exists($ticketsFile)) {
          $tickets = json_decode(file_get_contents($ticketsFile), true);
          if (is_array($tickets)) {
              $totalTickets = count($tickets);
              foreach ($tickets as $ticket) {
                  if ($ticket['status'] === 'open') $openTickets++;
                  if ($ticket['status'] === 'closed') $closedTickets++;
              }
          }
      }
      
      $hasUpdate = checkForUpdates();
      ?>
      
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-label">📊 Total Tickets</div>
          <div class="stat-value"><?= $totalTickets ?></div>
        </div>
        <div class="stat-card">
          <div class="stat-label">🟢 Open Tickets</div>
          <div class="stat-value" style="color:#22c55e;"><?= $openTickets ?></div>
        </div>
        <div class="stat-card">
          <div class="stat-label">🔴 Closed Tickets</div>
          <div class="stat-value" style="color:#ef4444;"><?= $closedTickets ?></div>
        </div>
        <div class="stat-card">
          <div class="stat-label">👥 Pending Users</div>
          <div class="stat-value" style="color:#f59e0b;"><?= $pendingUsers ?></div>
        </div>
      </div>

      <form method="post">
        <label><input type="checkbox" name="require_email_verify" <?= !empty($settings['require_email_verify']) ? 'checked':'' ?>> Require Email Verification</label>
        <label><input type="checkbox" name="require_admin_approve" <?= !empty($settings['require_admin_approve']) ? 'checked':'' ?>> Require Admin Approval</label>

        <a href="update.php" class="btn btn-secondary" style="display:block;text-decoration:none;margin-bottom:10px;">
          🔄 Pull Latest Updates
          <?php if ($hasUpdate): ?><span class="update-badge">NEW</span><?php endif; ?>
        </a>
        <button type="submit" name="save_exit" class="btn btn-primary">Save and Exit</button>
        <button type="submit" name="logout" class="btn btn-danger">Logout</button>
      </form>

      <div class="section-header">
        <h2>👥 Pending Users</h2>
      </div>
      <table>
        <tr><th>Email</th><th>Status</th><th>Action</th></tr>
        <?php
        $tokensFile = __DIR__ . '/tokens.json';
        if (file_exists($tokensFile)) {
            $tokens = json_decode(file_get_contents($tokensFile), true);
            if (is_array($tokens)) {
                foreach ($tokens as $user) {
                    if (!empty($user['verified']) && empty($user['approved'])) {
                        echo "<tr><td>".htmlspecialchars($user['email'])."</td><td>Verified, Awaiting Approval</td><td>
                        <form method='post' style='margin:0'>
                          <button type='submit' name='approve_user' value='".htmlspecialchars($user['email'])."' class='btn btn-primary'>Approve</button>
                        </form>
                        </td></tr>";
                    } elseif (empty($user['verified'])) {
                        echo "<tr><td>".htmlspecialchars($user['email'])."</td><td>Pending Email Verification</td><td>—</td></tr>";
                    }
                }
            }
        }
        ?>
      </table>

      <!-- Support Tickets Section -->
      <div class="ticket-section">
        <div class="section-header">
          <h2>🎫 Support Tickets</h2>
          <span style="font-size:13px;color:var(--muted);">Sorted by newest first</span>
        </div>
        <?php
        $ticketsFile = __DIR__ . '/tickets.json';
        if (file_exists($ticketsFile)) {
            $tickets = json_decode(file_get_contents($ticketsFile), true);
            if (is_array($tickets) && count($tickets) > 0) {
                // Sort tickets by created_at descending
                usort($tickets, function($a, $b) {
                    return strtotime($b['created_at']) - strtotime($a['created_at']);
                });
                
                foreach ($tickets as $ticket) {
                    $ticketId = htmlspecialchars($ticket['id']);
                    $replyCount = isset($ticket['replies']) ? count($ticket['replies']) : 0;
                    ?>
                    <div class="ticket-card" id="ticket-<?= $ticketId ?>">
                      <div class="ticket-header">
                        <div>
                          <div class="ticket-title">🎫 <?= htmlspecialchars($ticket['subject']) ?></div>
                          <div class="ticket-meta">
                            ID: <strong><?= $ticketId ?></strong> | 
                            <?php if (!empty($ticket['category'])): ?>
                              <?php
                              $categoryIcons = [
                                'technical' => '💻',
                                'billing' => '💳',
                                'account' => '👤',
                                'feature' => '✨',
                                'other' => '❓'
                              ];
                              $icon = $categoryIcons[$ticket['category']] ?? '📋';
                              echo $icon . ' <strong>' . htmlspecialchars(ucfirst($ticket['category'])) . '</strong> | ';
                              ?>
                            <?php endif; ?>
                            From: <strong><?= htmlspecialchars($ticket['email']) ?></strong> | 
                            Created: <?= htmlspecialchars(date('M j, Y, g:i A', strtotime($ticket['created_at']))) ?> | 
                            <?php if ($replyCount > 0): ?>💬 <strong><?= $replyCount ?></strong> replies<?php endif; ?>
                          </div>
                        </div>
                        <span class="status-badge status-<?= htmlspecialchars($ticket['status']) ?>">
                          <?= htmlspecialchars(ucfirst($ticket['status'])) ?>
                        </span>
                      </div>

                      <div style="margin-top:15px;">
                        <a href="view_ticket.php?id=<?= $ticketId ?>" target="_blank" class="view-ticket-btn">View & Reply to Ticket</a>
                      </div>
                    </div>
                    <?php
                }
            } else {
                echo "<p style='color:var(--muted);text-align:center;padding:20px;'>No support tickets yet.</p>";
            }
        } else {
            echo "<p style='color:var(--muted);text-align:center;padding:20px;'>No support tickets yet.</p>";
        }
        ?>
      </div>
    <?php endif; ?>
  </div>
</div>
</body>
</html>