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
}

// Handle update request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $response = ['success' => false, 'message' => '', 'log' => ''];

    switch ($action) {
        case 'download':
            logDeployment("🔄 Starting GitHub download deployment by admin");
            
            // GitHub repository details
            $owner = 'jasonzli-DEV';
            $repo = 'enderbit';
            $branch = 'main';
            $zipUrl = "https://github.com/{$owner}/{$repo}/archive/refs/heads/{$branch}.zip";
            
            // Download location
            $tempZip = sys_get_temp_dir() . '/enderbit-update.zip';
            $extractDir = sys_get_temp_dir() . '/enderbit-update';
            
            // Step 1: Download ZIP from GitHub
            logDeployment("📡 Downloading from GitHub...");
            $zipContent = @file_get_contents($zipUrl);
            
            if ($zipContent === false) {
                $response['message'] = "❌ Failed to download from GitHub. Check your internet connection.";
                logDeployment("❌ Download failed");
                break;
            }
            
            file_put_contents($tempZip, $zipContent);
            logDeployment("✅ Download successful (" . number_format(strlen($zipContent)) . " bytes)");
            
            // Step 2: Extract ZIP
            logDeployment("📦 Extracting files...");
            $zip = new ZipArchive;
            
            if ($zip->open($tempZip) !== TRUE) {
                $response['message'] = "❌ Failed to extract ZIP file.";
                logDeployment("❌ Extraction failed");
                unlink($tempZip);
                break;
            }
            
            // Remove old extraction directory if exists
            if (is_dir($extractDir)) {
                deleteDirectory($extractDir);
            }
            
            mkdir($extractDir, 0755, true);
            $zip->extractTo($extractDir);
            $zip->close();
            logDeployment("✅ Extraction successful");
            
            // Step 3: Copy files (skip config.php and JSON files)
            logDeployment("📋 Copying files...");
            $sourceDir = $extractDir . "/{$repo}-{$branch}/httpdocs";
            $targetDir = __DIR__;
            
            if (!is_dir($sourceDir)) {
                $response['message'] = "❌ Source directory not found in downloaded files.";
                logDeployment("❌ Source directory not found: " . $sourceDir);
                break;
            }
            
            $updatedFiles = [];
            $skippedFiles = ['config.php', 'tokens.json', 'tickets.json', 'settings.json', 'deployment.log'];
            
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($sourceDir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );
            
            foreach ($files as $file) {
                $relativePath = str_replace($sourceDir . '/', '', $file->getPathname());
                $targetPath = $targetDir . '/' . $relativePath;
                
                // Skip protected files
                if (in_array(basename($file), $skippedFiles)) {
                    continue;
                }
                
                if ($file->isDir()) {
                    if (!is_dir($targetPath)) {
                        mkdir($targetPath, 0755, true);
                    }
                } else {
                    copy($file->getPathname(), $targetPath);
                    $updatedFiles[] = $relativePath;
                }
            }
            
            // Step 4: Get latest commit SHA and update version file
            logDeployment("📝 Updating version info...");
            
            $apiUrl = "https://api.github.com/repos/{$owner}/{$repo}/commits/main";
            $context = stream_context_create([
                'http' => [
                    'header' => "User-Agent: EnderBit-Updater\r\n"
                ]
            ]);
            
            $commitData = @file_get_contents($apiUrl, false, $context);
            if ($commitData !== false) {
                $commit = json_decode($commitData, true);
                $versionInfo = [
                    'version' => substr($commit['sha'], 0, 7),
                    'updated' => date('Y-m-d H:i:s')
                ];
                file_put_contents($targetDir . '/version.json', json_encode($versionInfo, JSON_PRETTY_PRINT));
                logDeployment("✅ Version updated to: " . $versionInfo['version']);
            }
            
            // Step 5: Cleanup
            unlink($tempZip);
            deleteDirectory($extractDir);
            
            $response['success'] = true;
            $response['message'] = "✅ Successfully updated " . count($updatedFiles) . " files!";
            $response['log'] = "Updated files:\n" . implode("\n", array_slice($updatedFiles, 0, 50));
            if (count($updatedFiles) > 50) {
                $response['log'] .= "\n... and " . (count($updatedFiles) - 50) . " more files";
            }
            
            logDeployment("✅ Update successful - " . count($updatedFiles) . " files updated");
            logDeployment("Protected files preserved: " . implode(', ', $skippedFiles));
            break;

        case 'check':
            logDeployment("📊 Checking for updates");
            
            // Get current version
            $versionFile = __DIR__ . '/version.json';
            $currentVersion = 'unknown';
            $currentDate = 'unknown';
            
            if (file_exists($versionFile)) {
                $versionData = json_decode(file_get_contents($versionFile), true);
                $currentVersion = $versionData['version'] ?? 'unknown';
                $currentDate = $versionData['updated'] ?? 'unknown';
            }
            
            // Get latest commit info from GitHub API
            $owner = 'jasonzli-DEV';
            $repo = 'enderbit';
            $apiUrl = "https://api.github.com/repos/{$owner}/{$repo}/commits/main";
            
            $context = stream_context_create([
                'http' => [
                    'header' => "User-Agent: EnderBit-Updater\r\n"
                ]
            ]);
            
            $commitData = @file_get_contents($apiUrl, false, $context);
            
            if ($commitData === false) {
                $response['message'] = "❌ Failed to check GitHub for updates.";
                break;
            }
            
            $commit = json_decode($commitData, true);
            $latestVersion = substr($commit['sha'], 0, 7);
            $latestDate = date('Y-m-d H:i:s', strtotime($commit['commit']['author']['date']));
            
            // Compare versions
            $isUpToDate = ($currentVersion === $latestVersion);
            
            $response['success'] = true;
            
            if ($isUpToDate) {
                $response['message'] = "✅ Your site is up to date!";
            } else {
                $response['message'] = "🔔 Update available! Your site is out of date.";
            }
            
            $response['log'] = "═══════════════════════════════════\n" .
                              "CURRENT VERSION\n" .
                              "═══════════════════════════════════\n" .
                              "Version: " . $currentVersion . "\n" .
                              "Updated: " . $currentDate . "\n\n" .
                              "═══════════════════════════════════\n" .
                              "LATEST VERSION (GitHub)\n" .
                              "═══════════════════════════════════\n" .
                              "Version: " . $latestVersion . "\n" .
                              "Date: " . $latestDate . "\n" .
                              "Author: " . $commit['commit']['author']['name'] . "\n" .
                              "Message: " . $commit['commit']['message'] . "\n\n" .
                              "═══════════════════════════════════\n" .
                              "STATUS\n" .
                              "═══════════════════════════════════\n" .
                              ($isUpToDate ? "✅ UP TO DATE - No updates needed" : "🔔 OUT OF DATE - Update available!");
            
            logDeployment("✅ Check completed - " . ($isUpToDate ? "Up to date" : "Update available"));
            break;

        case 'log':
            logDeployment("📜 Viewing deployment log");
            $response['success'] = true;
            $response['message'] = "📜 Deployment Log";
            
            if (file_exists($logFile)) {
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

// Helper function to delete directory recursively
function deleteDirectory($dir) {
    if (!is_dir($dir)) return;
    
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    
    foreach ($files as $fileinfo) {
        if ($fileinfo->isDir()) {
            rmdir($fileinfo->getRealPath());
        } else {
            unlink($fileinfo->getRealPath());
        }
    }
    
    rmdir($dir);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>System Update - EnderBit Admin</title>
  <link rel="icon" type="image/png" sizes="96x96" href="/icon.png">
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
      <h3>ℹ️ Simple GitHub Updater</h3>
      <ul>
        <li>Downloads latest code directly from GitHub</li>
        <li>No Git installation required on server</li>
        <li>Protects your config.php and data files</li>
        <li>Safe and easy to use</li>
      </ul>
    </div>

    <div class="card">
      <h2>🚀 Deployment Actions</h2>
      <div class="action-buttons">
        <button class="btn btn-primary" onclick="downloadUpdate()" id="downloadBtn">
          <span>⬇️ Download & Install Update</span>
          <div class="spinner" id="downloadSpinner"></div>
        </button>
        <button class="btn btn-secondary" onclick="checkUpdates()" id="checkBtn">
          <span>🔍 Check for Updates</span>
          <div class="spinner" id="checkSpinner"></div>
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
      const spinner = spinnerId ? document.getElementById(spinnerId) : null;
      const statusMsg = document.getElementById('statusMessage');
      const outputBox = document.getElementById('outputBox');

      btn.disabled = true;
      if (spinner) spinner.style.display = 'inline-block';
      statusMsg.classList.remove('show', 'success', 'error');
      outputBox.classList.remove('show');

      try {
        const response = await fetch('update.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: `action=${action}`
        });

        const data = await response.json();

        statusMsg.textContent = data.message;
        statusMsg.classList.add('show');
        statusMsg.classList.add(data.success ? 'success' : 'error');

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

    function downloadUpdate() {
      if (confirm('⚠️ This will download and install updates from GitHub.\n\nYour config.php and data files will be preserved.\n\nContinue?')) {
        executeAction('download', 'downloadBtn', 'downloadSpinner');
      }
    }

    function checkUpdates() {
      executeAction('check', 'checkBtn', 'checkSpinner');
    }

    function viewLog() {
      executeAction('log', 'logBtn', null);
    }
  </script>
</body>
</html>
