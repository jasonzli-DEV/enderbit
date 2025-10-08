# Timezone Implementation Documentation

## Overview
Implemented automatic timezone detection based on user's IP address for all admin panel pages. Times are now displayed in the user's local timezone instead of server time.

## Features

### Automatic Timezone Detection
- Detects user timezone based on IP address using ip-api.com (free, no API key required)
- Caches results in cookies for 7 days to reduce API calls
- Falls back to America/New_York if detection fails
- Handles local IPs gracefully

### Multi-Layer Caching
- **Primary Cache**: PHP session (fast, per-session)
- **Secondary Cache**: HTTP-only cookies (persists across sessions, 7-day expiry)
- **Fallback**: API call if both caches miss
- No filesystem dependencies or file I/O overhead

### Visual Indicators
All admin pages now show timezone information:
- Backup Management: Shows timezone abbreviation and offset in header
- System Logs: Shows timezone info in header
- Ticket Management: Shows timezone info below page title
- Individual timestamps show timezone abbreviation next to time

## Files Modified

### New Files
- httpdocs/timezone_utils.php - Core timezone utility functions

### Modified Files
- httpdocs/backup.php - Updated time display to use formatTimeInUserTZ()
- httpdocs/logs.php - Updated log timestamps to use formatDateTimeInUserTZ()
- httpdocs/tickets_admin.php - Updated ticket creation times
- httpdocs/admin.php - Replaced old timezone function

## Functions Available

- getUserTimezone() - Returns user's detected timezone
- formatTimeInUserTZ($timestamp, $format) - Formats Unix timestamp in user's timezone
- formatDateTimeInUserTZ($datetime, $format) - Formats datetime string in user's timezone
- getTimezoneAbbr() - Returns timezone abbreviation (EST, PST, etc.)
- getTimezoneOffset() - Returns UTC offset (-05:00, +03:00, etc.)

## API Used

ip-api.com
- Free tier: 45 requests per minute
- No API key required
- Response time: ~50ms average

## Cache System

### Cookie-Based Cache
- **Cookie Name**: `tz_[8-char-hash]` (IP hashed with MD5 for privacy)
- **Cookie Data**: JSON with `{'tz': 'timezone', 'exp': timestamp}`
- **Expiry**: 7 days (604800 seconds)
- **Security**: HTTP-only flag prevents JavaScript access
- **Scope**: Domain-wide, travels with browser
- **Benefits**:
  - No file I/O overhead
  - Browser handles expiry automatically
  - No filesystem permissions needed
  - Per-user cache persists across sessions

### Cache Flow
1. Check `$_SESSION['timezone']` (primary cache)
2. Check cookie `tz_[hash]` (secondary cache)
3. Call ip-api.com API if both miss
4. Store result in both session and cookie

## Security Considerations

- IP addresses hashed in cookie names for privacy
- HTTP-only cookies prevent XSS attacks
- No personal data stored (only IP-to-timezone mapping)
- API calls timeout after 2 seconds
- Graceful fallback if API is down
- Local IPs not sent to API
- Cookies expire automatically after 7 days
