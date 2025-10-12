<?php
/**
 * EnderBit Scheduled Backup Cron Job
 * This file should be run via cron every hour to check for scheduled backups
 * Example crontab: 0 * * * * /usr/bin/php /path/to/httpdocs/cron_backup.php
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/logger.php';

$backupDir = __DIR__ . '/backups';
$scheduleFile = $backupDir . '/schedule.json';
$metadataFile = $backupDir . '/metadata.json';

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
function performBackup($description = '') {
    global $backupDir;
    
    if (!is_dir($backupDir)) {
        mkdir($backupDir, 0755, true);
    }
    
    $timestamp = date('Y-m-d_H-i-s');
    $backupFiles = [];
    $skippedFiles = [];
    $metadata = loadMetadata();
    
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

// Initialize logger
EnderBitLogger::init();

// Check if backup should run
if (checkScheduledBackup()) {
    EnderBitLogger::logSystem('CRON_BACKUP_STARTED', ['timestamp' => date('Y-m-d H:i:s')]);
    
    $result = performBackup('Scheduled backup (cron)');
    
    if ($result['success']) {
        $schedule = loadSchedule();
        $schedule['last_run'] = date('Y-m-d H:i:s');
        saveSchedule($schedule);
        
        EnderBitLogger::logAdmin('SCHEDULED_BACKUP_COMPLETED', 'BACKUP_JSON_FILES', [
            'files' => $result['files'],
            'timestamp' => $result['timestamp'],
            'source' => 'cron'
        ]);
        
        echo "Backup completed successfully at " . date('Y-m-d H:i:s') . "\n";
        echo "Files backed up: " . implode(', ', $result['files']) . "\n";
    } else {
        EnderBitLogger::logSystem('CRON_BACKUP_FAILED', [
            'timestamp' => date('Y-m-d H:i:s'),
            'reason' => 'No files backed up'
        ]);
        echo "Backup failed - no files were backed up\n";
    }
} else {
    echo "No backup needed at this time\n";
}
?>
