<?php
/**
 * Timezone Utility Functions
 * Detects user timezone based on IP address and provides formatting functions
 */

// Get user's timezone based on IP address
function getUserTimezone() {
    // Check if timezone is already stored in session
    if (isset($_SESSION['user_timezone'])) {
        return $_SESSION['user_timezone'];
    }
    
    // Get user's IP address
    $userIP = getUserIP();
    
    // Try to detect timezone from IP
    $timezone = getTimezoneFromIP($userIP);
    
    // Store in session for future requests
    $_SESSION['user_timezone'] = $timezone;
    
    return $timezone;
}

// Get user's IP address
function getUserIP() {
    $ip = '';
    
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        // Handle proxy/load balancer cases
        $ipList = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($ipList[0]);
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    
    // Don't try to geolocate local IPs
    if ($ip === '127.0.0.1' || $ip === '::1' || strpos($ip, '192.168.') === 0 || strpos($ip, '10.') === 0) {
        return null;
    }
    
    return $ip;
}

// Get timezone from IP address using ip-api.com (free, no API key required)
function getTimezoneFromIP($ip) {
    if (!$ip) {
        return 'America/New_York'; // Default fallback
    }
    
    // Try to get from cookie first (stored as hashed IP to timezone mapping)
    $cookieName = 'tz_' . substr(md5($ip), 0, 8); // Short hash of IP
    if (isset($_COOKIE[$cookieName])) {
        $cookieData = json_decode($_COOKIE[$cookieName], true);
        if ($cookieData && isset($cookieData['tz']) && isset($cookieData['exp'])) {
            // Check if cookie is still valid (7 days = 604800 seconds)
            if (time() < $cookieData['exp']) {
                return $cookieData['tz'];
            }
        }
    }
    
    // Try to fetch timezone from API
    try {
        $url = "http://ip-api.com/json/{$ip}?fields=timezone,status";
        $context = stream_context_create([
            'http' => [
                'timeout' => 2, // 2 second timeout
                'ignore_errors' => true
            ]
        ]);
        
        $response = @file_get_contents($url, false, $context);
        
        if ($response !== false) {
            $data = json_decode($response, true);
            
            if (isset($data['status']) && $data['status'] === 'success' && isset($data['timezone'])) {
                $timezone = $data['timezone'];
                
                // Store in cookie for 7 days
                $cookieData = [
                    'tz' => $timezone,
                    'exp' => time() + 604800 // 7 days
                ];
                setcookie(
                    $cookieName,
                    json_encode($cookieData),
                    time() + 604800, // 7 days
                    '/',
                    '',
                    false, // Not HTTPS-only (change to true if using HTTPS)
                    true   // HTTP-only for security
                );
                
                return $timezone;
            }
        }
    } catch (Exception $e) {
        // Silently fail and use default
    }
    
    // Fallback to default timezone
    return 'America/New_York';
}

// Format timestamp in user's timezone
function formatTimeInUserTZ($timestamp, $format = 'M j, Y, g:i A') {
    $timezone = getUserTimezone();
    
    try {
        $dt = new DateTime();
        $dt->setTimestamp($timestamp);
        $dt->setTimezone(new DateTimeZone($timezone));
        return $dt->format($format);
    } catch (Exception $e) {
        // Fallback to server time if timezone is invalid
        return date($format, $timestamp);
    }
}

// Format datetime string in user's timezone
function formatDateTimeInUserTZ($datetime, $format = 'M j, Y, g:i A') {
    $timezone = getUserTimezone();
    
    try {
        $dt = new DateTime($datetime);
        $dt->setTimezone(new DateTimeZone($timezone));
        return $dt->format($format);
    } catch (Exception $e) {
        // Fallback to server time if timezone is invalid
        return date($format, strtotime($datetime));
    }
}

// Get timezone abbreviation (e.g., EST, PST)
function getTimezoneAbbr() {
    $timezone = getUserTimezone();
    
    try {
        $dt = new DateTime();
        $dt->setTimezone(new DateTimeZone($timezone));
        return $dt->format('T');
    } catch (Exception $e) {
        return 'UTC';
    }
}

// Get timezone offset from UTC (e.g., -5:00, +3:00)
function getTimezoneOffset() {
    $timezone = getUserTimezone();
    
    try {
        $dt = new DateTime();
        $dt->setTimezone(new DateTimeZone($timezone));
        $offset = $dt->format('P');
        return $offset;
    } catch (Exception $e) {
        return '+00:00';
    }
}
