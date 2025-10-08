<?php
/**
 * EnderBit Logging Utility
 * Centralized logging system for all application events
 */

class EnderBitLogger {
    private static $logPath;
    
    public static function init() {
        self::$logPath = __DIR__ . '/';
    }
    
    /**
     * Log authentication events
     */
    public static function logAuth($event, $details = []) {
        $logFile = self::$logPath . 'auth.log';
        $ip = self::getClientIP();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        
        $entry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'type' => 'AUTH',
            'event' => $event,
            'ip' => $ip,
            'user_agent' => $userAgent,
            'details' => $details
        ];
        
        self::writeLog($logFile, $entry);
    }
    
    /**
     * Log user registration events
     */
    public static function logRegistration($event, $email, $details = []) {
        $logFile = self::$logPath . 'registration.log';
        $ip = self::getClientIP();
        
        $entry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'type' => 'REGISTRATION',
            'event' => $event,
            'email' => $email,
            'ip' => $ip,
            'details' => $details
        ];
        
        self::writeLog($logFile, $entry);
    }
    
    /**
     * Log ticket system events
     */
    public static function logTicket($event, $ticketId, $email = null, $details = []) {
        $logFile = self::$logPath . 'tickets.log';
        $ip = self::getClientIP();
        
        $entry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'type' => 'TICKET',
            'event' => $event,
            'ticket_id' => $ticketId,
            'email' => $email,
            'ip' => $ip,
            'details' => $details
        ];
        
        self::writeLog($logFile, $entry);
    }
    
    /**
     * Log email events
     */
    public static function logEmail($event, $to, $subject, $details = []) {
        $logFile = self::$logPath . 'email.log';
        
        $entry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'type' => 'EMAIL',
            'event' => $event,
            'to' => $to,
            'subject' => $subject,
            'details' => $details
        ];
        
        self::writeLog($logFile, $entry);
    }
    
    /**
     * Log Pterodactyl API events
     */
    public static function logPterodactyl($event, $endpoint, $details = []) {
        $logFile = self::$logPath . 'pterodactyl.log';
        
        $entry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'type' => 'PTERODACTYL',
            'event' => $event,
            'endpoint' => $endpoint,
            'details' => $details
        ];
        
        self::writeLog($logFile, $entry);
    }
    
    /**
     * Log admin actions
     */
    public static function logAdmin($event, $action, $details = []) {
        $logFile = self::$logPath . 'admin.log';
        $ip = self::getClientIP();
        
        $entry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'type' => 'ADMIN',
            'event' => $event,
            'action' => $action,
            'ip' => $ip,
            'details' => $details
        ];
        
        self::writeLog($logFile, $entry);
    }
    
    /**
     * Log security events
     */
    public static function logSecurity($event, $severity, $details = []) {
        $logFile = self::$logPath . 'security.log';
        $ip = self::getClientIP();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        
        $entry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'type' => 'SECURITY',
            'event' => $event,
            'severity' => $severity, // LOW, MEDIUM, HIGH, CRITICAL
            'ip' => $ip,
            'user_agent' => $userAgent,
            'details' => $details
        ];
        
        self::writeLog($logFile, $entry);
    }
    
    /**
     * Log system events
     */
    public static function logSystem($event, $details = []) {
        $logFile = self::$logPath . 'system.log';
        
        $entry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'type' => 'SYSTEM',
            'event' => $event,
            'details' => $details
        ];
        
        self::writeLog($logFile, $entry);
    }
    
    /**
     * Log file upload events
     */
    public static function logUpload($event, $filename, $details = []) {
        $logFile = self::$logPath . 'uploads.log';
        $ip = self::getClientIP();
        
        $entry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'type' => 'UPLOAD',
            'event' => $event,
            'filename' => $filename,
            'ip' => $ip,
            'details' => $details
        ];
        
        self::writeLog($logFile, $entry);
    }
    
    /**
     * Write log entry to file
     */
    private static function writeLog($logFile, $entry) {
        $logLine = json_encode($entry) . "\n";
        
        // Ensure log file exists and is writable
        if (!file_exists($logFile)) {
            @touch($logFile);
            @chmod($logFile, 0666);
        }
        
        // Write log entry
        @file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Get client IP address (Cloudflare-aware)
     */
    private static function getClientIP() {
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            return $_SERVER['HTTP_CF_CONNECTING_IP'];
        }
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return trim($ips[0]);
        }
        if (!empty($_SERVER['HTTP_X_REAL_IP'])) {
            return $_SERVER['HTTP_X_REAL_IP'];
        }
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    /**
     * Get all available log files
     */
    public static function getLogFiles() {
        $logs = [];
        $logTypes = [
            'auth' => 'Authentication',
            'registration' => 'User Registration',
            'tickets' => 'Support Tickets',
            'email' => 'Email System',
            'pterodactyl' => 'Pterodactyl API',
            'admin' => 'Admin Actions',
            'security' => 'Security Events',
            'system' => 'System Events',
            'uploads' => 'File Uploads',
            'php_errors' => 'PHP Errors',
            'deployment' => 'Git Deployments'
        ];
        
        foreach ($logTypes as $type => $name) {
            $file = self::$logPath . $type . '.log';
            $logs[$type] = [
                'name' => $name,
                'file' => $file,
                'exists' => file_exists($file),
                'size' => file_exists($file) ? filesize($file) : 0,
                'lines' => file_exists($file) ? count(file($file)) : 0
            ];
        }
        
        return $logs;
    }
    
    /**
     * Parse structured log file
     */
    public static function parseLogFile($logFile, $lines = 200, $search = '') {
        if (!file_exists($logFile)) {
            return [];
        }
        
        $logLines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($logLines === false) {
            return [];
        }
        
        // Get last N lines
        $logLines = array_slice($logLines, -$lines);
        
        $parsed = [];
        foreach ($logLines as $line) {
            $entry = json_decode($line, true);
            if ($entry === null) {
                // Handle plain text logs
                $entry = [
                    'timestamp' => 'Unknown',
                    'type' => 'PLAIN',
                    'event' => $line,
                    'details' => []
                ];
            }
            
            // Filter by search term
            if (!empty($search)) {
                $lineText = json_encode($entry);
                if (stripos($lineText, $search) === false) {
                    continue;
                }
            }
            
            $parsed[] = $entry;
        }
        
        return array_reverse($parsed); // Most recent first
    }
}

// Initialize logger
EnderBitLogger::init();
?>