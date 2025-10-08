<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/logger.php';
require_once __DIR__ . '/timezone_utils.php';

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
            EnderBitLogger::logAuth('ADMIN_LOGIN_SUCCESS', ['admin' => true]);
            header("Location: admin.php");
            exit;
        } else {
            EnderBitLogger::logAuth('ADMIN_LOGIN_FAILED', ['admin' => true, 'reason' => 'Invalid password']);
            EnderBitLogger::logSecurity('ADMIN_LOGIN_ATTEMPT_FAILED', 'MEDIUM', ['reason' => 'Invalid password']);
            $msg = "Invalid password.";
            $type = "error";
        }
    }
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
                    header("Location: admin.php?msg=" . urlencode("Approval failed - please try again") . "&type=error");
                    exit;
                }
                
                if ($result) {
                    EnderBitLogger::logAdmin('USER_APPROVAL_SUCCESS', 'APPROVE_USER', ['email' => $emailToApprove]);
                    EnderBitLogger::logRegistration('USER_APPROVED_AND_CREATED', $emailToApprove);
                } else {
                    EnderBitLogger::logAdmin('USER_APPROVAL_PTERODACTYL_FAILED', 'APPROVE_USER', ['email' => $emailToApprove]);
                }
                
                header("Location: admin.php?msg=" . urlencode("User approved and created!") . "&type=success");
                exit;
            }
        }
    }
    header("Location: admin.php?msg=" . urlencode("User not found.") . "&type=error");
    exit;
}

// Handle dashboard customization
$dashboardConfigFile = __DIR__ . '/dashboard_config.json';
if (isset($_POST['save_dashboard_stats'])) {
    $config = [
        'stat1' => $_POST['stat1'] ?? 'last_backup',
        'stat2' => $_POST['stat2'] ?? 'open_tickets',
        'stat3' => $_POST['stat3'] ?? 'today_activity',
        'stat4' => $_POST['stat4'] ?? 'security_alerts'
    ];
    file_put_contents($dashboardConfigFile, json_encode($config, JSON_PRETTY_PRINT));
    header("Location: admin.php?msg=" . urlencode("Dashboard stats updated!") . "&type=success");
    exit;
}

// Load dashboard configuration
$defaultConfig = [
    'stat1' => 'last_backup',
    'stat2' => 'open_tickets',
    'stat3' => 'today_activity',
    'stat4' => 'security_alerts'
];
if (file_exists($dashboardConfigFile)) {
    $dashboardConfig = json_decode(file_get_contents($dashboardConfigFile), true);
    if (!is_array($dashboardConfig)) {
        $dashboardConfig = $defaultConfig;
    }
} else {
    $dashboardConfig = $defaultConfig;
}

