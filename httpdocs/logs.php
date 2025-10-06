<?php
session_start();
require_once __DIR__ . '/config.php';

// Check admin authentication
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin.php");
    exit;
}

// Get log file path from query or default to PHP error log
$logType = $_GET['type'] ?? 'php';
$logFiles = [
    'php' => __DIR__ . '/php_errors.log',
    'apache' => __DIR__ . '/apache_errors.log',
    'nginx' => __DIR__ . '/nginx_errors.log',
    'custom' => __DIR__ . '/custom.log'
];

$logFile = $logFiles[$logType] ?? $logFiles['php'];
$lines = (int)($_GET['lines'] ?? 200);
$search = $_GET['search'] ?? '';

// Create log file if it doesn't exist
if (!file_exists($logFile)) {
    @touch($logFile);
    @chmod($logFile, 0666);
}

// Function to read last N lines of a file
function tail($filename, $lines = 200) {
    if (!file_exists($filename)) {
        return "Log file does not exist. It will be created when errors are logged.";
    }
    
    if (!is_readable($filename)) {
        return "Log file is not readable. Check file permissions.";
    }
    
    if (filesize($filename) === 0) {
        return "Log file is empty. No errors have been logged yet.";
    }
    
    $file = new SplFileObject($filename, 'r');
    $file->seek(PHP_INT_MAX);
    $lastLine = $file->key();
    $startLine = max(0, $lastLine - $lines);
    
    $output = [];
    $file->seek($startLine);
    while (!$file->eof()) {
        $line = $file->current();
        if ($line !== false) {
            $output[] = $line;
        }
        $file->next();
    }
    
    return implode('', $output);
}

$logContent = tail($logFile, $lines);

// Filter by search term
if (!empty($search)) {
    $logLines = explode("\n", $logContent);
    $filteredLines = array_filter($logLines, function($line) use ($search) {
        return stripos($line, $search) !== false;
    });
    $logContent = implode("\n", $filteredLines);
}
?>
<!doctype html>
<html lang="en" data-theme="dark">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>System Logs - EnderBit Admin</title>
<link rel="icon" type="image/png" sizes="96x96" href="/icon.png">
<style>
  :root {
    --bg:#0d1117; --card:#161b22; --accent:#58a6ff; --primary:#1f6feb;
    --muted:#8b949e; --green:#238636; --red:#f85149; --yellow:#f0883e;
    --text:#e6eef8; --input-bg:#0e1418; --input-border:#232629;
  }
  [data-theme="light"] {
    --bg:#eff6ff; --card:#ffffff; --accent:#3b82f6; --primary:#2563eb;
    --muted:#64748b; --green:#16a34a; --red:#dc2626; --yellow:#ea580c;
    --text:#1e3a8a; --input-bg:#ffffff; --input-border:#bfdbfe;
  }

  * { margin:0; padding:0; box-sizing:border-box; }
  html,body { height:100%; font-family:'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif;
    background:var(--bg); color:var(--text); }

  .page {
    max-width:1400px;
    margin:0 auto;
    padding:24px;
  }

  .header {
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:24px;
    padding-bottom:16px;
    border-bottom:2px solid var(--input-border);
  }
  
  .header h1 {
    color:var(--accent);
    font-size:28px;
  }

  .controls {
    background:var(--card);
    border:1px solid var(--input-border);
    border-radius:12px;
    padding:20px;
    margin-bottom:20px;
  }

  .control-row {
    display:flex;
    gap:12px;
    flex-wrap:wrap;
    align-items:center;
    margin-bottom:12px;
  }

  .control-row:last-child {
    margin-bottom:0;
  }

  label {
    font-weight:600;
    color:var(--muted);
    font-size:14px;
  }

  select, input[type="text"], input[type="number"] {
    padding:10px 14px;
    border-radius:8px;
    border:1px solid var(--input-border);
    background:var(--input-bg);
    color:var(--text);
    font-size:14px;
  }

  .btn {
    padding:10px 20px;
    border-radius:8px;
    border:none;
    font-weight:600;
    font-size:14px;
    cursor:pointer;
    text-decoration:none;
    display:inline-block;
    transition:opacity .2s;
  }

  .btn-primary {
    background:var(--primary);
    color:#fff;
  }

  .btn-secondary {
    background:var(--card);
    color:var(--text);
    border:1px solid var(--input-border);
  }

  .btn:hover {
    opacity:.9;
  }

  .log-container {
    background:var(--card);
    border:1px solid var(--input-border);
    border-radius:12px;
    padding:20px;
    overflow:auto;
  }

  .log-content {
    background:#000;
    color:#0f0;
    font-family:'Courier New', Consolas, monospace;
    font-size:13px;
    padding:16px;
    border-radius:8px;
    overflow-x:auto;
    white-space:pre-wrap;
    word-wrap:break-word;
    max-height:calc(100vh - 400px);
    line-height:1.5;
  }

  .log-info {
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:12px;
    padding-bottom:12px;
    border-bottom:1px solid var(--input-border);
    font-size:13px;
    color:var(--muted);
  }

  .error { color:var(--red); }
  .warning { color:var(--yellow); }
  .success { color:var(--green); }

  @media (max-width: 768px) {
    .control-row {
      flex-direction:column;
      align-items:stretch;
    }
    .control-row > * {
      width:100%;
    }
  }
</style>
</head>
<body>
  <div class="page">
    <div class="header">
      <h1>📋 System Logs</h1>
      <a href="/admin.php" class="btn btn-secondary">← Back to Admin</a>
    </div>

    <div class="controls">
      <form method="get" action="logs.php">
        <div class="control-row">
          <label for="type">Log Type:</label>
          <select name="type" id="type">
            <option value="php" <?= $logType === 'php' ? 'selected' : '' ?>>PHP Error Log</option>
            <option value="apache" <?= $logType === 'apache' ? 'selected' : '' ?>>Apache Error Log</option>
            <option value="nginx" <?= $logType === 'nginx' ? 'selected' : '' ?>>Nginx Error Log</option>
            <option value="custom" <?= $logType === 'custom' ? 'selected' : '' ?>>Custom Log</option>
          </select>

          <label for="lines">Lines:</label>
          <input type="number" name="lines" id="lines" value="<?= $lines ?>" min="10" max="10000" style="width:100px;">

          <label for="search">Search:</label>
          <input type="text" name="search" id="search" value="<?= htmlspecialchars($search) ?>" placeholder="Filter logs..." style="flex:1;min-width:200px;">

          <button type="submit" class="btn btn-primary">🔍 Load Logs</button>
          <button type="button" onclick="window.location.href='logs.php?type=<?= $logType ?>&lines=<?= $lines ?>'" class="btn btn-secondary">🔄 Refresh</button>
        </div>
      </form>
    </div>

    <div class="log-container">
      <div class="log-info">
        <span><strong>Log File:</strong> <?= htmlspecialchars($logFile) ?></span>
        <span><strong>Lines:</strong> <?= $lines ?></span>
      </div>
      <div class="log-content"><?= htmlspecialchars($logContent) ?: '<span style="color:var(--muted);">No logs found or log file is empty.</span>' ?></div>
    </div>
  </div>

<script>
// Theme toggle
const saved = localStorage.getItem("theme");
if(saved){
  document.documentElement.setAttribute("data-theme", saved);
}
</script>
</body>
</html>
