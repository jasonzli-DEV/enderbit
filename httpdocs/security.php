<?php
/**
 * EnderBit Security Utilities
 * 
 * Provides security features including:
 * - Password hashing and verification
 * - CSRF token generation and validation
 * - Rate limiting for login attempts
 * - Security headers
 */

class EnderBitSecurity {
    
    /**
     * Generate a CSRF token for form protection
     * 
     * @return string The generated CSRF token
     */
    public static function generateCSRFToken() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Validate a CSRF token from a form submission
     * 
     * @param string $token The token to validate
     * @return bool True if valid, false otherwise
     */
    public static function validateCSRFToken($token) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }
        
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Output CSRF token as hidden input field
     * 
     * @return string HTML hidden input field
     */
    public static function csrfField() {
        $token = self::generateCSRFToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }
    
    /**
     * Hash a password securely
     * 
     * @param string $password The plain text password
     * @return string The hashed password
     */
    public static function hashPassword($password) {
        // Try Argon2id first (most secure, PHP 7.3+)
        if (defined('PASSWORD_ARGON2ID')) {
            try {
                return password_hash($password, PASSWORD_ARGON2ID, [
                    'memory_cost' => 65536,
                    'time_cost' => 4
                    // Note: 'threads' option removed for broader compatibility
                ]);
            } catch (ValueError $e) {
                // Fall back to bcrypt if Argon2id not supported
            }
        }
        
        // Fall back to bcrypt (PHP 5.5+, widely supported)
        return password_hash($password, PASSWORD_BCRYPT, [
            'cost' => 12
        ]);
    }
    
    /**
     * Verify a password against a hash
     * 
     * @param string $password The plain text password
     * @param string $hash The hashed password
     * @return bool True if password matches, false otherwise
     */
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    /**
     * Check if password needs rehashing (algorithm updated)
     * 
     * @param string $hash The current hash
     * @return bool True if rehash needed, false otherwise
     */
    public static function needsRehash($hash) {
        // Check if using outdated algorithm or cost factors
        // First check against Argon2id if available
        if (defined('PASSWORD_ARGON2ID')) {
            return password_needs_rehash($hash, PASSWORD_ARGON2ID, [
                'memory_cost' => 65536,
                'time_cost' => 4
            ]);
        }
        
        // Fall back to bcrypt check
        return password_needs_rehash($hash, PASSWORD_BCRYPT, [
            'cost' => 12
        ]);
    }
    
    /**
     * Check rate limiting for login attempts
     * 
     * @param string $identifier Unique identifier (IP address, username, etc.)
     * @param int $maxAttempts Maximum attempts allowed
     * @param int $timeWindow Time window in seconds
     * @return array ['allowed' => bool, 'remaining' => int, 'reset_time' => int]
     */
    public static function checkRateLimit($identifier, $maxAttempts = 5, $timeWindow = 300) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $rateLimitFile = __DIR__ . '/rate_limits.json';
        $rateLimits = [];
        
        if (file_exists($rateLimitFile)) {
            $rateLimits = json_decode(file_get_contents($rateLimitFile), true);
            if (!is_array($rateLimits)) {
                $rateLimits = [];
            }
        }
        
        $now = time();
        $key = 'login_' . hash('sha256', $identifier);
        
        // Clean up old entries
        if (isset($rateLimits[$key])) {
            $rateLimits[$key] = array_filter($rateLimits[$key], function($timestamp) use ($now, $timeWindow) {
                return ($now - $timestamp) < $timeWindow;
            });
        } else {
            $rateLimits[$key] = [];
        }
        
        $attempts = count($rateLimits[$key]);
        $allowed = $attempts < $maxAttempts;
        
        if ($allowed) {
            $rateLimits[$key][] = $now;
            file_put_contents($rateLimitFile, json_encode($rateLimits, JSON_PRETTY_PRINT));
        }
        
        $oldestAttempt = !empty($rateLimits[$key]) ? min($rateLimits[$key]) : $now;
        $resetTime = $oldestAttempt + $timeWindow;
        
        return [
            'allowed' => $allowed,
            'attempts' => $attempts,
            'remaining' => max(0, $maxAttempts - $attempts - 1),
            'reset_time' => $resetTime,
            'wait_seconds' => $allowed ? 0 : max(0, $resetTime - $now)
        ];
    }
    
    /**
     * Reset rate limit for an identifier (on successful login)
     * 
     * @param string $identifier Unique identifier
     */
    public static function resetRateLimit($identifier) {
        $rateLimitFile = __DIR__ . '/rate_limits.json';
        
        if (file_exists($rateLimitFile)) {
            $rateLimits = json_decode(file_get_contents($rateLimitFile), true);
            if (!is_array($rateLimits)) {
                return;
            }
            
            $key = 'login_' . hash('sha256', $identifier);
            if (isset($rateLimits[$key])) {
                unset($rateLimits[$key]);
                file_put_contents($rateLimitFile, json_encode($rateLimits, JSON_PRETTY_PRINT));
            }
        }
    }
    
    /**
     * Set security headers for the response
     */
    public static function setSecurityHeaders() {
        // Prevent clickjacking
        header('X-Frame-Options: SAMEORIGIN');
        
        // Prevent MIME type sniffing
        header('X-Content-Type-Options: nosniff');
        
        // Enable XSS protection
        header('X-XSS-Protection: 1; mode=block');
        
        // Referrer policy
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        // Content Security Policy (adjust as needed)
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://www.google.com https://www.gstatic.com; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self' data:; frame-src https://www.google.com;");
        
        // Force HTTPS (uncomment if using HTTPS)
        // header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
    
    /**
     * Sanitize user input
     * 
     * @param string $input The input to sanitize
     * @param string $type The type of sanitization (email, string, html)
     * @return string The sanitized input
     */
    public static function sanitizeInput($input, $type = 'string') {
        switch ($type) {
            case 'email':
                return filter_var($input, FILTER_SANITIZE_EMAIL);
            
            case 'html':
                return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
            
            case 'string':
            default:
                return trim(strip_tags($input));
        }
    }
    
    /**
     * Validate email address
     * 
     * @param string $email The email to validate
     * @return bool True if valid, false otherwise
     */
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Generate a secure random token
     * 
     * @param int $length The length of the token
     * @return string The generated token
     */
    public static function generateToken($length = 32) {
        return bin2hex(random_bytes($length));
    }
    
    /**
     * Get client IP address (considering proxies)
     * 
     * @return string The client IP address
     */
    public static function getClientIP() {
        $ipKeys = [
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR'
        ];
        
        foreach ($ipKeys as $key) {
            if (isset($_SERVER[$key]) && !empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                // Handle comma-separated IPs (proxies)
                if (strpos($ip, ',') !== false) {
                    $ips = explode(',', $ip);
                    $ip = trim($ips[0]);
                }
                
                // Validate IP
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * Log security event
     * 
     * @param string $event Event type
     * @param string $severity Severity level (LOW, MEDIUM, HIGH, CRITICAL)
     * @param array $context Additional context
     */
    public static function logSecurityEvent($event, $severity, $context = []) {
        if (class_exists('EnderBitLogger')) {
            $context['ip'] = self::getClientIP();
            $context['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
            EnderBitLogger::logSecurity($event, $severity, $context);
        }
    }
}
