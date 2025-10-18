<?php
ob_start(); // Start output buffering to allow cookies to be set

require_once __DIR__ . '/admin_session.php';
require_once __DIR__ . '/background_tasks.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/logger.php';
require_once __DIR__ . '/timezone_utils.php';
require_once __DIR__ . '/security.php';

// Set security headers
EnderBitSecurity::setSecurityHeaders();

// Initialize and validate admin session
EnderBitAdminSession::init();
if (!EnderBitAdminSession::isLoggedIn()) {
    header("Location: admin.php");
    exit;
}

// Run scheduled tasks
EnderBitBackgroundTasks::runScheduledTasks();

$backupDir = __DIR__ . '/backups';
$metadataFile = $backupDir . '/metadata.json';
$scheduleFile = $backupDir . '/schedule.json';
$msg = $_GET['msg'] ?? '';
$msgType = $_GET['msgtype'] ?? 'success';

// Load metadata
function loadMetadata() {
    global $metadataFile;
    if (file_exists($metadataFile)) {
        $data = json_decode(file_get_contents($metadataFile), true);
        return is_array($data) ? $data : [];
    }
    return [];
}

// Save metadata
function saveMetadata($metadata) {
    global $metadataFile, $backupDir;
    if (!is_dir($backupDir)) {
        mkdir($backupDir, 0755, true);
    }
    file_put_contents($metadataFile, json_encode($metadata, JSON_PRETTY_PRINT));
}

// Load schedule settings
function loadSchedule() {
    global $scheduleFile;
    if (file_exists($scheduleFile)) {
        $data = json_decode(file_get_contents($scheduleFile), true);
        return is_array($data) ? $data : ['enabled' => false, 'frequency' => 'daily', 'last_run' => null];
    }
    return ['enabled' => false, 'frequency' => 'daily', 'last_run' => null];
}

// Save schedule settings
function saveSchedule($schedule) {
    global $scheduleFile, $backupDir;
    if (!is_dir($backupDir)) {
        mkdir($backupDir, 0755, true);
    }
    file_put_contents($scheduleFile, json_encode($schedule, JSON_PRETTY_PRINT));
}

// Check if scheduled backup should run
function checkScheduledBackup() {
    $schedule = loadSchedule();
    if (!$schedule['enabled']) return false;
    
    $lastRun = $schedule['last_run'] ?? null;
    if (!$lastRun) return true;
    
    $now = time();
    $lastRunTime = strtotime($lastRun);
    
    switch ($schedule['frequency']) {
        case 'hourly':
            return ($now - $lastRunTime) >= 3600; // 1 hour
        case 'daily':
            return ($now - $lastRunTime) >= 86400; // 24 hours
        case 'weekly':
            return ($now - $lastRunTime) >= 604800; // 7 days
        default:
            return false;
    }
}

// Perform backup
function performBackup($description = '', $fullBackup = false) {
    global $backupDir;
    
    if (!is_dir($backupDir)) {
        mkdir($backupDir, 0755, true);
    }
    
    $timestamp = date('Y-m-d_H-i-s');
    $backupFiles = [];
    $skippedFiles = [];
    $metadata = loadMetadata();
    
    if ($fullBackup) {
        // Full system backup - create a ZIP file
        $zipFileName = 'full_backup_' . $timestamp . '.zip';
        $zipPath = $backupDir . '/' . $zipFileName;
        $zip = new ZipArchive();
        
        if ($zip->open($zipPath, ZipArchive::CREATE) === TRUE) {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(__DIR__),
                RecursiveIteratorIterator::LEAVES_ONLY
            );
            
            $fileCount = 0;
            foreach ($files as $file) {
                if (!$file->isDir()) {
                    $filePath = $file->getRealPath();
                    $relativePath = substr($filePath, strlen(__DIR__) + 1);
                    
                    // Skip backups directory and git files
                    if (strpos($relativePath, 'backups/') !== 0 && 
                        strpos($relativePath, '.git') !== 0 &&
                        strpos($relativePath, 'uploads/') !== 0) {
                        $zip->addFile($filePath, $relativePath);
                        $fileCount++;
                    }
                }
            }
            $zip->close();
            
            $metadata['sets'][$timestamp] = [
                'description' => $description,
                'created' => time(),
                'files' => [$zipFileName],
                'type' => 'full',
                'file_count' => $fileCount
            ];
            
            saveMetadata($metadata);
            return ['success' => true, 'files' => [$zipFileName], 'timestamp' => $timestamp, 'type' => 'full', 'file_count' => $fileCount];
        } else {
            return ['success' => false, 'files' => [], 'timestamp' => $timestamp, 'error' => 'Failed to create ZIP file'];
        }
    } else {
        // Regular JSON backup
        $jsonFiles = ['tokens.json', 'tickets.json', 'settings.json'];
        
        foreach ($jsonFiles as $file) {
            $filePath = __DIR__ . '/' . $file;
            if (file_exists($filePath)) {
                $fileType = pathinfo($file, PATHINFO_FILENAME);
                $backupFileName = $fileType . '_' . $timestamp . '.json';
                $backupPath = $backupDir . '/' . $backupFileName;
                if (copy($filePath, $backupPath)) {
                    $backupFiles[] = $backupFileName;
                    
                    // Store metadata for individual file
                    $metadata[$backupFileName] = [
                        'timestamp' => $timestamp,
                        'created' => time(),
                        'type' => $fileType
                    ];
                }
            } else {
                $skippedFiles[] = $file;
            }
        }
        
        // Store backup set metadata
        if (!empty($backupFiles)) {
            $metadata['sets'][$timestamp] = [
                'description' => $description,
                'created' => time(),
                'files' => $backupFiles,
                'type' => 'json',
                'skipped' => $skippedFiles
            ];
        }
        
        saveMetadata($metadata);
        
        return ['success' => !empty($backupFiles), 'files' => $backupFiles, 'timestamp' => $timestamp, 'skipped' => $skippedFiles, 'type' => 'json'];
    }
}

