<?php
/**
 * EnderBit Hourly Billing System
 * Runs every hour to bill active servers and manage suspensions
 * Add this to background_tasks.php or run via cron
 */

class EnderBitBilling {
    
    /**
     * Process hourly billing for all active servers
     */
    public static function processHourlyBilling() {
        require_once __DIR__ . '/credits.php';
        require_once __DIR__ . '/pterodactyl_api.php';
        
        $config = require __DIR__ . '/config.php';
        $serversFile = $config['servers_file'];
        $servers = json_decode(file_get_contents($serversFile), true) ?? [];
        
        $billedCount = 0;
        $suspendedCount = 0;
        
        foreach ($servers as &$server) {
            if ($server['status'] !== 'active') {
                continue; // Skip suspended/deleted servers
            }
            
            $userId = $server['user_id'];
            $costPerHour = $server['cost_per_hour'];
            $lastBilled = $server['last_billed'] ?? time();
            $currentTime = time();
            
            // Calculate hours since last billing
            $hoursSinceLastBill = ($currentTime - $lastBilled) / 3600;
            
            // Only bill if at least 1 hour has passed
            if ($hoursSinceLastBill >= 1) {
                $hoursToBill = floor($hoursSinceLastBill);
                $totalCost = $hoursToBill * $costPerHour;
                
                // Check if user has enough credits
                if (EnderBitCredits::hasBalance($userId, $totalCost)) {
                    // Deduct credits
                    EnderBitCredits::deductCredits(
                        $userId, 
                        $totalCost, 
                        'server_billing',
                        "Server: {$server['name']} ({$hoursToBill}h @ {$costPerHour} credits/h)"
                    );
                    
                    // Update last billed time
                    $server['last_billed'] = $currentTime;
                    $billedCount++;
                    
                    self::logBilling($server['id'], $userId, $totalCost, $hoursToBill, 'success');
                } else {
                    // Insufficient credits - suspend server
                    $server['status'] = 'suspended';
                    $server['suspended_reason'] = 'Insufficient credits';
                    $server['suspended_at'] = $currentTime;
                    
                    // Suspend in Pterodactyl
                    PterodactylAPI::suspendServer($server['pterodactyl_id']);
                    
                    $suspendedCount++;
                    self::logBilling($server['id'], $userId, $totalCost, $hoursToBill, 'suspended');
                    
                    // Notify user (you can implement email/notification here)
                    self::notifyUserSuspension($userId, $server);
                }
            }
        }
        
        // Save updated servers
        file_put_contents($serversFile, json_encode($servers, JSON_PRETTY_PRINT));
        
        // Log billing run
        self::logBillingRun($billedCount, $suspendedCount);
        
        return [
            'billed' => $billedCount,
            'suspended' => $suspendedCount,
        ];
    }
    
    /**
     * Log billing event
     */
    private static function logBilling($serverId, $userId, $amount, $hours, $status) {
        $logFile = __DIR__ . '/billing.log';
        $logEntry = sprintf(
            "[%s] Server: %s | User: %s | Amount: %s credits | Hours: %s | Status: %s\n",
            date('Y-m-d H:i:s'),
            $serverId,
            $userId,
            $amount,
            $hours,
            $status
        );
        file_put_contents($logFile, $logEntry, FILE_APPEND);
    }
    
    /**
     * Log billing run summary
     */
    private static function logBillingRun($billed, $suspended) {
        $logFile = __DIR__ . '/billing.log';
        $logEntry = sprintf(
            "[%s] === Billing Run Complete === Billed: %d servers | Suspended: %d servers\n\n",
            date('Y-m-d H:i:s'),
            $billed,
            $suspended
        );
        file_put_contents($logFile, $logEntry, FILE_APPEND);
    }
    
    /**
     * Notify user about server suspension
     */
    private static function notifyUserSuspension($userId, $server) {
        // You can implement email notification here
        // For now, just log it
        $logFile = __DIR__ . '/suspension_notifications.log';
        $logEntry = sprintf(
            "[%s] User %s - Server '%s' suspended due to insufficient credits\n",
            date('Y-m-d H:i:s'),
            $userId,
            $server['name']
        );
        file_put_contents($logFile, $logEntry, FILE_APPEND);
    }
    
    /**
     * Unsuspend a server if user adds credits
     */
    public static function unsuspendServer($serverId, $userId) {
        require_once __DIR__ . '/credits.php';
        require_once __DIR__ . '/pterodactyl_api.php';
        
        $config = require __DIR__ . '/config.php';
        $serversFile = $config['servers_file'];
        $servers = json_decode(file_get_contents($serversFile), true) ?? [];
        
        foreach ($servers as &$server) {
            if ($server['id'] === $serverId && $server['user_id'] === $userId) {
                if ($server['status'] !== 'suspended') {
                    return ['success' => false, 'error' => 'Server is not suspended'];
                }
                
                // Check if user has minimum balance
                $minimumBalance = $config['credits']['minimum_balance'];
                if (!EnderBitCredits::hasBalance($userId, $minimumBalance)) {
                    return ['success' => false, 'error' => 'Insufficient credits. Need at least ' . $minimumBalance . ' credits.'];
                }
                
                // Unsuspend in Pterodactyl
                $result = PterodactylAPI::unsuspendServer($server['pterodactyl_id']);
                
                if ($result['success']) {
                    $server['status'] = 'active';
                    $server['last_billed'] = time();
                    unset($server['suspended_reason']);
                    unset($server['suspended_at']);
                    
                    file_put_contents($serversFile, json_encode($servers, JSON_PRETTY_PRINT));
                    
                    return ['success' => true, 'message' => 'Server unsuspended successfully'];
                }
                
                return $result;
            }
        }
        
        return ['success' => false, 'error' => 'Server not found'];
    }
}

// If this file is run directly (e.g., via cron), execute billing
if (php_sapi_name() === 'cli' || (isset($argv) && basename($argv[0]) === basename(__FILE__))) {
    echo "Running hourly billing...\n";
    $result = EnderBitBilling::processHourlyBilling();
    echo "Billed: {$result['billed']} servers\n";
    echo "Suspended: {$result['suspended']} servers\n";
    echo "Complete!\n";
}