// Define available stat options
$availableStats = [
    'last_backup' => [
        'label' => 'Last Backup',
        'icon' => '💾',
        'color' => '#3b82f6'
    ],
    'open_tickets' => [
        'label' => 'Open Tickets',
        'icon' => '🟢',
        'color' => '#22c55e'
    ],
    'closed_tickets' => [
        'label' => 'Closed Tickets',
        'icon' => '🔴',
        'color' => '#ef4444'
    ],
    'total_tickets' => [
        'label' => 'Total Tickets',
        'icon' => '📊',
        'color' => '#8b949e'
    ],
    'today_activity' => [
        'label' => 'Today\'s Activity',
        'icon' => '📈',
        'color' => '#3b82f6'
    ],
    'security_alerts' => [
        'label' => 'Security Alerts (24h)',
        'icon' => '🛡️',
        'color' => '#ef4444'
    ],
    'pending_users' => [
        'label' => 'Pending Users',
        'icon' => '👥',
        'color' => '#f59e0b'
    ],
    'total_users' => [
        'label' => 'Total Users',
        'icon' => '👤',
        'color' => '#8b949e'
    ],
    'server_time' => [
        'label' => 'Server Time',
        'icon' => '🕐',
        'color' => '#8b949e'
    ],
    'uptime' => [
        'label' => 'System Uptime',
        'icon' => '⏱️',
        'color' => '#22c55e'
    ]
];

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
    --muted:#8b949e; --green:#238636; --red:#f85149; --yellow:#f0883e;
    --text:#e6eef8; --input-bg:#0e1418; --input-border:#232629;
  }
  html,body{height:100%;margin:0;font-family:Inter,Arial,sans-serif;background:var(--bg);color:var(--text);}
  .page{min-height:100%;padding:28px;box-sizing:border-box;}
  .container{max-width:1400px;margin:0 auto;}
  .card{background:var(--card);border:1px solid var(--input-border);border-radius:12px;padding:24px;box-shadow:0 4px 12px rgba(0,0,0,.3);}
  .container{max-width:1400px;margin:0 auto;}
  .card{background:var(--card);border:1px solid var(--input-border);border-radius:12px;padding:24px;box-shadow:0 4px 12px rgba(0,0,0,.3);}
  .login-wrapper{min-height:100vh;display:flex;align-items:center;justify-content:center;}
  .login-card{max-width:420px;width:100%;margin:0 auto;}
  h1{margin:0 0 24px;color:var(--accent);font-size:32px;}
  h2{margin:0 0 16px;color:var(--accent);font-size:24px;}
  .page-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:32px;}
  .page-header h1{margin:0;}
  .quick-actions{display:flex;gap:12px;flex-wrap:wrap;}
  input[type=password]{width:100%;padding:14px;margin:10px 0;border-radius:8px;border:1px solid var(--input-border);background:var(--input-bg);color:var(--text);font-size:15px;box-sizing:border-box;}
  input[type=checkbox]{width:18px;height:18px;margin-right:8px;}
  label{display:flex;align-items:center;margin:12px 0;color:var(--text);cursor:pointer;font-size:15px;}
  label:hover{color:var(--accent);}
  .btn{display:inline-block;padding:12px 24px;border-radius:8px;font-weight:600;font-size:14px;text-align:center;cursor:pointer;border:0;text-decoration:none;transition:all .2s;box-sizing:border-box;}
  .btn-block{display:block;width:100%;}
  .btn-primary{background:var(--primary);color:#fff;}
  .btn-secondary{background:var(--input-bg);color:var(--text);border:1px solid var(--input-border);}
  .btn-danger{background:var(--red);color:#fff;}
  .btn-success{background:var(--green);color:#fff;}
  .btn-primary:hover,.btn-secondary:hover,.btn-danger:hover,.btn-success:hover{opacity:.9;transform:translateY(-1px);}
  .settings-card{margin-bottom:24px;}
  table{width:100%;border-collapse:collapse;}
  table th,table td{border-bottom:1px solid var(--input-border);padding:14px 12px;text-align:left;}
  table th{background:var(--input-bg);color:var(--accent);font-weight:600;font-size:13px;text-transform:uppercase;letter-spacing:0.5px;}
  table tr:last-child td{border-bottom:none;}
  table tr:hover{background:var(--input-bg);}
  .ticket-section{margin-top:24px;}
  .ticket-card{background:var(--card);border:1px solid var(--input-border);border-radius:10px;padding:20px;margin-bottom:16px;transition:all .2s;}
  .ticket-card:hover{border-color:var(--accent);box-shadow:0 2px 8px rgba(88,166,255,.1);}
  .ticket-header{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:12px;padding-bottom:12px;border-bottom:1px solid var(--input-border);}
  .ticket-title{font-size:18px;font-weight:600;color:var(--text);margin-bottom:8px;}
  .ticket-meta{font-size:13px;color:var(--muted);line-height:1.6;}
  .ticket-description{background:var(--input-bg);padding:14px;border-radius:8px;margin:12px 0;line-height:1.6;white-space:pre-wrap;}
  .ticket-replies{margin-top:16px;padding-top:16px;border-top:1px solid var(--input-border);}
  .reply-item{background:var(--input-bg);padding:12px;border-radius:8px;margin:10px 0;border-left:3px solid var(--yellow);}
  .reply-meta{font-size:12px;color:var(--muted);margin-bottom:6px;}
  .reply-form textarea{width:100%;padding:12px;border-radius:8px;border:1px solid var(--input-border);background:var(--input-bg);color:var(--text);font-family:inherit;resize:vertical;min-height:80px;margin-top:10px;box-sizing:border-box;}
  .status-badge{display:inline-block;padding:6px 12px;border-radius:6px;font-size:12px;font-weight:600;}
  .status-open{background:rgba(34,197,94,.15);color:#22c55e;}
  .status-closed{background:rgba(239,68,68,.15);color:#ef4444;}
  .btn-small{padding:8px 16px;font-size:13px;}
  .btn-secondary.btn-small{padding:8px 16px;font-size:13px;}
  button.btn-small{padding:8px 16px;font-size:13px;}
  .view-ticket-btn{display:inline-block;padding:10px 20px;background:var(--accent);color:#fff;text-decoration:none;border-radius:6px;font-size:14px;font-weight:600;transition:all .2s;}
  .view-ticket-btn:hover{opacity:.9;transform:translateY(-1px);}
  .stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:20px;margin-bottom:32px;}
  .stat-card{background:var(--card);border:1px solid var(--input-border);border-radius:12px;padding:24px;text-align:center;transition:all .2s;}
  .stat-card:hover{transform:translateY(-2px);box-shadow:0 4px 12px rgba(88,166,255,.15);border-color:var(--accent);}
  .stat-value{font-size:36px;font-weight:700;color:var(--accent);margin:12px 0;}
  .stat-label{font-size:13px;color:var(--muted);text-transform:uppercase;letter-spacing:1px;font-weight:600;}
  .stat-icon{font-size:28px;margin-bottom:8px;}
  .update-badge{background:var(--red);color:#fff;font-size:10px;padding:4px 8px;border-radius:12px;margin-left:8px;font-weight:700;animation:pulse 2s infinite;}
  @keyframes pulse{0%,100%{opacity:1}50%{opacity:.6}}
  .section-header{display:flex;justify-content:space-between;align-items:center;margin:32px 0 16px;}
  .section-header h2{margin:0;font-size:22px;}
  .filter-controls{background:var(--input-bg);border:1px solid var(--input-border);border-radius:10px;padding:20px;margin-bottom:20px;}
  .search-input{width:100%;padding:12px;border-radius:8px;border:1px solid var(--input-border);background:var(--card);color:var(--text);font-size:14px;margin-bottom:12px;box-sizing:border-box;}
  .search-input:focus{outline:none;border-color:var(--accent);}
  .filter-row{display:flex;gap:12px;flex-wrap:wrap;align-items:center;}
  .filter-select{flex:1;min-width:150px;padding:10px;border-radius:8px;border:1px solid var(--input-border);background:var(--card);color:var(--text);font-size:14px;}
  .filter-select:focus{outline:none;border-color:var(--accent);}
  .filter-results{margin-top:12px;font-size:13px;color:var(--muted);text-align:center;}
  .empty-state{text-align:center;padding:60px 20px;color:var(--muted);}
  .empty-state h3{color:var(--text);margin-bottom:8px;}

  /* Management Cards */
  .management-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:24px;margin-bottom:32px;}
  .management-card{background:var(--card);border:1px solid var(--input-border);border-radius:12px;padding:32px;text-align:center;transition:all .3s;text-decoration:none;display:block;position:relative;}
  .management-card:hover{transform:translateY(-4px);box-shadow:0 8px 24px rgba(88,166,255,.2);border-color:var(--accent);}
  .management-icon{font-size:48px;margin-bottom:16px;}
  .management-card h3{margin:0 0 12px;color:var(--accent);font-size:20px;}
  .management-card p{margin:0;color:var(--muted);font-size:14px;line-height:1.5;}
  .management-card .badge{position:absolute;top:16px;right:16px;background:var(--red);color:#fff;font-size:11px;padding:4px 10px;border-radius:12px;font-weight:700;}

  /* Banner System */
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

  @media (max-width: 768px) {
    .page-header{flex-direction:column;align-items:flex-start;gap:16px;}
    .stats-grid{grid-template-columns:repeat(2,1fr);gap:12px;}
    .filter-row{flex-direction:column;}
    .filter-select{width:100%;}
  }
</style>
</head>
<body>
  <?php if (isset($_GET['msg'])): ?>
    <div id="banner" class="banner <?= htmlspecialchars($_GET['type'] ?? 'success') ?>">
      <span><?= htmlspecialchars($_GET['msg']) ?></span>
      <span class="close" onclick="hideBanner()">×</span>
    </div>
  <?php endif; ?>

<div class="page">
  <div class="<?= !isset($_SESSION['admin_logged_in']) ? 'login-wrapper' : 'container' ?>">
    <?php if (!isset($_SESSION['admin_logged_in'])): ?>
      <div class="card login-card">
        <h1 style="text-align:center;">Admin Login</h1>
        <?php if (!empty($msg)): ?><p style="color:var(--red);text-align:center;"><?= htmlspecialchars($msg) ?></p><?php endif; ?>
        <form method="post">
          <input type="password" name="admin_password" placeholder="Enter Password" required>
          <button type="submit" class="btn btn-primary btn-block" style="margin-top:16px;">Login</button>
        </form>
        <a href="index.php" class="btn btn-secondary btn-block" style="margin-top:12px;">
          ← Return to Home
        </a>
      </div>
    <?php else: ?>
      <?php
      // Check for updates first
      $hasUpdate = checkForUpdates();
      
      // Function to calculate stat value
      function calculateStatValue($statType) {
          $tokensFile = __DIR__ . '/tokens.json';
          $ticketsFile = __DIR__ . '/tickets.json';
          
          switch ($statType) {
              case 'last_backup':
                  $backupMetadataFile = __DIR__ . '/backups/metadata.json';
                  if (file_exists($backupMetadataFile)) {
                      $metadata = json_decode(file_get_contents($backupMetadataFile), true);
                      if (isset($metadata['sets']) && !empty($metadata['sets'])) {
                          $latestTime = 0;
                          foreach ($metadata['sets'] as $set) {
                              if ($set['created'] > $latestTime) {
                                  $latestTime = $set['created'];
                              }
                          }
                          if ($latestTime > 0) {
                              $timeDiff = time() - $latestTime;
                              if ($timeDiff < 3600) {
                                  return floor($timeDiff / 60) . 'm ago';
                              } elseif ($timeDiff < 86400) {
                                  return floor($timeDiff / 3600) . 'h ago';
                              } else {
                                  return floor($timeDiff / 86400) . 'd ago';
                              }
                          }
                      }
                  }
                  return 'Never';
              
              case 'open_tickets':
                  if (file_exists($ticketsFile)) {
                      $tickets = json_decode(file_get_contents($ticketsFile), true);
                      if (is_array($tickets)) {
                          $count = 0;
                          foreach ($tickets as $ticket) {
                              if ($ticket['status'] === 'open') $count++;
                          }
                          return $count;
                      }
                  }
                  return 0;
              
              case 'closed_tickets':
                  if (file_exists($ticketsFile)) {
                      $tickets = json_decode(file_get_contents($ticketsFile), true);
                      if (is_array($tickets)) {
                          $count = 0;
                          foreach ($tickets as $ticket) {
                              if ($ticket['status'] === 'closed') $count++;
                          }
                          return $count;
                      }
                  }
                  return 0;
              
              case 'total_tickets':
                  if (file_exists($ticketsFile)) {
                      $tickets = json_decode(file_get_contents($ticketsFile), true);
                      if (is_array($tickets)) {
                          return count($tickets);
                      }
                  }
                  return 0;
              
              case 'today_activity':
                  if (file_exists($ticketsFile)) {
                      $tickets = json_decode(file_get_contents($ticketsFile), true);
                      if (is_array($tickets)) {
                          $count = 0;
                          $today = date('Y-m-d');
                          foreach ($tickets as $ticket) {
                              if (isset($ticket['created_at']) && strpos($ticket['created_at'], $today) === 0) {
                                  $count++;
                              }
                          }
                          return $count;
                      }
                  }
                  return 0;
              
              case 'security_alerts':
                  $securityLogFile = __DIR__ . '/security.log';
                  if (file_exists($securityLogFile)) {
                      $lines = file($securityLogFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                      $yesterday = time() - 86400;
                      $count = 0;
                      foreach ($lines as $line) {
                          if (strpos($line, '"type":"SECURITY"') !== false) {
                              $logEntry = json_decode($line, true);
                              if ($logEntry && isset($logEntry['timestamp'])) {
                                  $logTime = strtotime($logEntry['timestamp']);
                                  if ($logTime >= $yesterday) {
                                      $count++;
                                  }
                              }
                          }
                      }
                      return $count;
                  }
                  return 0;
              
              case 'pending_users':
                  if (file_exists($tokensFile)) {
                      $tokens = json_decode(file_get_contents($tokensFile), true);
                      if (is_array($tokens)) {
                          return count($tokens);
                      }
                  }
                  return 0;
              
              case 'total_users':
                  // Count approved users (you can modify this based on your user storage)
                  return 0; // Placeholder - implement based on your user system
              
              case 'server_time':
                  return date('g:i A');
              
              case 'uptime':
                  // Simple uptime based on first log entry
                  $authLogFile = __DIR__ . '/auth.log';
                  if (file_exists($authLogFile)) {
                      $lines = file($authLogFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                      if (!empty($lines)) {
                          $firstLine = json_decode($lines[0], true);
                          if ($firstLine && isset($firstLine['timestamp'])) {
                              $uptime = time() - strtotime($firstLine['timestamp']);
                              $days = floor($uptime / 86400);
                              return $days . ' day' . ($days != 1 ? 's' : '');
                          }
                      }
                  }
                  return 'N/A';
              
              default:
                  return 0;
          }
      }
      
      // Get pending users count for management card badge
      $pendingUsers = calculateStatValue('pending_users');
      $openTickets = calculateStatValue('open_tickets');
      ?>
      
      <div class="page-header">
        <h1>🎛️ Admin Dashboard</h1>
        <div class="quick-actions">
          <button onclick="openCustomizeModal()" class="btn btn-secondary">
            ⚙️ Customize Stats
          </button>
          <a href="update.php" class="btn btn-secondary">
            🔄 Update<?php if ($hasUpdate): ?><span class="update-badge">NEW</span><?php endif; ?>
          </a>
        </div>
      </div>
      
      <!-- Statistics Overview -->
      <div class="stats-grid">
        <?php foreach (['stat1', 'stat2', 'stat3', 'stat4'] as $statKey): ?>
          <?php 
          $statType = $dashboardConfig[$statKey];
          $statInfo = $availableStats[$statType];
          $statValue = calculateStatValue($statType);
          $valueStyle = '';
          
          // Special styling for certain stats
          if ($statType === 'last_backup' || $statType === 'server_time') {
              $valueStyle = 'font-size:20px;';
          } elseif ($statType === 'security_alerts') {
              $color = ($statValue > 0) ? '#ef4444' : '#22c55e';
              $valueStyle = "color:{$color};";
          } else {
              $valueStyle = "color:{$statInfo['color']};";
          }
          ?>
          <div class="stat-card">
            <div class="stat-icon"><?= $statInfo['icon'] ?></div>
            <div class="stat-label"><?= htmlspecialchars($statInfo['label']) ?></div>
            <div class="stat-value" style="<?= $valueStyle ?>"><?= htmlspecialchars($statValue) ?></div>
          </div>
        <?php endforeach; ?>
      </div>

      <div class="management-grid">
        <a href="tickets_admin.php" class="management-card">
          <div class="management-icon">🎫</div>
          <h3>Ticket Management</h3>
          <p>View and manage support tickets</p>
          <?php if ($openTickets > 0): ?>
            <span class="badge"><?= $openTickets ?> open</span>
          <?php endif; ?>
        </a>

        <a href="users_admin.php" class="management-card">
          <div class="management-icon">👥</div>
          <h3>User Management</h3>
          <p>Approve pending user registrations</p>
          <?php if ($pendingUsers > 0): ?>
            <span class="badge"><?= $pendingUsers ?> pending</span>
          <?php endif; ?>
        </a>

        <a href="logs.php" class="management-card">
          <div class="management-icon">📋</div>
          <h3>System Logs</h3>
          <p>View application logs and events</p>
        </a>

        <a href="backup.php" class="management-card">
          <div class="management-icon">💾</div>
          <h3>Backup Management</h3>
          <p>Create and manage data backups</p>
        </a>
      </div>

      <!-- Settings Card -->
      <div class="card settings-card">
        <h2>⚙️ Settings</h2>
        <form method="post">
          <label>
            <input type="checkbox" name="require_email_verify" <?= !empty($settings['require_email_verify']) ? 'checked':'' ?>> 
            Require Email Verification
          </label>
          <label>
            <input type="checkbox" name="require_admin_approve" <?= !empty($settings['require_admin_approve']) ? 'checked':'' ?>> 
            Require Admin Approval
          </label>

          <div style="display:flex;gap:12px;margin-top:24px;flex-wrap:wrap;">
            <button type="submit" name="save_exit" class="btn btn-primary" style="flex:1;min-width:200px;">💾 Save and Exit</button>
            <button type="submit" name="logout" class="btn btn-danger" style="flex:1;min-width:200px;">🚪 Logout</button>
          </div>
        </form>
      </div>
    <?php endif; ?>
  </div>
</div>

<script>
// Search and Filter Functionality
(function() {
  const searchInput = document.getElementById('searchTickets');
  const filterStatus = document.getElementById('filterStatus');
  const filterPriority = document.getElementById('filterPriority');
  const filterCategory = document.getElementById('filterCategory');
  const resetBtn = document.getElementById('resetFilters');
  const filterResults = document.getElementById('filterResults');
  const ticketCards = document.querySelectorAll('.ticket-card');

  function applyFilters() {
    const searchTerm = searchInput.value.toLowerCase();
    const statusFilter = filterStatus.value;
    const priorityFilter = filterPriority.value;
    const categoryFilter = filterCategory.value;

    let visibleCount = 0;
    let totalCount = ticketCards.length;

    ticketCards.forEach(card => {
      const ticketText = card.textContent.toLowerCase();
      const ticketStatus = card.querySelector('.status-badge').textContent.toLowerCase();
      const ticketMeta = card.querySelector('.ticket-meta').textContent.toLowerCase();
      
      // Search filter
      const matchesSearch = searchTerm === '' || ticketText.includes(searchTerm);
      
      // Status filter
      const matchesStatus = statusFilter === 'all' || ticketStatus.includes(statusFilter);
      
      // Priority filter
      const matchesPriority = priorityFilter === 'all' || ticketMeta.includes(priorityFilter);
      
      // Category filter
      const matchesCategory = categoryFilter === 'all' || ticketMeta.includes(categoryFilter);

      if (matchesSearch && matchesStatus && matchesPriority && matchesCategory) {
        card.style.display = 'block';
        visibleCount++;
      } else {
        card.style.display = 'none';
      }
    });

    // Update results count
    if (searchTerm || statusFilter !== 'all' || priorityFilter !== 'all' || categoryFilter !== 'all') {
      filterResults.textContent = `Showing ${visibleCount} of ${totalCount} tickets`;
    } else {
      filterResults.textContent = '';
    }
  }

  // Add event listeners
  if (searchInput) {
    searchInput.addEventListener('input', applyFilters);
    filterStatus.addEventListener('change', applyFilters);
    filterPriority.addEventListener('change', applyFilters);
    filterCategory.addEventListener('change', applyFilters);
    
    resetBtn.addEventListener('click', function() {
      searchInput.value = '';
      filterStatus.value = 'all';
      filterPriority.value = 'all';
      filterCategory.value = 'all';
      applyFilters();
    });
  }
})();

// Banner system
function hideBanner(){
  const b = document.getElementById('banner');
  if (!b) return;
  b.classList.add('hide');
  b.classList.remove('show');
}
window.addEventListener('load', ()=>{
  const b = document.getElementById('banner');
  if (!b) return;
  // Remove hide class and add show class
  b.classList.remove('hide');
  setTimeout(()=> b.classList.add('show'), 120);
  setTimeout(()=> hideBanner(), 5000);
});

// Dashboard customization modal
function openCustomizeModal() {
  document.getElementById('customizeModal').style.display = 'flex';
}

function closeCustomizeModal() {
  document.getElementById('customizeModal').style.display = 'none';
}

// Close modal when clicking outside
window.addEventListener('click', function(e) {
  const modal = document.getElementById('customizeModal');
  if (e.target === modal) {
    closeCustomizeModal();
  }
});
</script>

<!-- Customize Stats Modal -->
<div id="customizeModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.8);z-index:9999;align-items:center;justify-content:center;">
  <div style="background:var(--card);border:1px solid var(--input-border);border-radius:12px;padding:32px;max-width:600px;width:90%;max-height:80vh;overflow-y:auto;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;">
      <h2 style="margin:0;color:var(--accent);">⚙️ Customize Dashboard Stats</h2>
      <button onclick="closeCustomizeModal()" style="background:none;border:none;color:var(--text);font-size:24px;cursor:pointer;padding:0;width:32px;height:32px;">×</button>
    </div>
    
    <p style="color:var(--muted);margin-bottom:24px;">Choose what information to display in each of the 4 dashboard statistics panels.</p>
    
    <form method="post">
      <?php foreach (['stat1' => 'Panel 1 (Top Left)', 'stat2' => 'Panel 2 (Top Right)', 'stat3' => 'Panel 3 (Bottom Left)', 'stat4' => 'Panel 4 (Bottom Right)'] as $statKey => $statLabel): ?>
        <div style="margin-bottom:20px;">
          <label style="display:block;color:var(--accent);font-weight:600;margin-bottom:8px;"><?= $statLabel ?></label>
          <select name="<?= $statKey ?>" style="width:100%;padding:12px;border-radius:8px;border:1px solid var(--input-border);background:var(--card);color:var(--text);font-size:14px;">
            <?php foreach ($availableStats as $key => $info): ?>
              <option value="<?= $key ?>" <?= ($dashboardConfig[$statKey] === $key) ? 'selected' : '' ?>>
                <?= $info['icon'] ?> <?= htmlspecialchars($info['label']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      <?php endforeach; ?>
      
      <div style="display:flex;gap:12px;margin-top:32px;">
        <button type="submit" name="save_dashboard_stats" class="btn btn-primary" style="flex:1;">
          💾 Save Changes
        </button>
        <button type="button" onclick="closeCustomizeModal()" class="btn btn-secondary" style="flex:1;">
          Cancel
        </button>
      </div>
    </form>
  </div>
</div>

</body>
</html>