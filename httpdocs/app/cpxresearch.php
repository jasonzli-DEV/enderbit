<?php
/**
 * CPX Research Integration
 * Handles surveys and credit rewards
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
     * Get survey wall URL for user
     */
    public static function getSurveyWallUrl($userId) {
        self::init();
        
        if (!self::$config['enabled']) {
            return null;
        }
        
        $params = [
            'app_id' => self::$config['app_id'],
            'ext_user_id' => $userId,
            'secure_hash' => self::generateSecureHash($userId),
        ];
        
        return self::$config['survey_url'] . '?' . http_build_query($params);
    }
    
    /**
     * Generate secure hash for API requests
     */
    private static function generateSecureHash($userId) {
        self::init();
        return hash('sha256', $userId . self::$config['app_id'] . self::$config['secret_key']);
    }
    
    /**
     * Verify callback signature from CPX Research
     */
    public static function verifyCallback($userId, $amount, $transactionId, $hash) {
        self::init();
        $expectedHash = hash('sha256', $transactionId . $userId . $amount . self::$config['secret_key']);
        return hash_equals($expectedHash, $hash);
    }
    
    /**
     * Process reward callback from CPX Research
     */
    public static function processReward($userId, $amount, $transactionId, $surveyName = '') {
        require_once __DIR__ . '/credits.php';
        
        // CPX Research typically pays in cents, convert to credits
        $credits = round($amount); // Direct conversion: cents = credits
        
        if ($credits > 0) {
            EnderBitCredits::addCredits(
                $userId, 
                $credits, 
                'cpxresearch', 
                "Survey completed: {$surveyName} (Transaction: {$transactionId})"
            );
            
            // Log the reward
            self::logReward($userId, $amount, $credits, $transactionId, $surveyName);
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Log reward from CPX Research
     */
    private static function logReward($userId, $amount, $credits, $transactionId, $surveyName) {
        $logFile = __DIR__ . '/cpxresearch_rewards.log';
        $logEntry = sprintf(
            "[%s] User: %s | Amount: %s cents | Credits: %s | Transaction: %s | Survey: %s\n",
            date('Y-m-d H:i:s'),
            $userId,
            $amount,
            $credits,
            $transactionId,
            $surveyName
        );
        file_put_contents($logFile, $logEntry, FILE_APPEND);
    }
    
    /**
     * Get iframe embed code for survey wall
     */
    public static function getSurveyWallEmbed($userId, $width = '100%', $height = '600px') {
        $url = self::getSurveyWallUrl($userId);
        if (!$url) {
            return '<p>Survey wall is currently unavailable.</p>';
        }
        
        return sprintf(
            '<iframe src="%s" width="%s" height="%s" frameborder="0" style="border:none; border-radius:8px;"></iframe>',
            htmlspecialchars($url),
            htmlspecialchars($width),
            htmlspecialchars($height)
        );
    }
}

