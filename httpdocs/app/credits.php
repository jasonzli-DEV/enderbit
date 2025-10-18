<?php
/**
 * EnderBit Credit Management System
 * Handles credit balances, transactions, and billing
 */

class EnderBitCredits {
    private static $config;
    
    public static function init() {
        if (!self::$config) {
            self::$config = require __DIR__ . '/config.php';
        }
    }
    
    /**
     * Get user's credit balance
     */
    public static function getBalance($userId) {
        self::init();
        $credits = self::loadCredits();
        return $credits[$userId] ?? 0;
    }
    
    /**
     * Add credits to user account
     */
    public static function addCredits($userId, $amount, $source, $description = '') {
        self::init();
        $credits = self::loadCredits();
        
        if (!isset($credits[$userId])) {
            $credits[$userId] = 0;
        }
        
        $credits[$userId] += $amount;
        self::saveCredits($credits);
        
        // Log transaction
        self::logTransaction($userId, $amount, 'credit', $source, $description);
        
        return $credits[$userId];
    }
    
    /**
     * Deduct credits from user account
     */
    public static function deductCredits($userId, $amount, $reason, $description = '') {
        self::init();
        $credits = self::loadCredits();
        
        if (!isset($credits[$userId]) || $credits[$userId] < $amount) {
            return false; // Insufficient balance
        }
        
        $credits[$userId] -= $amount;
        self::saveCredits($credits);
        
        // Log transaction
        self::logTransaction($userId, -$amount, 'debit', $reason, $description);
        
        return $credits[$userId];
    }
    
    /**
     * Check if user has enough credits
     */
    public static function hasBalance($userId, $amount) {
        return self::getBalance($userId) >= $amount;
    }
    
    /**
     * Log a transaction
     */
    private static function logTransaction($userId, $amount, $type, $source, $description) {
        $transactions = self::loadTransactions();
        
        $transactions[] = [
            'id' => uniqid('txn_', true),
            'user_id' => $userId,
            'amount' => $amount,
            'type' => $type, // credit or debit
            'source' => $source, // purchase, ayetstudios, server_billing, signup_bonus, etc.
            'description' => $description,
            'balance_after' => self::getBalance($userId),
            'timestamp' => time(),
            'date' => date('Y-m-d H:i:s'),
        ];
        
        self::saveTransactions($transactions);
    }
    
    /**
     * Get user's transaction history
     */
    public static function getTransactions($userId, $limit = 50) {
        $transactions = self::loadTransactions();
        $userTransactions = array_filter($transactions, function($txn) use ($userId) {
            return $txn['user_id'] === $userId;
        });
        
        // Sort by timestamp descending
        usort($userTransactions, function($a, $b) {
            return $b['timestamp'] - $a['timestamp'];
        });
        
        return array_slice($userTransactions, 0, $limit);
    }
    
    /**
     * Grant signup bonus credits
     */
    public static function grantSignupBonus($userId) {
        self::init();
        $bonusAmount = self::$config['credits']['free_signup_credits'];
        return self::addCredits($userId, $bonusAmount, 'signup_bonus', 'Welcome bonus credits');
    }
    
    /**
     * Calculate hourly cost for a server
     */
    public static function calculateHourlyCost($game, $plan) {
        self::init();
        if (isset(self::$config['server_pricing'][$game][$plan]['cost_per_hour'])) {
            return self::$config['server_pricing'][$game][$plan]['cost_per_hour'];
        }
        return 0;
    }
    
    /**
     * Format credits for display
     */
    public static function formatCredits($amount) {
        self::init();
        $symbol = self::$config['credits']['currency_symbol'];
        return $symbol . number_format($amount, 0);
    }
    
    /**
     * Load credits from file
     */
    private static function loadCredits() {
        self::init();
        $file = self::$config['credits_file'];
        if (!file_exists($file)) {
            return [];
        }
        $data = json_decode(file_get_contents($file), true);
        return $data ?? [];
    }
    
    /**
     * Save credits to file
     */
    private static function saveCredits($credits) {
        self::init();
        $file = self::$config['credits_file'];
        file_put_contents($file, json_encode($credits, JSON_PRETTY_PRINT));
    }
    
    /**
     * Load transactions from file
     */
    private static function loadTransactions() {
        self::init();
        $file = self::$config['transactions_file'];
        if (!file_exists($file)) {
            return [];
        }
        $data = json_decode(file_get_contents($file), true);
        return $data ?? [];
    }
    
    /**
     * Save transactions to file
     */
    private static function saveTransactions($transactions) {
        self::init();
        $file = self::$config['transactions_file'];
        file_put_contents($file, json_encode($transactions, JSON_PRETTY_PRINT));
    }
}
