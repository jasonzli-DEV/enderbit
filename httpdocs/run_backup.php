<?php
/**
 * Standalone Backup Runner
 * 
 * This file can be called by external cron services (no server access needed)
 * or by simple HTTP requests to run backups automatically.
 * 
 * Usage:
 * 1. Via external cron service (e.g., cron-job.org, easycron.com):
 *    URL: https://yourdomain.com/run_backup.php?key=YOUR_SECRET_KEY
 * 
 * 2. Via curl/wget:
 *    curl https://yourdomain.com/run_backup.php?key=YOUR_SECRET_KEY
 * 
 * 3. Via browser (for testing):
 *    https://yourdomain.com/run_backup.php?key=YOUR_SECRET_KEY
 */

// Security: Require a secret key to prevent unauthorized backup runs
$BACKUP_SECRET_KEY = 'CHANGE_THIS_TO_A_RANDOM_STRING'; // TODO: Change this!

// Check for secret key
$providedKey = $_GET['key'] ?? '';
if ($providedKey !== $BACKUP_SECRET_KEY) {
    http_response_code(403);
    header('Content-Type: application/json');
    die(json_encode(['error' => 'Unauthorized', 'message' => 'Invalid or missing key']));
}

// Load required files
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/background_tasks.php';
require_once __DIR__ . '/logger.php';

// Set execution time limit (backups can take time)
set_time_limit(300); // 5 minutes

// Log the backup run
EnderBitLogger::logSystem('STANDALONE_BACKUP_TRIGGERED', [
    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
]);

// Run the scheduled tasks (backups)
try {
    EnderBitBackgroundTasks::runScheduledTasks();
    
    // Get schedule info to return status
    $scheduleFile = __DIR__ . '/backups/schedule.json';
    $schedule = ['enabled' => false, 'frequency' => 'daily', 'last_run' => null];
    if (file_exists($scheduleFile)) {
        $schedule = json_decode(file_get_contents($scheduleFile), true);
    }
    
    $response = [
        'success' => true,
        'message' => 'Backup check completed',
        'schedule' => [
            'enabled' => $schedule['enabled'] ?? false,
            'frequency' => $schedule['frequency'] ?? 'daily',
            'last_run' => $schedule['last_run'] ?? null,
            'last_run_human' => $schedule['last_run'] ? date('Y-m-d H:i:s', $schedule['last_run']) : 'Never'
        ],
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    http_response_code(200);
    header('Content-Type: application/json');
    echo json_encode($response, JSON_PRETTY_PRINT);
    
    EnderBitLogger::logSystem('STANDALONE_BACKUP_SUCCESS', [
        'last_run' => $schedule['last_run'] ?? null
    ]);
    
} catch (Exception $e) {
    $response = [
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode($response, JSON_PRETTY_PRINT);
    
    EnderBitLogger::logSystem('STANDALONE_BACKUP_ERROR', [
        'error' => $e->getMessage()
    ]);
}
