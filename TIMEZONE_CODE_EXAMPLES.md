# Code Examples - Timezone Implementation

## How to Use in Your Code

### 1. Include the Utility File
```php
require_once __DIR__ . '/timezone_utils.php';
```

### 2. Format a Unix Timestamp
```php
// Before
$timestamp = time();
echo date('M j, Y g:i A', $timestamp);
// Output: Oct 8, 2025 6:30 PM (server time)

// After
$timestamp = time();
echo formatTimeInUserTZ($timestamp, 'M j, Y g:i A');
// Output: Oct 8, 2025 11:30 AM (user's timezone)
```

### 3. Format a DateTime String
```php
// Before
$datetime = '2025-10-08 18:30:00';
echo date('M j, Y g:i A', strtotime($datetime));
// Output: Oct 8, 2025 6:30 PM (server time)

// After
$datetime = '2025-10-08 18:30:00';
echo formatDateTimeInUserTZ($datetime, 'M j, Y g:i A');
// Output: Oct 8, 2025 11:30 AM (user's timezone)
```

### 4. Show Timezone Abbreviation
```php
echo getTimezoneAbbr(); // Output: PST, EST, GMT, etc.
```

### 5. Show Timezone Offset
```php
echo getTimezoneOffset(); // Output: -08:00, +05:00, etc.
```

### 6. Show Full Timestamp with Timezone
```php
echo formatTimeInUserTZ(time(), 'M j, Y g:i A') . ' (' . getTimezoneAbbr() . ')';
// Output: Oct 8, 2025 11:30 AM (PST)
```

## Real Examples from Project

### backup.php - Backup Cards
```php
// Before
<div class="backup-time">
  <?= date('g:i A', $set['created']) ?>
</div>
<div class="backup-date">
  <?= date('l, F j, Y', $set['created']) ?>
</div>

// After
<div class="backup-time">
  <?= formatTimeInUserTZ($set['created'], 'g:i A') ?>
</div>
<div class="backup-date">
  <?= formatTimeInUserTZ($set['created'], 'l, F j, Y') ?>
</div>
```

### logs.php - Log Entries
```php
// Before
<span class="log-timestamp">
  <?= htmlspecialchars($entry['timestamp']) ?>
</span>

// After
<span class="log-timestamp">
  <?= htmlspecialchars(formatDateTimeInUserTZ($entry['timestamp'], 'M j, Y g:i:s A')) ?>
  <span style="font-size:10px;color:var(--text-secondary);">
    (<?= getTimezoneAbbr() ?>)
  </span>
</span>
```

### tickets_admin.php - Ticket Creation Time
```php
// Before
Created: <?= htmlspecialchars(format_user_time($ticket['created_at'], $ticket['user_timezone'] ?? 'America/New_York')) ?>

// After
Created: <?= htmlspecialchars(formatDateTimeInUserTZ($ticket['created_at'], 'M j, Y, g:i A')) ?>
<span style="color:var(--text-secondary);font-size:11px;">
  (<?= getTimezoneAbbr() ?>)
</span>
```

### Header Timezone Indicator
```php
// Backup Management / System Logs
<span style="font-size:13px;color:var(--text-secondary);padding:6px 12px;background:var(--input-bg);border-radius:6px;">
  🌍 Your timezone: <?= getTimezoneAbbr() ?> (<?= getTimezoneOffset() ?>)
</span>
```

## Available Date Formats

### Common Formats
```php
// Time only
formatTimeInUserTZ($timestamp, 'g:i A')           // 2:30 PM
formatTimeInUserTZ($timestamp, 'H:i:s')           // 14:30:00
formatTimeInUserTZ($timestamp, 'g:i:s A')         // 2:30:45 PM

// Date only
formatTimeInUserTZ($timestamp, 'M j, Y')          // Oct 8, 2025
formatTimeInUserTZ($timestamp, 'l, F j, Y')       // Tuesday, October 8, 2025
formatTimeInUserTZ($timestamp, 'Y-m-d')           // 2025-10-08

// Date + Time
formatTimeInUserTZ($timestamp, 'M j, Y g:i A')    // Oct 8, 2025 2:30 PM
formatTimeInUserTZ($timestamp, 'Y-m-d H:i:s')     // 2025-10-08 14:30:45
formatTimeInUserTZ($timestamp, 'D, M j, g:i A')   // Tue, Oct 8, 2:30 PM

// Full format
formatTimeInUserTZ($timestamp, 'l, F j, Y g:i:s A')  // Tuesday, October 8, 2025 2:30:45 PM
```

### PHP Date Format Characters
```
d - Day of month (01-31)
D - Day name short (Mon, Tue)
l - Day name full (Monday, Tuesday)
j - Day of month no leading zero (1-31)

m - Month number (01-12)
M - Month name short (Jan, Feb)
F - Month name full (January, February)
n - Month number no leading zero (1-12)

Y - Year 4 digits (2025)
y - Year 2 digits (25)

g - Hour 12-hour no leading zero (1-12)
G - Hour 24-hour no leading zero (0-23)
h - Hour 12-hour (01-12)
H - Hour 24-hour (00-23)

i - Minutes (00-59)
s - Seconds (00-59)

A - AM/PM uppercase
a - am/pm lowercase

T - Timezone abbreviation (EST, PST)
P - Timezone offset (+02:00, -05:00)
```

## Testing Scenarios

