<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/logger.php';

// Check admin authentication
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin.php");
    exit;
}

$backupDir = __DIR__ . '/backups';
$msg = $_GET['msg'] ?? '';
$msgType = $_GET['msgtype'] ?? 'success';

// Handle backup restore
if (isset($_POST['restore_backup'])) {
    $backupFile = $_POST['backup_file'];
    $backupPath = $backupDir . '/' . $backupFile;
    
    if (file_exists($backupPath) && pathinfo($backupFile, PATHINFO_EXTENSION) === 'json') {
        // Determine original file name
        $originalFile = '';
        if (strpos($backupFile, 'tokens_') === 0) {
            $originalFile = 'tokens.json';
        } elseif (strpos($backupFile, 'tickets_') === 0) {
            $originalFile = 'tickets.json';
        } elseif (strpos($backupFile, 'settings_') === 0) {
            $originalFile = 'settings.json';
        }
        
        if ($originalFile) {
            $originalPath = __DIR__ . '/' . $originalFile;
            if (copy($backupPath, $originalPath)) {
                EnderBitLogger::logAdmin('BACKUP_RESTORED', 'RESTORE_BACKUP', ['backup_file' => $backupFile, 'original_file' => $originalFile]);
                header("Location: backup.php?msg=" . urlencode("Backup restored successfully: $originalFile") . "&msgtype=success");
            } else {
                header("Location: backup.php?msg=" . urlencode("Failed to restore backup") . "&msgtype=error");
            }
        } else {
            header("Location: backup.php?msg=" . urlencode("Invalid backup file format") . "&msgtype=error");
        }
    } else {
        header("Location: backup.php?msg=" . urlencode("Backup file not found") . "&msgtype=error");
    }
    exit;
}

// Handle backup deletion
if (isset($_POST['delete_backup'])) {
    $backupFile = $_POST['backup_file'];
    $backupPath = $backupDir . '/' . $backupFile;
    
    if (file_exists($backupPath)) {
        if (unlink($backupPath)) {
            EnderBitLogger::logAdmin('BACKUP_DELETED', 'DELETE_BACKUP', ['backup_file' => $backupFile]);
            header("Location: backup.php?msg=" . urlencode("Backup deleted successfully") . "&msgtype=success");
        } else {
            header("Location: backup.php?msg=" . urlencode("Failed to delete backup") . "&msgtype=error");
        }
    } else {
        header("Location: backup.php?msg=" . urlencode("Backup file not found") . "&msgtype=error");
    }
    exit;
}

