# Timezone Implementation Documentation

## Overview
Implemented automatic timezone detection based on user's IP address for all admin panel pages. Times are now displayed in the user's local timezone instead of server time.

## Features

### Automatic Timezone Detection
- Detects user timezone based on IP address using ip-api.com (free, no API key required)
- Caches results for 7 days to reduce API calls
- Falls back to America/New_York if detection fails
- Handles local IPs gracefully

### Session-Based Storage
- Once detected, timezone is stored in PHP session
- No repeated API calls during the same session
- Fast subsequent page loads

### Visual Indicators
All admin pages now show timezone information:
- Backup Management: Shows timezone abbreviation and offset in header
- System Logs: Shows timezone info in header
- Ticket Management: Shows timezone info below page title
- Individual timestamps show timezone abbreviation next to time

## Files Modified

### New Files
- httpdocs/timezone_utils.php - Core timezone utility functions
- httpdocs/cache/timezone_cache.json - IP-to-timezone cache (gitignored)

### Modified Files
- httpdocs/backup.php - Updated time display to use formatTimeInUserTZ()
- httpdocs/logs.php - Updated log timestamps to use formatDateTimeInUserTZ()
- httpdocs/tickets_admin.php - Updated ticket creation times
- httpdocs/admin.php - Replaced old timezone function
- .gitignore - Added httpdocs/cache/ to ignore timezone cache

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

- Location: httpdocs/cache/timezone_cache.json
- Expiry: 7 days (604800 seconds)
- Max Entries: 1000 (auto-purges oldest entries)
- Fallback: If cache is corrupted, recreates from scratch

## Security Considerations

- IP addresses are cached but not logged permanently
- No personal data stored (only IP to timezone mapping)
- API calls timeout after 2 seconds
- Graceful fallback if API is down
- Local IPs not sent to API