// Handle backup creation
if (isset($_POST['create_backup'])) {
    $description = trim($_POST['description'] ?? '');
    $fullBackup = isset($_POST['full_backup']);
    $result = performBackup($description, $fullBackup);
    
    if ($result['success']) {
        $logData = [
            'files' => $result['files'], 
            'timestamp' => $result['timestamp'],
            'description' => $description,
            'type' => $result['type']
        ];
        
        if (!empty($result['skipped'])) {
            $logData['skipped'] = $result['skipped'];
        }
        
        if ($fullBackup) {
            $logData['file_count'] = $result['file_count'];
        }
        
        EnderBitLogger::logAdmin($fullBackup ? 'FULL_BACKUP_CREATED' : 'JSON_BACKUP_CREATED', 'BACKUP_FILES', $logData);
        
        $msg = $fullBackup 
            ? "Full system backup created successfully ({$result['file_count']} files)"
            : "Backup created successfully" . (!empty($result['skipped']) ? " (Note: " . implode(', ', $result['skipped']) . " not found)" : "");
        
        header("Location: backup.php?msg=" . urlencode($msg) . "&msgtype=success");
    } else {
        $errorMsg = $fullBackup && isset($result['error']) 
            ? $result['error']
            : "No JSON files found to backup";
        header("Location: backup.php?msg=" . urlencode($errorMsg) . "&msgtype=error");
    }
    exit;
}

// Handle description update
if (isset($_POST['update_description'])) {
    $backupTimestamp = $_POST['backup_timestamp'];
    $newDescription = trim($_POST['new_description'] ?? '');
    $metadata = loadMetadata();
    
    if (isset($metadata['sets'][$backupTimestamp])) {
        $metadata['sets'][$backupTimestamp]['description'] = $newDescription;
        saveMetadata($metadata);
        EnderBitLogger::logAdmin('BACKUP_DESCRIPTION_UPDATED', 'UPDATE_BACKUP_DESCRIPTION', [
            'timestamp' => $backupTimestamp,
            'description' => $newDescription
        ]);
        header("Location: backup.php?msg=" . urlencode("Description updated successfully") . "&msgtype=success");
    } else {
        header("Location: backup.php?msg=" . urlencode("Backup set not found") . "&msgtype=error");
    }
    exit;
}

