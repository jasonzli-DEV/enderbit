<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/logger.php';

// Check admin authentication
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin.php");
    exit;
}

// Get log file path from query or default to auth log
$logType = $_GET['type'] ?? 'auth';
$lines = (int)($_GET['lines'] ?? 200);
$search = $_GET['search'] ?? '';
$format = $_GET['format'] ?? ($_COOKIE['logs_format'] ?? 'structured'); // Use cookie as default

// Get all available log files
$availableLogs = EnderBitLogger::getLogFiles();

// Determine log file path
$logFile = __DIR__ . '/' . $logType . '.log';
if (!isset($availableLogs[$logType])) {
    $logType = 'auth';
    $logFile = __DIR__ . '/auth.log';
}

// Create log file if it doesn't exist
if (!file_exists($logFile)) {
    @touch($logFile);
    @chmod($logFile, 0666);
}

// Function to read last N lines of a file (for raw logs)
function tail($filename, $lines = 200) {
    if (!file_exists($filename)) {
        return "Log file does not exist. It will be created when events are logged.";
    }
    
    if (!is_readable($filename)) {
        return "Log file is not readable. Check file permissions.";
    }
    
    if (filesize($filename) === 0) {
        return "Log file is empty. No events have been logged yet.";
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

// Get log content based on format
if ($format === 'structured') {
    $logEntries = EnderBitLogger::parseLogFile($logFile, $lines, $search);
    $logContent = null;
} else {
    $logContent = tail($logFile, $lines);
    $logEntries = null;
    
    // Filter by search term for raw logs
    if (!empty($search)) {
        $logLines = explode("\n", $logContent);
        $filteredLines = array_filter($logLines, function($line) use ($search) {
            return stripos($line, $search) !== false;
        });
        $logContent = implode("\n", $filteredLines);
    }
}

// Handle clear log file action
if (isset($_POST['clear_log']) && $_POST['clear_log'] === $logType) {
    if (file_exists($logFile)) {
        file_put_contents($logFile, '');
        EnderBitLogger::logSystem('LOG_FILE_CLEARED', ['log_type' => $logType, 'cleared_by' => 'admin']);
        header("Location: logs.php?type=$logType&format=$format&lines=$lines&msg=" . urlencode("Log file cleared successfully") . "&msgtype=success");
        exit;
    }
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

  .log-entry {
    border-bottom:1px solid var(--input-border);
    padding:12px 0;
    margin-bottom:8px;
  }

  .log-entry:last-child {
    border-bottom:none;
    margin-bottom:0;
  }

  .log-header {
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:8px;
    font-size:12px;
  }

  .log-timestamp {
    color:var(--muted);
    font-family:monospace;
  }

  .log-type {
    padding:2px 8px;
    border-radius:4px;
    font-size:11px;
    font-weight:600;
    text-transform:uppercase;
  }

  .log-type.AUTH { background:var(--primary); color:#fff; }
  .log-type.REGISTRATION { background:var(--green); color:#fff; }
  .log-type.TICKET { background:var(--yellow); color:#000; }
  .log-type.EMAIL { background:var(--accent); color:#fff; }
  .log-type.PTERODACTYL { background:#ff6b35; color:#fff; }
  .log-type.ADMIN { background:var(--red); color:#fff; }
  .log-type.SECURITY { background:#dc143c; color:#fff; }
  .log-type.SYSTEM { background:var(--muted); color:#fff; }
  .log-type.PERFORMANCE { background:#9333ea; color:#fff; }
  .log-type.UPLOAD { background:#8a2be2; color:#fff; }

  .log-event {
    font-weight:600;
    color:var(--text);
    margin-bottom:4px;
  }

  .log-details {
    font-size:12px;
    color:var(--muted);
    margin-left:12px;
  }

  .log-details code {
    background:var(--input-bg);
    padding:2px 4px;
    border-radius:3px;
    font-family:monospace;
  }

  .stats-grid {
    display:grid;
    grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));
    gap:16px;
    margin-bottom:24px;
  }

  .stat-card {
    background:var(--card);
    border:1px solid var(--input-border);
    border-radius:12px;
    padding:16px;
    text-align:center;
  }

  .stat-value {
    font-size:24px;
    font-weight:700;
    color:var(--accent) !important;
  }

  .stat-card.clickable-stat .stat-value {
    color:var(--accent) !important;
  }

  .stat-label {
    font-size:12px;
    color:var(--muted);
    margin-bottom:4px;
  }

  .format-toggle {
    margin-left:12px;
  }

  .clickable-stat {
    cursor: pointer;
    transition: all 0.2s ease;
  }

  .clickable-stat:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
  }

  .clickable-stat.active {
    border: 2px solid var(--accent);
    box-shadow: 0 0 12px rgba(88, 166, 255, 0.3);
  }

  .clear-btn {
    background: var(--red);
    color: #fff;
    border: none;
    padding: 10px 16px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    margin-top: 8px;
  }

  .clear-btn:hover {
    opacity: 0.9;
    transform: translateY(-1px);
  }

  .banner {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    padding: 12px 20px;
    color: #fff;
    z-index: 1000;
    display: flex;
    justify-content: space-between;
    align-items: center;
    transform: translateY(-100%);
    transition: transform 0.3s ease;
  }

  .banner.show {
    transform: translateY(0);
  }

  .banner.success {
    background: var(--green);
  }

  .banner.error {
    background: var(--red);
  }

  .banner .close {
    cursor: pointer;
    font-weight: 700;
    opacity: 0.8;
  }

  .banner .close:hover {
    opacity: 1;
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
    <?php if (isset($_GET['msg'])): ?>
      <div id="banner" class="banner <?= htmlspecialchars($_GET['msgtype'] ?? 'success') ?> show">
        <span><?= htmlspecialchars($_GET['msg']) ?></span>
        <span class="close" onclick="hideBanner()">×</span>
      </div>
    <?php endif; ?>

    <div class="header">
      <h1>📋 System Logs</h1>
      <a href="/admin.php" class="btn btn-secondary">← Back to Admin</a>
    </div>

    <!-- Log Statistics -->
    <div class="stats-grid">
      <?php foreach ($availableLogs as $type => $info): ?>
        <div class="stat-card clickable-stat <?= $logType === $type ? 'active' : '' ?>" 
             onclick="switchLogType('<?= $type ?>')">
          <div class="stat-label"><?= htmlspecialchars($info['name']) ?></div>
          <div class="stat-value">
            <?= $info['exists'] ? number_format($info['lines']) : '0' ?>
          </div>
          <div style="font-size:11px;color:var(--muted);">
            <?= $info['exists'] ? number_format($info['size'] / 1024, 1) . ' KB' : 'No data' ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="controls">
      <form method="get" action="logs.php" id="logForm">
        <div class="control-row">
          <label for="type">Log Type:</label>
          <select name="type" id="type" onchange="loadLogs()">
            <?php foreach ($availableLogs as $type_key => $info): ?>
              <option value="<?= $type_key ?>" <?= $logType === $type_key ? 'selected' : '' ?>>
                <?= htmlspecialchars($info['name']) ?> 
                <?= $info['exists'] ? '(' . number_format($info['lines']) . ')' : '(empty)' ?>
              </option>
            <?php endforeach; ?>
          </select>

          <label for="format">Format:</label>
          <select name="format" id="format" class="format-toggle" onchange="loadLogs()">
            <option value="structured" <?= $format === 'structured' ? 'selected' : '' ?>>Structured</option>
            <option value="raw" <?= $format === 'raw' ? 'selected' : '' ?>>Raw</option>
          </select>

          <label for="lines">Lines:</label>
          <input type="number" name="lines" id="lines" value="<?= $lines ?>" min="10" max="10000" style="width:100px;" onchange="loadLogs()">

          <label for="search">Search:</label>
          <input type="text" name="search" id="search" value="<?= htmlspecialchars($search) ?>" placeholder="Filter logs..." style="flex:1;min-width:200px;" oninput="debounceSearch()">

          <button type="button" onclick="window.location.href='logs.php?type=<?= $logType ?>&format=<?= $format ?>&lines=<?= $lines ?>'" class="btn btn-secondary">🔄 Refresh</button>
        </div>
      </form>
      
      <?php if (file_exists($logFile) && filesize($logFile) > 0): ?>
        <div style="margin-top: 12px;">
          <form method="post" style="display:inline;" onsubmit="return confirm('Are you sure you want to clear this log file? This action cannot be undone.')">
            <input type="hidden" name="clear_log" value="<?= $logType ?>">
            <button type="submit" class="clear-btn">🗑️ Clear Log</button>
          </form>
        </div>
      <?php endif; ?>
    </div>

    <div class="log-container">
      <div class="log-info">
        <span><strong>Log File:</strong> <?= htmlspecialchars($logFile) ?></span>
        <span><strong>Lines:</strong> <?= $lines ?> | <strong>Format:</strong> <?= ucfirst($format) ?></span>
      </div>
      
      <?php if ($format === 'structured' && $logEntries !== null): ?>
        <?php if (empty($logEntries)): ?>
          <div style="color:var(--muted);text-align:center;padding:40px;">
            No log entries found or log file is empty.
          </div>
        <?php else: ?>
          <div style="max-height:calc(100vh - 400px);overflow-y:auto;">
            <?php foreach ($logEntries as $entry): ?>
              <div class="log-entry">
                <div class="log-header">
                  <span class="log-timestamp"><?= htmlspecialchars($entry['timestamp']) ?></span>
                  <span class="log-type <?= htmlspecialchars($entry['type']) ?>"><?= htmlspecialchars($entry['type']) ?></span>
                </div>
                <div class="log-event"><?= htmlspecialchars($entry['event']) ?></div>
                <?php if (!empty($entry['details']) || isset($entry['memory_usage']) || isset($entry['execution_time'])): ?>
                  <div class="log-details">
                    <?php if ($entry['type'] === 'PERFORMANCE'): ?>
                      <?php if (isset($entry['memory_usage'])): ?>
                        <div><strong>Memory Usage:</strong> <code><?= number_format($entry['memory_usage'] / 1024 / 1024, 2) ?> MB</code></div>
                      <?php endif; ?>
                      <?php if (isset($entry['memory_peak'])): ?>
                        <div><strong>Peak Memory:</strong> <code><?= number_format($entry['memory_peak'] / 1024 / 1024, 2) ?> MB</code></div>
                      <?php endif; ?>
                      <?php if (isset($entry['execution_time'])): ?>
                        <div><strong>Execution Time:</strong> <code><?= number_format($entry['execution_time'] * 1000, 2) ?> ms</code></div>
                      <?php endif; ?>
                    <?php endif; ?>
                    
                    <?php if (!empty($entry['details'])): ?>
                      <?php foreach ($entry['details'] as $key => $value): ?>
                        <?php if (!empty($value)): ?>
                          <div><strong><?= htmlspecialchars($key) ?>:</strong> <code><?= htmlspecialchars(is_array($value) ? json_encode($value) : $value) ?></code></div>
                        <?php endif; ?>
                      <?php endforeach; ?>
                    <?php endif; ?>
                    
                    <?php if (isset($entry['ip'])): ?>
                      <div><strong>IP:</strong> <code><?= htmlspecialchars($entry['ip']) ?></code></div>
                    <?php endif; ?>
                    
                    <?php if (isset($entry['email'])): ?>
                      <div><strong>Email:</strong> <code><?= htmlspecialchars($entry['email']) ?></code></div>
                    <?php endif; ?>
                    
                    <?php if (isset($entry['ticket_id'])): ?>
                      <div><strong>Ticket:</strong> <code><?= htmlspecialchars($entry['ticket_id']) ?></code></div>
                    <?php endif; ?>
                    
                    <?php if (isset($entry['user_agent'])): ?>
                      <div><strong>User Agent:</strong> <code><?= htmlspecialchars(substr($entry['user_agent'], 0, 100)) ?><?= strlen($entry['user_agent']) > 100 ? '...' : '' ?></code></div>
                    <?php endif; ?>
                  </div>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      <?php else: ?>
        <div class="log-content"><?= htmlspecialchars($logContent) ?: '<span style="color:var(--muted);">No logs found or log file is empty.</span>' ?></div>
      <?php endif; ?>
    </div>
  </div>

<script>
// Theme toggle
const saved = localStorage.getItem("theme");
if(saved){
  document.documentElement.setAttribute("data-theme", saved);
}

// Auto-load logs functionality
function loadLogs() {
  const form = document.getElementById('logForm');
  if (form) {
    // Save format preference to cookie
    const formatSelect = document.getElementById('format');
    if (formatSelect) {
      document.cookie = `logs_format=${formatSelect.value}; path=/; max-age=${30 * 24 * 60 * 60}`; // 30 days
    }
    form.submit();
  }
}

// Switch log type when clicking statistics
function switchLogType(logType) {
  const typeSelect = document.getElementById('type');
  if (typeSelect) {
    typeSelect.value = logType;
    loadLogs();
  }
}

// Debounced search to avoid too many requests
let searchTimeout;
function debounceSearch() {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    loadLogs();
  }, 500);
}

// Banner management
function hideBanner() {
  const banner = document.getElementById('banner');
  if (banner) {
    banner.classList.remove('show');
    // Remove from URL after hiding
    setTimeout(() => {
      const url = new URL(window.location);
      url.searchParams.delete('msg');
      url.searchParams.delete('msgtype');
      window.history.replaceState({}, '', url);
    }, 300);
  }
}

// Auto-hide banner after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
  const banner = document.getElementById('banner');
  if (banner) {
    setTimeout(() => {
      hideBanner();
    }, 5000);
  }
  
  // Initialize format preference from cookie if not set by URL
  const formatSelect = document.getElementById('format');
  if (formatSelect && !new URLSearchParams(window.location.search).has('format')) {
    const savedFormat = getCookie('logs_format');
    if (savedFormat && ['structured', 'raw'].includes(savedFormat)) {
      formatSelect.value = savedFormat;
    }
  }
});

// Helper function to get cookie value
function getCookie(name) {
  const value = `; ${document.cookie}`;
  const parts = value.split(`; ${name}=`);
  if (parts.length === 2) return parts.pop().split(';').shift();
  return null;
}
</script>
</body>
</html>
