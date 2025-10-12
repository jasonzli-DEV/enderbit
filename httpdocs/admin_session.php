<?php
/**
 * EnderBit Admin Session Manager
 * Centralized session handling with time-based validation
 * Handles both session cookies and remember me functionality
 */

require_once __DIR__ . '/logger.php';

class EnderBitAdminSession {
    private static $sessionTimeout = 86400; // 24 hours in seconds
    private static $rememberMeDuration = 2592000; // 30 days in seconds
    
    /**
     * Initialize admin session
     * Call this at the start of every admin page
     */
    public static function init() {
        // Configure session settings
        ini_set('session.cookie_lifetime', 0); // Session cookie by default
        ini_set('session.gc_maxlifetime', self::$sessionTimeout);
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        self::validateSession();
    }
    
    /**
     * Validate the current session based on time
     */
    private static function validateSession() {
        // Check if remember me cookie exists
        $hasRememberCookie = isset($_COOKIE['admin_remember']) && $_COOKIE['admin_remember'] === 'true';
        
        if ($hasRememberCookie) {
            // Check remember me timestamp
            $rememberTimestamp = isset($_COOKIE['admin_remember_time']) ? intval($_COOKIE['admin_remember_time']) : 0;
            $now = time();
            
            if ($rememberTimestamp > 0 && ($now - $rememberTimestamp) < self::$rememberMeDuration) {
                // Remember me is still valid
                if (!isset($_SESSION['admin_logged_in'])) {
                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['login_time'] = $rememberTimestamp;
                    $_SESSION['remember_me'] = true;
                }
                
                // Refresh cookies
                self::refreshRememberCookie();
            } else {
                // Remember me expired
                self::clearRememberCookie();
                if (isset($_SESSION['remember_me'])) {
                    EnderBitLogger::logAuth('ADMIN_SESSION_EXPIRED', [
                        'reason' => 'Remember me cookie expired',
                        'duration' => '30 days'
                    ]);
                    self::logout();
                }
            }
        } else {
            // No remember me - check session timeout
            if (isset($_SESSION['admin_logged_in']) && isset($_SESSION['login_time'])) {
                $loginTime = intval($_SESSION['login_time']);
                $now = time();
                
                // Check if session has expired (24 hours)
                if (($now - $loginTime) >= self::$sessionTimeout) {
                    EnderBitLogger::logAuth('ADMIN_SESSION_EXPIRED', [
                        'reason' => 'Session timeout',
                        'duration' => '24 hours'
                    ]);
                    self::logout();
                }
            }
        }
    }
    
    /**
     * Login admin with optional remember me
     */
    public static function login($rememberMe = false) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['login_time'] = time();
        $_SESSION['remember_me'] = $rememberMe;
        
        if ($rememberMe) {
            self::setRememberCookie();
        } else {
            self::clearRememberCookie();
        }
        
        EnderBitLogger::logAuth('ADMIN_LOGIN_SUCCESS', [
            'admin' => true,
            'remember_me' => $rememberMe,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Logout admin
     */
    public static function logout() {
        self::clearRememberCookie();
        
        EnderBitLogger::logAuth('ADMIN_LOGOUT', [
            'admin' => true,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        
        session_destroy();
        session_start(); // Restart for logout message
    }
    
    /**
     * Check if admin is logged in
     */
    public static function isLoggedIn() {
        return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
    }
    
    /**
     * Set remember me cookie
     */
    private static function setRememberCookie() {
        $now = time();
        $expire = $now + self::$rememberMeDuration;
        
        setcookie('admin_remember', 'true', $expire, '/', '', isset($_SERVER['HTTPS']), true);
        setcookie('admin_remember_time', strval($now), $expire, '/', '', isset($_SERVER['HTTPS']), true);
        
        // Extend session cookie lifetime
        if (session_status() === PHP_SESSION_ACTIVE) {
            $params = session_get_cookie_params();
            setcookie(session_name(), session_id(), $expire, 
                $params['path'], $params['domain'], isset($_SERVER['HTTPS']), true);
        }
    }
    
    /**
     * Refresh remember me cookie (on each page load)
     */
    private static function refreshRememberCookie() {
        if (isset($_COOKIE['admin_remember']) && $_COOKIE['admin_remember'] === 'true') {
            $expire = time() + self::$rememberMeDuration;
            
            setcookie('admin_remember', 'true', $expire, '/', '', isset($_SERVER['HTTPS']), true);
            
            // Keep the original login time, don't update it
            if (isset($_COOKIE['admin_remember_time'])) {
                $originalTime = $_COOKIE['admin_remember_time'];
                setcookie('admin_remember_time', $originalTime, $expire, '/', '', isset($_SERVER['HTTPS']), true);
            }
            
            // Extend session cookie lifetime
            if (session_status() === PHP_SESSION_ACTIVE) {
                $params = session_get_cookie_params();
                setcookie(session_name(), session_id(), $expire, 
                    $params['path'], $params['domain'], isset($_SERVER['HTTPS']), true);
            }
        }
    }
    
    /**
     * Clear remember me cookie
     */
    private static function clearRememberCookie() {
        setcookie('admin_remember', '', time() - 3600, '/', '', isset($_SERVER['HTTPS']), true);
        setcookie('admin_remember_time', '', time() - 3600, '/', '', isset($_SERVER['HTTPS']), true);
        
        // Reset session cookie to browser session
        if (session_status() === PHP_SESSION_ACTIVE) {
            $params = session_get_cookie_params();
            setcookie(session_name(), session_id(), 0, 
                $params['path'], $params['domain'], isset($_SERVER['HTTPS']), true);
        }
    }
    
    /**
     * Get session info for debugging
     */
    public static function getSessionInfo() {
        return [
            'logged_in' => self::isLoggedIn(),
            'login_time' => $_SESSION['login_time'] ?? null,
            'remember_me' => $_SESSION['remember_me'] ?? false,
            'time_since_login' => isset($_SESSION['login_time']) ? (time() - intval($_SESSION['login_time'])) : null,
            'session_expires_in' => isset($_SESSION['login_time']) ? (self::$sessionTimeout - (time() - intval($_SESSION['login_time']))) : null
        ];
    }
}
?>