// Handle backup set restoration
if (isset($_POST['restore_backup_set'])) {
    $backupTimestamp = $_POST['backup_timestamp'];
    $metadata = loadMetadata();
    
    if (isset($metadata['sets'][$backupTimestamp])) {
        $backupType = $metadata['sets'][$backupTimestamp]['type'] ?? 'json';
        $files = $metadata['sets'][$backupTimestamp]['files'];
        
        if ($backupType === 'full' && !empty($files)) {
            // Full backup restore - ZIP file
            $zipFile = $files[0];
            $zipPath = $backupDir . '/' . $zipFile;
            
            if (file_exists($zipPath)) {
                header("Location: backup.php?msg=" . urlencode("Full backup restore not yet implemented - please download and restore manually") . "&msgtype=error");
            } else {
                header("Location: backup.php?msg=" . urlencode("Backup file not found") . "&msgtype=error");
            }
        } else {
            // JSON backup restore
            $restored = 0;
            
            foreach ($files as $backupFile) {
                $backupPath = $backupDir . '/' . $backupFile;
                
                if (file_exists($backupPath)) {
                    $originalFile = '';
                    if (strpos($backupFile, 'tokens_') === 0) {
                        $originalFile = 'tokens.json';
                    } elseif (strpos($backupFile, 'tickets_') === 0) {
                        $originalFile = 'tickets.json';
                    } elseif (strpos($backupFile, 'settings_') === 0) {
                        $originalFile = 'settings.json';
                    }
                    
                    if ($originalFile && copy($backupPath, __DIR__ . '/' . $originalFile)) {
                        $restored++;
                    }
                }
            }
            
            if ($restored > 0) {
                EnderBitLogger::logAdmin('BACKUP_SET_RESTORED', 'RESTORE_BACKUP', ['timestamp' => $backupTimestamp, 'files_restored' => $restored]);
                header("Location: backup.php?msg=" . urlencode("Backup set restored successfully ($restored files)") . "&msgtype=success");
            } else {
                header("Location: backup.php?msg=" . urlencode("Failed to restore backup set") . "&msgtype=error");
            }
        }
    } else {
        header("Location: backup.php?msg=" . urlencode("Backup set not found") . "&msgtype=error");
    }
    exit;
}

// Handle backup set deletion
if (isset($_POST['delete_backup_set'])) {
    $backupTimestamp = $_POST['backup_timestamp'];
    $metadata = loadMetadata();
    
    if (isset($metadata['sets'][$backupTimestamp])) {
        $files = $metadata['sets'][$backupTimestamp]['files'];
        $deleted = 0;
        
        foreach ($files as $backupFile) {
            $backupPath = $backupDir . '/' . $backupFile;
            if (file_exists($backupPath) && unlink($backupPath)) {
                $deleted++;
                // Remove individual file metadata
                if (isset($metadata[$backupFile])) {
                    unset($metadata[$backupFile]);
                }
            }
        }
        
        // Remove set metadata
        unset($metadata['sets'][$backupTimestamp]);
        saveMetadata($metadata);
        
        EnderBitLogger::logAdmin('BACKUP_SET_DELETED', 'DELETE_BACKUP', ['timestamp' => $backupTimestamp, 'files_deleted' => $deleted]);
        header("Location: backup.php?msg=" . urlencode("Backup set deleted successfully") . "&msgtype=success");
    } else {
        header("Location: backup.php?msg=" . urlencode("Backup set not found") . "&msgtype=error");
    }
    exit;
}

// Handle schedule update
if (isset($_POST['update_schedule'])) {
    $schedule = [
        'enabled' => isset($_POST['schedule_enabled']),
        'frequency' => $_POST['schedule_frequency'] ?? 'daily',
        'last_run' => loadSchedule()['last_run'] ?? null
    ];
    
    saveSchedule($schedule);
    EnderBitLogger::logAdmin('BACKUP_SCHEDULE_UPDATED', 'UPDATE_SCHEDULE', $schedule);
    header("Location: backup.php?msg=" . urlencode("Backup schedule updated. Backups will run automatically on any page visit when the time interval is reached.") . "&msgtype=success");
    exit;
}

// Get backup sets
$backupSets = [];
$metadata = loadMetadata();
$schedule = loadSchedule();

