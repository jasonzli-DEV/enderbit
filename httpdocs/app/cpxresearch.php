<?php
/**
 * CPX Research Integration
 * Handles surveys and credit rewards
 * Documentation: https://cpx-research.com/
 */

class CPXResearch {
    private static $config;
    
    public static function init() {
        if (!self::$config) {
            $appConfig = require __DIR__ . '/config.php';
            self::$config = $appConfig['cpxresearch'];
        }
    }
    
    /**
     * Get user info for CPX Research
     * Returns username and email if available
     */
    private static function getUserInfo($userId) {
        // Try to get user info from users.json
        $config = require __DIR__ . '/config.php';
        $usersFile = $config['users_file'];
        
        if (file_exists($usersFile)) {
            $users = json_decode(file_get_contents($usersFile), true) ?? [];
            foreach ($users as $user) {
                if ($user['email'] === $userId) {
                    return [
                        'email' => $user['email'],
                        'username' => $user['email'], // Use email as username if no separate username
                    ];
                }
            }
        }
        
        // Fallback - use email as both
        return [
            'email' => $userId,
            'username' => $userId,
        ];
    }
    
    /**
     * Generate secure hash for CPX Research
     * Formula: md5(app_id-secure_key-ext_user_id)
     */
    private static function generateSecureHash($userId) {
        self::init();
        
        if (empty(self::$config['secret_key'])) {
            // If no secret key configured, return empty hash
            // CPX will work without hash but less secure
            return '';
        }
        
        // CPX Research uses: md5(app_id-secure_key-ext_user_id)
        $hashString = self::$config['app_id'] . '-' . self::$config['secret_key'] . '-' . $userId;
        return md5($hashString);
    }
    
    /**
     * Get survey wall URL for user
     */
    public static function getSurveyWallUrl($userId) {
        self::init();
        
        if (!self::$config['enabled']) {
            return null;
        }
        
        $userInfo = self::getUserInfo($userId);
        $secureHash = self::generateSecureHash($userId);
        
        $params = [
            'app_id' => self::$config['app_id'],
            'ext_user_id' => $userId,
            'secure_hash' => $secureHash,
            'username' => $userInfo['username'],
            'email' => $userInfo['email'],
            'subid_1' => '', // Optional: you can track sources
            'subid_2' => '', // Optional: you can track sources
        ];
        
        return self::$config['survey_url'] . '?' . http_build_query($params);
    }
    
    /**
     * Verify callback from CPX Research
     * CPX sends: user_id, transaction_id, currency_amount, payout, type, status
     */
    public static function verifyCallback($params) {
        self::init();
        
        // CPX Research doesn't send a hash in callbacks by default
        // They verify by IP whitelist instead
        // You should whitelist CPX Research IPs in your firewall
        
        // Basic validation
        if (empty($params['user_id']) || empty($params['transaction_id'])) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Process reward callback from CPX Research
     * CPX sends: user_id, transaction_id, currency_amount, type, status
     */
    public static function processReward($userId, $amount, $transactionId, $type = '', $status = '') {
        require_once __DIR__ . '/credits.php';
        
        // Convert amount to credits (CPX typically sends in local currency or points)
        // Adjust conversion rate as needed
        $credits = round($amount * 10); // Example: 1 unit = 10 credits
        
        if ($credits > 0) {
            EnderBitCredits::addCredits(
                $userId, 
                $credits, 
                'cpxresearch', 
                "Survey completed (Type: {$type}, Status: {$status}, Transaction: {$transactionId})"
            );
            
            // Log the reward
            self::logReward($userId, $amount, $credits, $transactionId, $type, $status);
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Log reward from CPX Research
     */
    private static function logReward($userId, $amount, $credits, $transactionId, $type, $status) {
        $logFile = __DIR__ . '/cpxresearch_rewards.log';
        $logEntry = sprintf(
            "[%s] User: %s | Amount: %s | Credits: %s | Transaction: %s | Type: %s | Status: %s\n",
            date('Y-m-d H:i:s'),
            $userId,
            $amount,
            $credits,
            $transactionId,
            $type,
            $status
        );
        file_put_contents($logFile, $logEntry, FILE_APPEND);
    }
    
    /**
     * Get iframe embed code for survey wall
     */
    public static function getSurveyWallEmbed($userId, $width = '100%', $height = '2000px') {
        $url = self::getSurveyWallUrl($userId);
        if (!$url) {
            return '<p>Survey wall is currently unavailable.</p>';
        }
        
        // Use exact format from CPX Research
        return sprintf(
            '<iframe width="%s" frameBorder="0" height="%s" src="%s"></iframe>',
            htmlspecialchars($width),
            htmlspecialchars($height),
            htmlspecialchars($url)
        );
    }
}