// Get all backup files
$backups = [];
if (is_dir($backupDir)) {
    $files = scandir($backupDir);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..' && pathinfo($file, PATHINFO_EXTENSION) === 'json') {
            $filePath = $backupDir . '/' . $file;
            $backups[] = [
                'name' => $file,
                'size' => filesize($filePath),
                'modified' => filemtime($filePath),
                'type' => strpos($file, 'tokens_') === 0 ? 'User Tokens' : 
                          (strpos($file, 'tickets_') === 0 ? 'Support Tickets' : 
                          (strpos($file, 'settings_') === 0 ? 'Admin Settings' : 'Unknown'))
            ];
        }
    }
    
    // Sort by modification time (newest first)
    usort($backups, function($a, $b) {
        return $b['modified'] - $a['modified'];
    });
}
?>
<!doctype html>
<html lang="en" data-theme="dark">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Backup Management - EnderBit Admin</title>
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
    max-width:1200px;
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

  .btn {
    padding:10px 20px;
    border-radius:8px;
    border:none;
    font-weight:600;
    font-size:14px;
    cursor:pointer;
    text-decoration:none;
    display:inline-block;
    transition:all .2s;
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

  .btn-danger {
    background:var(--red);
    color:#fff;
  }

  .btn-success {
    background:var(--green);
    color:#fff;
  }

  .btn:hover {
    opacity:.9;
    transform:translateY(-1px);
  }

  .backup-table {
    background:var(--card);
    border:1px solid var(--input-border);
    border-radius:12px;
    overflow:hidden;
  }

  table {
    width:100%;
    border-collapse:collapse;
  }

  th, td {
    padding:16px;
    text-align:left;
    border-bottom:1px solid var(--input-border);
  }

  th {
    background:var(--input-bg);
    font-weight:600;
    color:var(--accent);
  }

  tr:last-child td {
    border-bottom:none;
  }

  tr:hover {
    background:var(--input-bg);
  }

  .file-type {
    padding:4px 8px;
    border-radius:4px;
    font-size:12px;
    font-weight:600;
  }

  .type-tokens { background:var(--primary); color:#fff; }
  .type-tickets { background:var(--yellow); color:#000; }
  .type-settings { background:var(--green); color:#fff; }

  .banner {
    position:fixed;
    top:0;
    left:0;
    right:0;
    padding:12px 20px;
    color:#fff;
    z-index:1000;
    display:flex;
    justify-content:space-between;
    align-items:center;
    transform:translateY(-100%);
    transition:transform 0.3s ease;
  }

  .banner.show {
    transform:translateY(0);
  }

  .banner.success {
    background:var(--green);
  }

  .banner.error {
    background:var(--red);
  }

  .banner .close {
    cursor:pointer;
    font-weight:700;
    opacity:0.8;
  }

  .banner .close:hover {
    opacity:1;
  }

  .empty-state {
    text-align:center;
    padding:60px 20px;
    color:var(--muted);
  }

  .empty-state h3 {
    margin-bottom:12px;
    color:var(--text);
  }

  .action-buttons {
    display:flex;
    gap:8px;
  }

  .action-buttons .btn {
    padding:6px 12px;
    font-size:12px;
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
    <div class="header">
      <h1>💾 Backup Management</h1>
      <div>
        <a href="/logs.php" class="btn btn-secondary">← Back to Logs</a>
        <a href="/admin.php" class="btn btn-secondary">Admin Panel</a>
      </div>
    </div>

    <?php if (empty($backups)): ?>
      <div class="backup-table">
        <div class="empty-state">
          <h3>No Backups Found</h3>
          <p>No backup files have been created yet. Create your first backup from the Logs page.</p>
        </div>
      </div>
    <?php else: ?>
      <div class="backup-table">
        <table>
          <thead>
            <tr>
              <th>File Name</th>
              <th>Type</th>
              <th>Size</th>
              <th>Created</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($backups as $backup): ?>
              <tr>
                <td><code><?= htmlspecialchars($backup['name']) ?></code></td>
                <td>
                  <span class="file-type <?= 
                    $backup['type'] === 'User Tokens' ? 'type-tokens' : 
                    ($backup['type'] === 'Support Tickets' ? 'type-tickets' : 'type-settings') 
                  ?>">
                    <?= htmlspecialchars($backup['type']) ?>
                  </span>
                </td>
                <td><?= number_format($backup['size'] / 1024, 1) ?> KB</td>
                <td><?= date('M j, Y, g:i A', $backup['modified']) ?></td>
                <td>
                  <div class="action-buttons">
                    <form method="post" style="display:inline;" onsubmit="return confirm('Restore this backup? This will overwrite the current file.')">
                      <input type="hidden" name="backup_file" value="<?= htmlspecialchars($backup['name']) ?>">
                      <button type="submit" name="restore_backup" class="btn btn-success">↻ Restore</button>
                    </form>
                    <form method="post" style="display:inline;" onsubmit="return confirm('Delete this backup? This action cannot be undone.')">
                      <input type="hidden" name="backup_file" value="<?= htmlspecialchars($backup['name']) ?>">
                      <button type="submit" name="delete_backup" class="btn btn-danger">🗑️ Delete</button>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

<script>
// Theme toggle
const saved = localStorage.getItem("theme");
if(saved){
  document.documentElement.setAttribute("data-theme", saved);
}

// Banner management
function hideBanner() {
  const banner = document.getElementById('banner');
  if (banner) {
    banner.classList.remove('show');
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
});
</script>
</body>
</html>