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

// Get timezone from IP address using ipapi.co (free, no API key required)
function getTimezoneFromIP($ip) {
    if (!$ip) {
        return 'America/New_York'; // Default fallback
    }
    
    // Try to get from cache file first
    $cacheFile = __DIR__ . '/cache/timezone_cache.json';
    $cache = [];
    
    if (file_exists($cacheFile)) {
        $cache = json_decode(file_get_contents($cacheFile), true) ?? [];
        
        // Check if we have a cached result for this IP (valid for 7 days)
        if (isset($cache[$ip]) && (time() - $cache[$ip]['time']) < 604800) {
            return $cache[$ip]['timezone'];
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
                
                // Cache the result
                $cache[$ip] = [
                    'timezone' => $timezone,
                    'time' => time()
                ];
                
                // Ensure cache directory exists
                $cacheDir = dirname($cacheFile);
                if (!is_dir($cacheDir)) {
                    mkdir($cacheDir, 0755, true);
                }
                
                // Save cache (limit to 1000 entries)
                if (count($cache) > 1000) {
                    // Remove oldest entries
                    uasort($cache, function($a, $b) {
                        return $a['time'] - $b['time'];
                    });
                    $cache = array_slice($cache, -1000, null, true);
                }
                
                file_put_contents($cacheFile, json_encode($cache, JSON_PRETTY_PRINT));
                
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
