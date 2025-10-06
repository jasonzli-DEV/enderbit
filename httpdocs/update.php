<?php
session_start();
require_once __DIR__ . '/config.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin.php");
    exit;
}

// Deployment log file
$logFile = __DIR__ . '/deployment.log';

// Function to log deployment activities
function logDeployment($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] $message\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND);
    return $logEntry;
}

// Function to execute git command and capture output
function executeGitCommand($command) {
    $output = [];
    $returnVar = 0;
    exec($command . ' 2>&1', $output, $returnVar);
    return [
        'success' => $returnVar === 0,
        'output' => implode("\n", $output),
        'code' => $returnVar
    ];
}

// Handle update request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $response = ['success' => false, 'message' => '', 'log' => ''];

    // Change to project directory
    $projectDir = dirname(__DIR__);
    chdir($projectDir);

    switch ($action) {
        case 'pull':
            logDeployment("🔄 Starting Git pull deployment by " . ($_SESSION['admin_email'] ?? 'admin'));
            
            // Step 1: Check Git status
            $status = executeGitCommand('git status --porcelain');
            if (!empty(trim($status['output']))) {
                logDeployment("⚠️ Warning: Uncommitted changes detected");
                $response['message'] = "Warning: You have uncommitted local changes. ";
            }

            // Step 2: Fetch latest changes
            logDeployment("📡 Fetching latest changes from remote...");
            $fetch = executeGitCommand('git fetch origin');
            if (!$fetch['success']) {
                $response['message'] = "❌ Failed to fetch from remote: " . $fetch['output'];
                logDeployment("❌ Fetch failed: " . $fetch['output']);
                break;
            }
            logDeployment("✅ Fetch successful");

            // Step 3: Pull changes (try main branch first, fallback to master)
            logDeployment("⬇️ Pulling changes...");
            $pull = executeGitCommand('git pull origin main --no-rebase');
            
            // If main doesn't exist, try master
            if (!$pull['success'] && strpos($pull['output'], "couldn't find remote ref main") !== false) {
                logDeployment("ℹ️ Main branch not found, trying master...");
                $pull = executeGitCommand('git pull origin master --no-rebase');
            }
            
            if ($pull['success']) {
                $response['success'] = true;
                
                // Check if already up to date
                if (strpos($pull['output'], 'Already up to date') !== false) {
                    $response['message'] .= "ℹ️ Already up to date. No changes pulled.";
                    logDeployment("ℹ️ No changes - already up to date");
                } else {
                    $response['message'] .= "✅ Successfully pulled latest changes!";
                    logDeployment("✅ Pull successful");
                }
                
                $response['log'] = $pull['output'];
            } else {
                // Check if it's a merge conflict
                if (strpos($pull['output'], 'CONFLICT') !== false) {
                    $response['message'] = "⚠️ Merge conflict detected. Please resolve manually via terminal.";
                    logDeployment("❌ Merge conflict: " . $pull['output']);
                } else {
                    $response['message'] = "❌ Pull failed: " . $pull['output'];
                    logDeployment("❌ Pull failed: " . $pull['output']);
                }
                $response['log'] = $pull['output'];
            }
            break;

        case 'status':
            logDeployment("📊 Checking Git status");
            
            // Get current branch
            $branch = executeGitCommand('git rev-parse --abbrev-ref HEAD');
            
            // Get status
            $status = executeGitCommand('git status');
            
            // Get last commit
            $lastCommit = executeGitCommand('git log -1 --pretty=format:"%h - %s (%cr by %an)"');
            
            // Check if behind remote
            executeGitCommand('git fetch origin');
            $behind = executeGitCommand('git rev-list HEAD..origin/' . trim($branch['output']) . ' --count');
            
            $response['success'] = true;
            $response['message'] = "📊 Git Status Retrieved";
            $response['log'] = "Current Branch: " . trim($branch['output']) . "\n" .
                              "Last Commit: " . trim($lastCommit['output']) . "\n" .
                              "Behind Remote: " . trim($behind['output']) . " commit(s)\n\n" .
                              $status['output'];
            logDeployment("✅ Status check completed");
            break;

        case 'log':
            logDeployment("📜 Viewing deployment log");
            $response['success'] = true;
            $response['message'] = "📜 Deployment Log";
            
            if (file_exists($logFile)) {
                // Get last 50 lines
                $lines = file($logFile);
                $lastLines = array_slice($lines, -50);
                $response['log'] = implode('', $lastLines);
            } else {
                $response['log'] = "No deployment log found.";
            }
            break;

        default:
            $response['message'] = "Invalid action";
    }

    echo json_encode($response);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>System Update - EnderBit Admin</title>
  <style>
  :root {
    --bg:#0d1117; --card:#161b22; --accent:#58a6ff; --primary:#1f6feb;
    --muted:#8b949e; --green:#238636; --red:#f85149;
    --text:#e6eef8; --input-bg:#0e1418; --input-border:#232629;
    --bg-gradient:#07101a;
  }
  [data-theme="light"] {
    --bg:#eff6ff; --card:#ffffff; --accent:#3b82f6; --primary:#2563eb;
    --muted:#64748b; --green:#16a34a; --red:#dc2626;
    --text:#1e3a8a; --input-bg:#ffffff; --input-border:#bfdbfe;
    --bg-gradient:#dbeafe;
  }

  * { margin:0; padding:0; box-sizing:border-box; }
  body {
    font-family:'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif;
    background:linear-gradient(180deg,var(--bg),var(--bg-gradient));
    color:var(--text);
    min-height:100vh;
    padding:40px 20px;
  }

  .container {
    max-width:900px;
    margin:0 auto;
  }

  .header {
    display:flex;
    align-items:center;
    justify-content:space-between;
    margin-bottom:32px;
    flex-wrap:wrap;
    gap:16px;
  }

  .header h1 {
    font-size:28px;
    font-weight:600;
  }

  .back-btn {
    background:var(--card);
    color:var(--text);
    border:1px solid var(--input-border);
    padding:10px 20px;
    border-radius:8px;
    cursor:pointer;
    text-decoration:none;
    display:inline-flex;
    align-items:center;
    gap:8px;
    font-size:14px;
    transition:all 0.2s;
  }

  .back-btn:hover {
    background:var(--input-bg);
    border-color:var(--accent);
  }

  .card {
    background:var(--card);
    border:1px solid var(--input-border);
    border-radius:12px;
    padding:24px;
    margin-bottom:20px;
  }

  .card h2 {
    font-size:20px;
    margin-bottom:16px;
    display:flex;
    align-items:center;
    gap:8px;
  }

  .action-buttons {
    display:flex;
    gap:12px;
    flex-wrap:wrap;
  }

  .btn {
    padding:12px 24px;
    border-radius:8px;
    border:none;
    font-size:14px;
    font-weight:500;
    cursor:pointer;
    transition:all 0.2s;
    display:inline-flex;
    align-items:center;
    gap:8px;
  }

  .btn-primary {
    background:var(--accent);
    color:white;
  }

  .btn-primary:hover {
    background:var(--primary);
    transform:translateY(-2px);
    box-shadow:0 4px 12px rgba(59,130,246,0.3);
  }

  .btn-secondary {
    background:var(--input-bg);
    color:var(--text);
    border:1px solid var(--input-border);
  }

  .btn-secondary:hover {
    background:var(--card);
    border-color:var(--accent);
  }

  .btn:disabled {
    opacity:0.5;
    cursor:not-allowed;
  }

  .output-box {
    background:var(--input-bg);
    border:1px solid var(--input-border);
    border-radius:8px;
    padding:16px;
    margin-top:16px;
    font-family:'Courier New', monospace;
    font-size:13px;
    line-height:1.6;
    white-space:pre-wrap;
    word-wrap:break-word;
    max-height:400px;
    overflow-y:auto;
    display:none;
  }

  .output-box.show {
    display:block;
  }

  .status-message {
    padding:12px 16px;
    border-radius:8px;
    margin-top:16px;
    display:none;
    align-items:center;
    gap:8px;
  }

  .status-message.show {
    display:flex;
  }

  .status-message.success {
    background:rgba(35,134,54,0.1);
    border:1px solid var(--green);
    color:var(--green);
  }

  .status-message.error {
    background:rgba(248,81,73,0.1);
    border:1px solid var(--red);
    color:var(--red);
  }

  .status-message.warning {
    background:rgba(240,136,62,0.1);
    border:1px solid #f0883e;
    color:#f0883e;
  }

  .spinner {
    border:3px solid var(--input-border);
    border-top:3px solid var(--accent);
    border-radius:50%;
    width:20px;
    height:20px;
    animation:spin 1s linear infinite;
    display:none;
  }

  @keyframes spin {
    0% { transform:rotate(0deg); }
    100% { transform:rotate(360deg); }
  }

  .info-box {
    background:rgba(59,130,246,0.1);
    border:1px solid var(--accent);
    border-radius:8px;
    padding:16px;
    margin-bottom:20px;
    color:var(--text);
  }

  .info-box h3 {
    font-size:16px;
    margin-bottom:8px;
    color:var(--accent);
  }

  .info-box ul {
    margin-left:20px;
    line-height:1.8;
  }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <h1>🔄 System Update</h1>
      <a href="admin.php" class="back-btn">← Back to Admin</a>
    </div>

    <div class="info-box">
      <h3>ℹ️ Before You Start</h3>
      <ul>
        <li>Make sure Git is configured on your server</li>
        <li>Ensure SSH keys or credentials are set up for remote access</li>
        <li>Backup important data before pulling changes</li>
        <li>Check deployment logs for any issues</li>
      </ul>
    </div>

    <div class="card">
      <h2>🚀 Deployment Actions</h2>
      <div class="action-buttons">
        <button class="btn btn-primary" onclick="pullChanges()" id="pullBtn">
          <span>⬇️ Pull Latest Changes</span>
          <div class="spinner" id="pullSpinner"></div>
        </button>
        <button class="btn btn-secondary" onclick="checkStatus()" id="statusBtn">
          <span>📊 Check Git Status</span>
          <div class="spinner" id="statusSpinner"></div>
        </button>
        <button class="btn btn-secondary" onclick="viewLog()" id="logBtn">
          📜 View Deployment Log
        </button>
      </div>

      <div class="status-message" id="statusMessage"></div>
      <div class="output-box" id="outputBox"></div>
    </div>
  </div>

  <script>
    async function executeAction(action, btnId, spinnerId) {
      const btn = document.getElementById(btnId);
      const spinner = document.getElementById(spinnerId);
      const statusMsg = document.getElementById('statusMessage');
      const outputBox = document.getElementById('outputBox');

      // Disable button and show spinner
      btn.disabled = true;
      if (spinner) spinner.style.display = 'inline-block';
      statusMsg.classList.remove('show', 'success', 'error', 'warning');
      outputBox.classList.remove('show');

      try {
        const response = await fetch('update.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: `action=${action}`
        });

        const data = await response.json();

        // Show status message
        statusMsg.textContent = data.message;
        statusMsg.classList.add('show');
        
        if (data.success) {
          statusMsg.classList.add('success');
        } else {
          statusMsg.classList.add('error');
        }

        // Show output log if available
        if (data.log) {
          outputBox.textContent = data.log;
          outputBox.classList.add('show');
        }

      } catch (error) {
        statusMsg.textContent = '❌ Error: ' + error.message;
        statusMsg.classList.add('show', 'error');
      } finally {
        btn.disabled = false;
        if (spinner) spinner.style.display = 'none';
      }
    }

    function pullChanges() {
      if (confirm('⚠️ This will pull the latest changes from Git. Continue?')) {
        executeAction('pull', 'pullBtn', 'pullSpinner');
      }
    }

    function checkStatus() {
      executeAction('status', 'statusBtn', 'statusSpinner');
    }

    function viewLog() {
      executeAction('log', 'logBtn', null);
    }
  </script>
</body>
</html>
