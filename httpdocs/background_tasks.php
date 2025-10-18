<?php
/**
 * EnderBit Background Tasks Handler
 * Automatically runs scheduled tasks when admin pages are accessed
 * No cron job required - uses time-based checking
 */

require_once __DIR__ . '/logger.php';

class EnderBitBackgroundTasks {
    private static $backupDir;
    private static $scheduleFile;
    private static $metadataFile;
    
    public static function init() {
        self::$backupDir = __DIR__ . '/backups';
        self::$scheduleFile = self::$backupDir . '/schedule.json';
        self::$metadataFile = self::$backupDir . '/metadata.json';
    }
    
    /**
     * Check and run all scheduled tasks
     * Called on every admin page load
     */
    public static function runScheduledTasks() {
        self::init();
        self::checkScheduledBackup();
        self::checkHourlyBilling();
        // Add more scheduled tasks here in the future
    }
    
    /**
     * Check if hourly billing should run
     */
    private static function checkHourlyBilling() {
        // Check if app billing system exists
        $billingFile = __DIR__ . '/../app/billing.php';
        if (!file_exists($billingFile)) {
            return false;
        }
        
        // Load billing schedule
        $billingScheduleFile = __DIR__ . '/../app/billing_schedule.json';
        $billingSchedule = [];
        
        if (file_exists($billingScheduleFile)) {
            $billingSchedule = json_decode(file_get_contents($billingScheduleFile), true) ?? [];
        }
        
        $lastRun = $billingSchedule['last_run'] ?? 0;
        $now = time();
        
        // Check if at least 1 hour has passed since last billing run
        $hoursSinceLastRun = ($now - $lastRun) / 3600;
        
        if ($hoursSinceLastRun >= 1) {
            try {
                require_once $billingFile;
                $result = EnderBitBilling::processHourlyBilling();
                
                // Update last run time
                $billingSchedule['last_run'] = $now;
                $billingSchedule['last_result'] = $result;
                file_put_contents($billingScheduleFile, json_encode($billingSchedule, JSON_PRETTY_PRINT));
                
                EnderBitLogger::logSystem('BILLING_RUN', 'BACKGROUND_TASK', [
                    'billed' => $result['billed'],
                    'suspended' => $result['suspended']
                ]);
                
                return true;
            } catch (Exception $e) {
                EnderBitLogger::logSystem('BILLING_ERROR', 'BACKGROUND_TASK', [
                    'error' => $e->getMessage()
                ]);
                return false;
            }
        }
        
        return false;
    }
    
    /**
     * Check if scheduled backup should run
     */
    private static function checkScheduledBackup() {
        $schedule = self::loadSchedule();
        
        if (!$schedule['enabled']) {
            return false;
        }
        
        $lastRun = $schedule['last_run'] ?? null;
        $now = time();
        
        // Determine if backup should run based on frequency
        $shouldRun = false;
        
        if (!$lastRun) {
            $shouldRun = true; // Never run before
        } else {
            $lastRunTime = strtotime($lastRun);
            $timeDiff = $now - $lastRunTime;
            
            switch ($schedule['frequency']) {
                case 'hourly':
                    $shouldRun = ($timeDiff >= 3600); // 1 hour
                    break;
                case 'daily':
                    $shouldRun = ($timeDiff >= 86400); // 24 hours
                    break;
                case 'weekly':
                    $shouldRun = ($timeDiff >= 604800); // 7 days
                    break;
            }
        }
        
        if ($shouldRun) {
            self::performScheduledBackup();
        }
        
        return $shouldRun;
    }
    
    /**
     * Perform the actual backup
     */
    private static function performScheduledBackup() {
        EnderBitLogger::logSystem('SCHEDULED_BACKUP_STARTED', [
            'timestamp' => date('Y-m-d H:i:s'),
            'triggered_by' => 'background_task'
        ]);
        
        if (!is_dir(self::$backupDir)) {
            mkdir(self::$backupDir, 0755, true);
        }
        
        $timestamp = date('Y-m-d_H-i-s');
        $backupFiles = [];
        $metadata = self::loadMetadata();
        
        // Regular JSON backup
        $jsonFiles = ['tokens.json', 'tickets.json', 'settings.json'];
        
        foreach ($jsonFiles as $file) {
            $filePath = __DIR__ . '/' . $file;
            if (file_exists($filePath)) {
                $fileType = pathinfo($file, PATHINFO_FILENAME);
                $backupFileName = $fileType . '_' . $timestamp . '.json';
                $backupPath = self::$backupDir . '/' . $backupFileName;
                
                if (copy($filePath, $backupPath)) {
                    $backupFiles[] = $backupFileName;
                    
                    // Store metadata for individual file
                    $metadata[$backupFileName] = [
                        'timestamp' => $timestamp,
                        'created' => time(),
                        'type' => $fileType
                    ];
                }
            }
        }
        
        // Store backup set metadata
        if (!empty($backupFiles)) {
            $metadata['sets'][$timestamp] = [
                'description' => 'Scheduled backup (automatic)',
                'created' => time(),
                'files' => $backupFiles,
                'type' => 'json'
            ];
            
            self::saveMetadata($metadata);
            
            // Update last run time
            $schedule = self::loadSchedule();
            $schedule['last_run'] = date('Y-m-d H:i:s');
            self::saveSchedule($schedule);
            
            EnderBitLogger::logAdmin('SCHEDULED_BACKUP_COMPLETED', 'BACKUP_JSON_FILES', [
                'files' => $backupFiles,
                'timestamp' => $timestamp,
                'source' => 'background_task'
            ]);
        } else {
            EnderBitLogger::logSystem('SCHEDULED_BACKUP_FAILED', [
                'timestamp' => date('Y-m-d H:i:s'),
                'reason' => 'No files backed up'
            ]);
        }
    }
    
    /**
     * Load schedule settings
     */
    private static function loadSchedule() {
        if (file_exists(self::$scheduleFile)) {
            $data = json_decode(file_get_contents(self::$scheduleFile), true);
            return is_array($data) ? $data : ['enabled' => false, 'frequency' => 'daily', 'last_run' => null];
        }
        return ['enabled' => false, 'frequency' => 'daily', 'last_run' => null];
    }
    
    /**
     * Save schedule settings
     */
    private static function saveSchedule($schedule) {
        if (!is_dir(self::$backupDir)) {
            mkdir(self::$backupDir, 0755, true);
        }
        file_put_contents(self::$scheduleFile, json_encode($schedule, JSON_PRETTY_PRINT));
    }
    
    /**
     * Load metadata
     */
    private static function loadMetadata() {
        if (file_exists(self::$metadataFile)) {
            $data = json_decode(file_get_contents(self::$metadataFile), true);
            return is_array($data) ? $data : [];
        }
        return [];
    }
    
    /**
     * Save metadata
     */
    private static function saveMetadata($metadata) {
        if (!is_dir(self::$backupDir)) {
            mkdir(self::$backupDir, 0755, true);
        }
        file_put_contents(self::$metadataFile, json_encode($metadata, JSON_PRETTY_PRINT));
    }
}
?>
