<?php
/**
 * AyeT Studios Integration
 * Handles offerwall, rewarded videos, and credit rewards
 */

class AyeTStudios {
    private static $config;
    
    public static function init() {
        if (!self::$config) {
            $appConfig = require __DIR__ . '/config.php';
            self::$config = $appConfig['ayetstudios'];
        }
    }
    
    /**
     * Get offerwall URL for user
     */
    public static function getOfferwallUrl($userId) {
        self::init();
        
        if (!self::$config['enabled']) {
            return null;
        }
        
        $params = [
            'app_id' => self::$config['app_id'],
            'user_id' => $userId,
            'signature' => self::generateSignature($userId),
        ];
        
        return self::$config['offerwall_url'] . '?' . http_build_query($params);
    }
    
    /**
     * Generate signature for API requests
     */
    private static function generateSignature($userId) {
        self::init();
        return hash('sha256', $userId . self::$config['secret_key']);
    }
    
    /**
     * Verify callback signature from AyeT Studios
     */
    public static function verifyCallback($userId, $amount, $transactionId, $signature) {
        self::init();
        $expectedSignature = hash('sha256', $userId . $amount . $transactionId . self::$config['secret_key']);
        return hash_equals($expectedSignature, $signature);
    }
    
    /**
     * Process reward callback from AyeT Studios
     */
    public static function processReward($userId, $amount, $transactionId, $offerName = '') {
        require_once __DIR__ . '/credits.php';
        
        // Convert AyeT Studios currency to credits (you can adjust the conversion rate)
        $credits = round($amount / 10); // Example: 100 coins = 10 credits
        
        if ($credits > 0) {
            EnderBitCredits::addCredits(
                $userId, 
                $credits, 
                'ayetstudios', 
                "Earned from: {$offerName} (Transaction: {$transactionId})"
            );
            
            // Log the reward
            self::logReward($userId, $amount, $credits, $transactionId, $offerName);
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Log reward from AyeT Studios
     */
    private static function logReward($userId, $amount, $credits, $transactionId, $offerName) {
        $logFile = __DIR__ . '/ayetstudios_rewards.log';
        $logEntry = sprintf(
            "[%s] User: %s | Amount: %s | Credits: %s | Transaction: %s | Offer: %s\n",
            date('Y-m-d H:i:s'),
            $userId,
            $amount,
            $credits,
            $transactionId,
            $offerName
        );
        file_put_contents($logFile, $logEntry, FILE_APPEND);
    }
    
    /**
     * Get iframe embed code for offerwall
     */
    public static function getOfferwallEmbed($userId, $width = '100%', $height = '600px') {
        $url = self::getOfferwallUrl($userId);
        if (!$url) {
            return '<p>Offerwall is currently unavailable.</p>';
        }
        
        return sprintf(
            '<iframe src="%s" width="%s" height="%s" frameborder="0" style="border:none; border-radius:8px;"></iframe>',
            htmlspecialchars($url),
            htmlspecialchars($width),
            htmlspecialchars($height)
        );
    }
}
