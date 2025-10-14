<?php
/**
 * Time Utilities
 * 
 * Provides reliable time functions with fallback to free external time APIs
 * if server time is unreliable.
 */

class EnderBitTime {
    
    /**
     * Get current timestamp
     * Uses server time by default, falls back to WorldTimeAPI if needed
     * 
     * @param bool $useExternal Force using external API
     * @return int Unix timestamp
     */
    public static function now($useExternal = false) {
        if (!$useExternal) {
            return time();
        }
        
        return self::getExternalTime();
    }
    
    /**
     * Get current time from free external API (WorldTimeAPI)
     * 
     * @param string $timezone Optional timezone (e.g., 'America/New_York')
     * @return int Unix timestamp
     */
    public static function getExternalTime($timezone = null) {
        // Use WorldTimeAPI - completely free, no API key needed
        $url = $timezone 
            ? "https://worldtimeapi.org/api/timezone/{$timezone}"
            : "https://worldtimeapi.org/api/ip"; // Detect timezone from IP
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($ch, CURLOPT_USERAGENT, 'EnderBit-TimeSync/1.0');
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200 && $response) {
            $data = json_decode($response, true);
            if (isset($data['unixtime'])) {
                return (int)$data['unixtime'];
            }
        }
        
        // Fallback to server time if API fails
        return time();
    }
    
    /**
     * Get current time in specific timezone
     * 
     * @param string $timezone Timezone (e.g., 'America/New_York')
     * @return array ['timestamp' => int, 'datetime' => string, 'timezone' => string]
     */
    public static function getTimeInTimezone($timezone) {
        $url = "https://worldtimeapi.org/api/timezone/{$timezone}";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($ch, CURLOPT_USERAGENT, 'EnderBit-TimeSync/1.0');
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200 && $response) {
            $data = json_decode($response, true);
            return [
                'timestamp' => (int)($data['unixtime'] ?? time()),
                'datetime' => $data['datetime'] ?? date('c'),
                'timezone' => $data['timezone'] ?? $timezone,
                'utc_offset' => $data['utc_offset'] ?? '+00:00',
                'day_of_week' => $data['day_of_week'] ?? date('w'),
                'week_number' => $data['week_number'] ?? date('W')
            ];
        }
        
        // Fallback to server time
        return [
            'timestamp' => time(),
            'datetime' => date('c'),
            'timezone' => $timezone,
            'utc_offset' => date('P'),
            'day_of_week' => (int)date('w'),
            'week_number' => (int)date('W')
        ];
    }
    
    /**
     * Get list of available timezones from API
     * 
     * @return array List of timezone strings
     */
    public static function getAvailableTimezones() {
        $url = "https://worldtimeapi.org/api/timezone";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($ch, CURLOPT_USERAGENT, 'EnderBit-TimeSync/1.0');
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200 && $response) {
            $timezones = json_decode($response, true);
            if (is_array($timezones)) {
                return $timezones;
            }
        }
        
        // Fallback to PHP's timezone list
        return timezone_identifiers_list();
    }
    
    /**
     * Compare server time vs external time (for debugging)
     * 
     * @return array ['server' => int, 'external' => int, 'diff' => int, 'synced' => bool]
     */
    public static function checkTimeSync() {
        $serverTime = time();
        $externalTime = self::getExternalTime();
        $diff = abs($serverTime - $externalTime);
        
        return [
            'server' => $serverTime,
            'external' => $externalTime,
            'diff' => $diff,
            'synced' => $diff < 5, // Within 5 seconds is considered synced
            'message' => $diff < 5 
                ? 'Server time is synchronized' 
                : "Server time is off by {$diff} seconds"
        ];
    }
    
    /**
     * Format timestamp to human readable string
     * 
     * @param int $timestamp Unix timestamp
     * @param string $format Date format (default: 'Y-m-d H:i:s')
     * @return string Formatted date string
     */
    public static function format($timestamp, $format = 'Y-m-d H:i:s') {
        return date($format, $timestamp);
    }
    
    /**
     * Get time ago string (e.g., "5 minutes ago")
     * 
     * @param int $timestamp Unix timestamp
     * @return string Time ago string
     */
    public static function timeAgo($timestamp) {
        $now = time();
        $diff = $now - $timestamp;
        
        if ($diff < 60) {
            return $diff . ' second' . ($diff != 1 ? 's' : '') . ' ago';
        }
        
        $diff = floor($diff / 60);
        if ($diff < 60) {
            return $diff . ' minute' . ($diff != 1 ? 's' : '') . ' ago';
        }
        
        $diff = floor($diff / 60);
        if ($diff < 24) {
            return $diff . ' hour' . ($diff != 1 ? 's' : '') . ' ago';
        }
        
        $diff = floor($diff / 24);
        if ($diff < 30) {
            return $diff . ' day' . ($diff != 1 ? 's' : '') . ' ago';
        }
        
        $diff = floor($diff / 30);
        if ($diff < 12) {
            return $diff . ' month' . ($diff != 1 ? 's' : '') . ' ago';
        }
        
        $diff = floor($diff / 12);
        return $diff . ' year' . ($diff != 1 ? 's' : '') . ' ago';
    }
}