### Test 1: User in Different Timezone
```php
// Simulate Tokyo timezone
$_SESSION['user_timezone'] = 'Asia/Tokyo';

$timestamp = 1696780800; // Oct 8, 2025 18:00:00 UTC
echo formatTimeInUserTZ($timestamp, 'M j, Y g:i A');
// Output: Oct 9, 2025 3:00 AM (next day!)
```

### Test 2: Daylight Saving Time
```php
// Summer time (PDT)
$_SESSION['user_timezone'] = 'America/Los_Angeles';
$summer = strtotime('2025-07-15 12:00:00 UTC');
echo formatTimeInUserTZ($summer, 'g:i A T');
// Output: 5:00 AM PDT

// Winter time (PST)
$winter = strtotime('2025-12-15 12:00:00 UTC');
echo formatTimeInUserTZ($winter, 'g:i A T');
// Output: 4:00 AM PST
```

### Test 3: Cache Hit vs Miss
```php
// First request (cookie/session miss)
$start = microtime(true);
$tz1 = getUserTimezone();
$time1 = microtime(true) - $start;
echo "First call: {$time1}s (API call + cookie set)\n";

// Second request same session (session hit)
$start = microtime(true);
$tz2 = getUserTimezone();
$time2 = microtime(true) - $start;
echo "Second call: {$time2}s (from session)\n";

// Third request new session (cookie hit)
unset($_SESSION['user_timezone']); // simulate new session
$start = microtime(true);
$tz3 = getUserTimezone();
$time3 = microtime(true) - $start;
echo "Third call: {$time3}s (from cookie)\n";

// Results:
// First call: 0.052s (API call + cookie set)
// Second call: 0.00001s (from session)
// Third call: 0.00002s (from cookie, no API call)
```

## Error Handling

### Invalid Timezone
```php
// If API fails or returns invalid timezone
try {
    $formatted = formatTimeInUserTZ($timestamp, 'M j, Y g:i A');
} catch (Exception $e) {
    // Automatically falls back to server time
    $formatted = date('M j, Y g:i A', $timestamp);
}
```

### Network Timeout
```php
// IP-API call has 2-second timeout
// If timeout occurs, falls back to 'America/New_York'
$timezone = getUserTimezone();
// Returns: 'America/New_York' (fallback)
```

### Cache Expiry
```php
// Cookies automatically expire after 7 days
// Browser handles cleanup automatically
// No manual cache management needed
```

## Performance Optimization Tips

### 1. Batch Operations
```php
// Get timezone once at start
$timezone = getUserTimezone();

// Then use directly (if needed for custom logic)
foreach ($timestamps as $ts) {
    $dt = new DateTime();
    $dt->setTimestamp($ts);
    $dt->setTimezone(new DateTimeZone($timezone));
    echo $dt->format('M j, Y g:i A') . "\n";
}
```

### 2. Cookie Persistence
```php
// Timezone stored in cookies (7-day expiry)
// Persists across sessions automatically
// No file I/O overhead
// Browser handles cache management
```

### 3. Session-First Strategy
```php
// getUserTimezone() checks in order:
// 1. Session (fastest)
// 2. Cookie (very fast)
// 3. API call (only if both miss)
// No configuration needed - automatic
```

## Integration with Other Pages

### Adding to New Admin Pages
```php
<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/timezone_utils.php';  // Add this line

// Then use anywhere in the page
echo formatTimeInUserTZ(time(), 'M j, Y g:i A');
```

### User-Facing Pages (Optional)
```php
// Currently only on admin pages
// To add to user pages:
require_once __DIR__ . '/timezone_utils.php';

// Display ticket creation time
echo "Ticket created: " . formatDateTimeInUserTZ($ticket['created_at'], 'M j, Y g:i A');
echo " (" . getTimezoneAbbr() . ")";
```

## Debugging

### Check Current Timezone
```php
// Add to any admin page temporarily
echo "Detected timezone: " . getUserTimezone() . "<br>";
echo "Timezone abbreviation: " . getTimezoneAbbr() . "<br>";
echo "Timezone offset: " . getTimezoneOffset() . "<br>";
echo "User IP: " . getUserIP() . "<br>";
```

### Force Timezone Detection
```php
// Clear session to force cookie check
unset($_SESSION['user_timezone']);

// Clear cookies to force API call (in browser console)
document.cookie.split(';').forEach(c => {
  if(c.trim().startsWith('tz_')) {
    document.cookie = c.split('=')[0] + '=;expires=Thu, 01 Jan 1970 00:00:00 UTC;path=/';
  }
});
```

### View Cookie Contents
```php
// View timezone cookies
foreach ($_COOKIE as $name => $value) {
    if (strpos($name, 'tz_') === 0) {
        echo "$name: ";
        print_r(json_decode($value, true));
        echo "<br>";
    }
}
// Output: tz_a1b2c3d4: Array ( [tz] => America/Los_Angeles [exp] => 1733612400 )
```

## Migration Notes

If you have existing datetime display code:

### Pattern 1: Direct date() calls
```php
// Search for:
date('format', $timestamp)

// Replace with:
formatTimeInUserTZ($timestamp, 'format')
```

### Pattern 2: DateTime objects
```php
// Old way:
$dt = new DateTime($datetime);
echo $dt->format('M j, Y g:i A');

// New way:
echo formatDateTimeInUserTZ($datetime, 'M j, Y g:i A');
```

### Pattern 3: strtotime() conversions
```php
// Old way:
echo date('M j, Y g:i A', strtotime($datetime));

// New way:
echo formatDateTimeInUserTZ($datetime, 'M j, Y g:i A');
```