if (isset($metadata['sets']) && is_array($metadata['sets'])) {
    foreach ($metadata['sets'] as $timestamp => $setData) {
        $backupSets[] = [
            'timestamp' => $timestamp,
            'description' => $setData['description'] ?? '',
            'created' => $setData['created'] ?? 0,
            'files' => $setData['files'] ?? [],
            'file_count' => count($setData['files'] ?? [])
        ];
    }
    
    // Sort by created time (newest first)
    usort($backupSets, function($a, $b) {
        return $b['created'] - $a['created'];
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
    font-weight:600;
    font-size:14px;
    border:none;
    cursor:pointer;
    transition:all .2s;
    text-decoration:none;
    display:inline-block;
  }

  .btn-primary { background:var(--primary); color:#fff; }
  .btn-secondary { background:var(--input-bg); color:var(--text); border:1px solid var(--input-border); }
  .btn-success { background:var(--green); color:#fff; }
  .btn-danger { background:var(--red); color:#fff; }
  
  .btn:hover { opacity:.9; transform:translateY(-1px); }
  .btn-small { padding:6px 12px; font-size:12px; }

  .card {
    background:var(--card);
    border:1px solid var(--input-border);
    border-radius:12px;
    padding:24px;
    margin-bottom:24px;
    box-shadow:0 4px 12px rgba(0,0,0,.3);
  }

  .card h2 {
    color:var(--accent);
    margin-bottom:16px;
    font-size:20px;
  }

  .form-group {
    margin-bottom:16px;
  }

  .form-group label {
    display:block;
    margin-bottom:8px;
    font-weight:600;
    color:var(--text);
  }

  .form-group input,
  .form-group textarea,
  .form-group select {
    width:100%;
    padding:12px;
    border:1px solid var(--input-border);
    border-radius:8px;
    background:var(--input-bg);
    color:var(--text);
    font-family:inherit;
    font-size:14px;
  }

  .form-group textarea {
    resize:vertical;
    min-height:80px;
  }

  .form-group input:focus,
  .form-group textarea:focus,
  .form-group select:focus {
    outline:none;
    border-color:var(--accent);
  }

  .form-group input[type="checkbox"] {
    width:auto;
    margin-right:8px;
  }

  .backup-grid {
    display:grid;
    grid-template-columns:repeat(auto-fill,minmax(350px,1fr));
    gap:20px;
  }

  .backup-set-card {
    background:var(--card);
    border:1px solid var(--input-border);
    border-radius:12px;
    padding:20px;
    transition:all .3s;
  }

  .backup-set-card:hover {
    transform:translateY(-2px);
    box-shadow:0 6px 20px rgba(88,166,255,.15);
    border-color:var(--accent);
  }

  .backup-time {
    font-size:18px;
    font-weight:700;
    color:var(--accent);
    margin-bottom:8px;
  }

  .backup-date {
    font-size:13px;
    color:var(--muted);
    margin-bottom:12px;
  }

  .backup-description {
    background:var(--input-bg);
    padding:12px;
    border-radius:6px;
    margin-bottom:12px;
    min-height:40px;
    font-style:italic;
    color:var(--muted);
    font-size:14px;
  }

  .backup-description:empty:before {
    content:"No description";
    opacity:0.5;
  }

  .backup-files {
    font-size:12px;
    color:var(--muted);
    margin-bottom:12px;
    padding:8px;
    background:var(--input-bg);
    border-radius:6px;
  }

  .backup-actions {
    display:flex;
    gap:8px;
    flex-wrap:wrap;
  }

  .edit-form {
    display:none;
    margin-top:12px;
    padding:12px;
    background:var(--input-bg);
    border-radius:6px;
  }

  .edit-form.active {
    display:block;
  }

  .edit-form input {
    width:100%;
    padding:8px;
    margin-bottom:8px;
    border:1px solid var(--input-border);
    border-radius:4px;
    background:var(--card);
    color:var(--text);
  }

  .empty-state {
    text-align:center;
    padding:60px 20px;
    color:var(--muted);
  }

  .empty-state h3 {
    color:var(--text);
    margin-bottom:8px;
    font-size:20px;
  }

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
  .banner.show { left:20px; }
  .banner.hide { left:-500px !important; }
  .banner.success { background:var(--green); color:#fff; }
  .banner.error { background:var(--red); color:#fff; }
  .banner .close { cursor:pointer; font-weight:700; color:#fff; padding-left:12px; opacity:0.8; }
  .banner .close:hover { opacity:1; }

  @media (max-width: 768px) {
    .backup-grid {
      grid-template-columns:1fr;
    }
  }
</style>
</head>
<body>
  <?php if ($msg): ?>
    <div id="banner" class="banner <?= htmlspecialchars($msgType) ?> hide">
      <span><?= htmlspecialchars($msg) ?></span>
      <span class="close" onclick="hideBanner()">×</span>
    </div>
  <?php endif; ?>

  <div class="page">
    <div class="header">
      <h1>💾 Backup Management</h1>
      <div style="display:flex;align-items:center;gap:12px;">
        <span style="font-size:13px;color:var(--text-secondary);padding:6px 12px;background:var(--input-bg);border-radius:6px;">
          🌍 Your timezone: <?= getTimezoneAbbr() ?> (<?= getTimezoneOffset() ?>)
        </span>
        <a href="/admin.php" class="btn btn-secondary">← Back to Admin Panel</a>
      </div>
    </div>

    <!-- Create Backup Card -->
    <div class="card">
      <h2>📦 Create New Backup</h2>
      <form method="post">
        <div class="form-group">
          <label for="description">Backup Description (Optional)</label>
          <textarea 
            id="description" 
            name="description" 
            placeholder="e.g., Before major update, End of month backup, etc."
          ></textarea>
        </div>
        
        <div class="form-group">
          <label>
            <input type="checkbox" name="full_backup" id="full_backup" onchange="updateBackupInfo()">
            <strong>Full System Backup</strong> (includes all PHP files, images, uploads, etc.)
          </label>
          <p style="font-size:12px; color:var(--muted); margin-top:4px; margin-left:26px;">
            <span id="backup-info">Regular backup includes: tokens.json, tickets.json, and settings.json</span>
          </p>
        </div>
        
        <button type="submit" name="create_backup" class="btn btn-primary">💾 Create Backup</button>
      </form>
    </div>

    <!-- Schedule Backup Card -->
    <div class="card">
      <h2>⏰ Scheduled Backups</h2>
      <form method="post">
        <div class="form-group">
          <label>
            <input type="checkbox" name="schedule_enabled" <?= $schedule['enabled'] ? 'checked' : '' ?>>
            Enable Scheduled Backups
          </label>
          <p style="font-size:13px; color:var(--muted); margin-top:8px;">
            When enabled, backups will run automatically based on the frequency you set below. The system checks the time on every page visit (any visitor, not just admins).
          </p>
        </div>
        <div class="form-group">
          <label for="schedule_frequency">Backup Frequency</label>
          <select id="schedule_frequency" name="schedule_frequency">
            <option value="hourly" <?= ($schedule['frequency'] ?? 'daily') === 'hourly' ? 'selected' : '' ?>>Every Hour</option>
            <option value="daily" <?= ($schedule['frequency'] ?? 'daily') === 'daily' ? 'selected' : '' ?>>Daily</option>
            <option value="weekly" <?= ($schedule['frequency'] ?? 'daily') === 'weekly' ? 'selected' : '' ?>>Weekly</option>
          </select>
          <p style="font-size:13px; color:var(--muted); margin-top:8px;">
            The system compares current time vs. last backup time. When the interval is reached, a new backup runs automatically on the next page visit.
          </p>
        </div>
        <?php if (!empty($schedule['last_run'])): ?>
          <p style="font-size:13px; color:var(--muted); margin-bottom:12px;">
            Last scheduled backup: <?= is_numeric($schedule['last_run']) ? date('Y-m-d H:i:s', (int)$schedule['last_run']) : htmlspecialchars($schedule['last_run']) ?>
          </p>
        <?php endif; ?>
        <button type="submit" name="update_schedule" class="btn btn-primary">💾 Save Schedule Settings</button>
      </form>
      
      <div style="margin-top:24px; padding:16px; background:var(--input-bg); border-radius:8px; border-left:4px solid var(--green);">
        <h3 style="margin-bottom:12px; color:var(--green);">✅ How Automatic Backups Work</h3>
        <ul style="font-size:14px; color:var(--muted); margin-left:20px; line-height:1.8;">
          <li>Backups run automatically when <strong>anyone</strong> visits your site</li>
          <li>System checks: current time - last backup time ≥ frequency interval</li>
          <li>If true, backup runs in the background</li>
          <li>No cron jobs or external services needed</li>
          <li>No admin login required</li>
        </ul>
        <p style="font-size:13px; color:var(--muted); margin-top:12px;">
          � <strong>Example:</strong> If frequency is "Daily" and last backup was 25 hours ago, the next page visit will trigger a backup.
        </p>
      </div>
    </div>

    <!-- Backup Sets -->
    <h2 style="color:var(--accent); margin-bottom:16px;">📚 Backup History</h2>
    <?php if (empty($backupSets)): ?>
      <div class="card">
        <div class="empty-state">
          <h3>No Backups Found</h3>
          <p>No backup files have been created yet. Create your first backup above.</p>
        </div>
      </div>
    <?php else: ?>
      <div class="backup-grid">
        <?php foreach ($backupSets as $set): ?>
          <div class="backup-set-card">
            <div class="backup-time">
              <?= formatTimeInUserTZ($set['created'], 'g:i A') ?>
              <?php if (($set['type'] ?? 'json') === 'full'): ?>
                <span style="background:var(--primary);color:#fff;font-size:10px;padding:2px 6px;border-radius:4px;margin-left:8px;">FULL</span>
              <?php endif; ?>
            </div>
            <div class="backup-date">
              <?= formatTimeInUserTZ($set['created'], 'l, F j, Y') ?>
            </div>
            
            <div class="backup-description" id="desc-<?= md5($set['timestamp']) ?>">
              <?= htmlspecialchars($set['description']) ?>
            </div>
            
            <div class="backup-files">
              <?php if (($set['type'] ?? 'json') === 'full'): ?>
                📦 Full system backup (<?= number_format($set['file_count'] ?? 0) ?> files)
                <br>
                <span style="font-size:11px;">Complete site backup in ZIP format</span>
              <?php else: ?>
                📦 <?= $set['file_count'] ?> files backed up
                <br>
                <span style="font-size:11px;">
                  <?php
                  $fileTypes = [];
                  foreach ($set['files'] as $file) {
                      if (strpos($file, 'tokens_') === 0) $fileTypes[] = 'Users';
                      elseif (strpos($file, 'tickets_') === 0) $fileTypes[] = 'Tickets';
                      elseif (strpos($file, 'settings_') === 0) $fileTypes[] = 'Settings';
                  }
                  echo implode(' • ', array_unique($fileTypes));
                  
                  // Show skipped files warning
                  if (!empty($set['skipped'])) {
                      echo '<br><span style="color:var(--yellow);">⚠️ Missing: ' . implode(', ', $set['skipped']) . '</span>';
                  }
                  ?>
                </span>
              <?php endif; ?>
            </div>
            
            <div class="backup-actions">
              <button onclick="toggleEdit('<?= md5($set['timestamp']) ?>')" class="btn btn-secondary btn-small">
                ✏️ Edit
              </button>
              <form method="post" style="display:inline;" onsubmit="return confirm('Restore this backup set? This will overwrite current files.')">
                <input type="hidden" name="backup_timestamp" value="<?= htmlspecialchars($set['timestamp']) ?>">
                <button type="submit" name="restore_backup_set" class="btn btn-success btn-small">
                  ↻ Restore
                </button>
              </form>
              <form method="post" style="display:inline;" onsubmit="return confirm('Delete this backup set? This cannot be undone.')">
                <input type="hidden" name="backup_timestamp" value="<?= htmlspecialchars($set['timestamp']) ?>">
                <button type="submit" name="delete_backup_set" class="btn btn-danger btn-small">
                  🗑️ Delete
                </button>
              </form>
            </div>
            
            <form method="post" class="edit-form" id="edit-<?= md5($set['timestamp']) ?>">
              <input type="hidden" name="backup_timestamp" value="<?= htmlspecialchars($set['timestamp']) ?>">
              <input type="text" name="new_description" value="<?= htmlspecialchars($set['description']) ?>" placeholder="Enter description">
              <div style="display:flex; gap:8px;">
                <button type="submit" name="update_description" class="btn btn-primary btn-small">💾 Save</button>
                <button type="button" onclick="toggleEdit('<?= md5($set['timestamp']) ?>')" class="btn btn-secondary btn-small">✖ Cancel</button>
              </div>
            </form>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

<script>
// Update backup info text
function updateBackupInfo() {
  const checkbox = document.getElementById('full_backup');
  const info = document.getElementById('backup-info');
  
  if (checkbox.checked) {
    info.innerHTML = '<strong>Full backup will include:</strong> All PHP files, HTML, CSS, JavaScript, images, uploads, and configuration files (excluding backups directory)';
    info.style.color = 'var(--accent)';
  } else {
    info.innerHTML = 'Regular backup includes: tokens.json, tickets.json, and settings.json';
    info.style.color = 'var(--muted)';
  }
}

// Banner management
function hideBanner() {
  const banner = document.getElementById('banner');
  if (banner) {
    banner.classList.add('hide');
    banner.classList.remove('show');
  }
}

// Auto-show and hide banner
document.addEventListener('DOMContentLoaded', function() {
  const banner = document.getElementById('banner');
  if (banner) {
    banner.classList.remove('hide');
    setTimeout(() => banner.classList.add('show'), 120);
    setTimeout(() => {
      hideBanner();
    }, 5000);
  }
});

// Toggle edit description form
function toggleEdit(id) {
  const form = document.getElementById('edit-' + id);
  
  if (form.classList.contains('active')) {
    form.classList.remove('active');
  } else {
    // Close all other edit forms
    document.querySelectorAll('.edit-form.active').forEach(f => f.classList.remove('active'));
    form.classList.add('active');
  }
}
</script>
</body>
</html>
