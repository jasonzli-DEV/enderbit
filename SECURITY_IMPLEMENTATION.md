# Security Enhancements - Implementation Guide

## Overview
EnderBit Hosting Panel now includes comprehensive security features to protect against common web vulnerabilities and attacks.

## Security Features Implemented

### 1. Password Hashing (✅ Implemented)
**What it does**: Securely stores admin passwords using Argon2ID hashing algorithm instead of plain text.

**Benefits**:
- Passwords cannot be recovered even if config.php is compromised
- Uses industry-standard Argon2ID algorithm (recommended by OWASP)
- Automatic rehashing when algorithm parameters improve

**How it works**:
- Old method: `'admin_password' => 'mypassword'` (plain text)
- New method: `'admin_password_hash' => '$argon2id$...'` (secure hash)
- System automatically falls back to plain password if hash doesn't exist yet

### 2. CSRF Protection (✅ Implemented)
**What it does**: Prevents Cross-Site Request Forgery attacks by validating that form submissions come from legitimate sources.

**Benefits**:
- Prevents attackers from tricking admins into performing unwanted actions
- Validates all POST requests with unique tokens
- Tokens are session-specific and expire with the session

**Protected Actions**:
- Admin login
- User approval/denial
- Settings changes
- Logout
- Dashboard customization
- All other POST forms

### 3. Rate Limiting (✅ Implemented)
**What it does**: Prevents brute force login attempts by limiting the number of failed login attempts.

**Configuration**:
- **Max Attempts**: 5 failed attempts
- **Time Window**: 5 minutes (300 seconds)
- **Tracking**: By IP address

**How it works**:
- After 5 failed login attempts from the same IP
- User must wait 5 minutes before trying again
- Successful login resets the counter
- Stored in `rate_limits.json`

### 4. Security Headers (✅ Implemented)
**What it does**: Adds HTTP security headers to prevent common attacks.

**Headers Added**:
- `X-Frame-Options: SAMEORIGIN` - Prevents clickjacking
- `X-Content-Type-Options: nosniff` - Prevents MIME sniffing
- `X-XSS-Protection: 1; mode=block` - Enables XSS protection
- `Referrer-Policy: strict-origin-when-cross-origin` - Controls referrer information
- `Content-Security-Policy` - Restricts resource loading

### 5. Input Sanitization (✅ Implemented)
**What it does**: Cleans and validates user input to prevent injection attacks.

**Sanitization Types**:
- **Email**: Validates and sanitizes email addresses
- **String**: Removes HTML tags and trims whitespace  
- **HTML**: Escapes special characters for safe display

**Applied To**:
- Email addresses in user management
- Denial reasons
- All user-provided text

### 6. Enhanced Logging (✅ Implemented)
**What it does**: Logs security events with detailed context including IP address and user agent.

**Events Logged**:
- Login successes and failures
- Rate limiting triggers
- CSRF validation failures
- Password migration events
- Plain text password detection

## Migration Guide

### Step 1: Update Your Installation

Pull the latest code:
```bash
cd /path/to/enderbit.com
git pull origin main
```

### Step 2: Migrate Your Admin Password

Run the migration script:
```bash
php httpdocs/migrate_password.php
```

**What this does**:
1. Reads your current `admin_password` from config.php
2. Generates a secure Argon2ID hash
3. Creates a backup of config.php
4. Adds `admin_password_hash` to config.php

**Output**:
```
EnderBit Password Migration Script
===================================

Current configuration:
  Plain password found: ********

Starting migration...
✓ Generated secure hash
✓ Created backup: config.php.backup.2025-10-13_123456
✓ Updated config.php with hashed password

✓ Migration complete!

Important notes:
1. Your old password still works
2. The system now uses the secure hash instead
3. You can optionally remove the 'admin_password' line from config.php
4. A backup was created: config.php.backup.2025-10-13_123456
```

### Step 3: Test Login

1. Navigate to `/admin.php`
2. Log in with your existing password
3. Verify successful login
4. Check `security.log` for successful authentication

### Step 4: Cleanup (Optional)

After verifying login works, you can:

1. **Remove plain password** from `config.php`:
   ```php
   // Before
   'admin_password' => 'mypassword',
   'admin_password_hash' => '$argon2id$...',
   
   // After (remove first line)
   'admin_password_hash' => '$argon2id$...',
   ```

2. **Delete backup file**:
   ```bash
   rm httpdocs/config.php.backup.*
   ```

## Configuration

### Rate Limiting

To adjust rate limiting settings, edit `admin.php` line ~93:

```php
$rateLimit = EnderBitSecurity::checkRateLimit($clientIP, 5, 300);
// Parameters: checkRateLimit($identifier, $maxAttempts, $timeWindowSeconds)
```

**Examples**:
- Stricter: `checkRateLimit($clientIP, 3, 600)` - 3 attempts per 10 minutes
- Relaxed: `checkRateLimit($clientIP, 10, 300)` - 10 attempts per 5 minutes

### HTTPS (Recommended)

If your site uses HTTPS, uncomment this line in `security.php` (line 176):

```php
// Uncomment if using HTTPS:
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
```

This enables HTTP Strict Transport Security (HSTS).

## File Structure

```
httpdocs/
├── security.php              # Security utilities class (NEW)
├── migrate_password.php      # Password migration script (NEW)
├── rate_limits.json          # Rate limiting data (AUTO-CREATED)
├── admin.php                 # Updated with security features
├── backup.php                # Updated with security headers
├── logs.php                  # Updated with security headers
├── tickets.php               # Updated with security headers
├── update.php                # Updated with security headers
└── users_admin.php           # Updated with CSRF protection
```

## API Reference

### EnderBitSecurity Class

#### Password Functions

```php
// Hash a password
$hash = EnderBitSecurity::hashPassword($plainPassword);

// Verify a password
$isValid = EnderBitSecurity::verifyPassword($plainPassword, $hash);

// Check if rehash needed
$needsUpdate = EnderBitSecurity::needsRehash($hash);
```

#### CSRF Functions

```php
// Generate token
$token = EnderBitSecurity::generateCSRFToken();

// Validate token
$isValid = EnderBitSecurity::validateCSRFToken($_POST['csrf_token']);

// Output hidden field
echo EnderBitSecurity::csrfField();
```

#### Rate Limiting

```php
// Check rate limit
$result = EnderBitSecurity::checkRateLimit($identifier, $maxAttempts, $timeWindow);
// Returns: ['allowed' => bool, 'attempts' => int, 'remaining' => int, 'reset_time' => int, 'wait_seconds' => int]

// Reset rate limit (on successful login)
EnderBitSecurity::resetRateLimit($identifier);
```

#### Sanitization

```php
// Sanitize email
$clean = EnderBitSecurity::sanitizeInput($email, 'email');

// Sanitize string
$clean = EnderBitSecurity::sanitizeInput($text, 'string');

// Sanitize HTML
$clean = EnderBitSecurity::sanitizeInput($html, 'html');

// Validate email
$isValid = EnderBitSecurity::validateEmail($email);
```

#### Utilities

```php
// Get client IP (proxy-aware)
$ip = EnderBitSecurity::getClientIP();

// Generate secure token
$token = EnderBitSecurity::generateToken(32);

// Set security headers
EnderBitSecurity::setSecurityHeaders();

// Log security event
EnderBitSecurity::logSecurityEvent($event, $severity, $context);
```

## Security Log Examples

### Successful Login
```json
{
  "timestamp": "2025-10-13 14:23:45",
  "event": "ADMIN_LOGIN_SUCCESS",
  "severity": "LOW",
  "context": {
    "admin": true,
    "remember_me": true,
    "ip": "192.168.1.100",
    "user_agent": "Mozilla/5.0..."
  }
}
```

### Rate Limited
```json
{
  "timestamp": "2025-10-13 14:25:10",
  "event": "ADMIN_LOGIN_RATE_LIMITED",
  "severity": "HIGH",
  "context": {
    "admin": true,
    "attempts": 5,
    "wait_seconds": 240,
    "ip": "192.168.1.100",
    "user_agent": "Mozilla/5.0..."
  }
}
```

### CSRF Failed
```json
{
  "timestamp": "2025-10-13 14:30:22",
  "event": "CSRF_VALIDATION_FAILED",
  "severity": "MEDIUM",
  "context": {
    "action": "approve_user",
    "ip": "192.168.1.100",
    "user_agent": "Mozilla/5.0..."
  }
}
```

## Troubleshooting

### "Security validation failed" Error

**Cause**: CSRF token validation failed  
**Solution**: 
1. Check if sessions are working properly
2. Clear browser cache and cookies
3. Ensure forms include `<?= EnderBitSecurity::csrfField() ?>`

### "Too many login attempts" Message

**Cause**: Rate limiting triggered  
**Solution**:
1. Wait 5 minutes and try again
2. Check `rate_limits.json` to see tracking data
3. To manually reset, delete the entry from `rate_limits.json`

### Password Migration Failed

**Cause**: Script couldn't automatically update config.php  
**Solution**:
1. Run the migration script to get the hash
2. Manually add this line to `config.php`:
   ```php
   'admin_password_hash' => 'THE_GENERATED_HASH',
   ```

### Can't Login After Migration

**Cause**: Hash might not be configured correctly  
**Solution**:
1. Check config.php has `admin_password_hash` entry
2. System falls back to plain password if hash missing
3. Check `security.log` for error details
4. Restore from backup: `cp config.php.backup.* config.php`

## Security Best Practices

### 1. Regular Updates
- Keep PHP updated (7.4+ required, 8.0+ recommended)
- Pull latest code from GitHub regularly
- Monitor security.log for suspicious activity

### 2. HTTPS
- Use HTTPS in production (Let's Encrypt is free)
- Uncomment HSTS header in security.php
- Update CSP header if using CDN resources

### 3. File Permissions
```bash
chmod 755 httpdocs/
chmod 644 httpdocs/*.php
chmod 666 httpdocs/*.json
chmod 600 httpdocs/config.php  # Most restrictive for config
```

### 4. Monitoring
- Review `security.log` regularly
- Set up alerts for HIGH severity events
- Monitor rate_limits.json for patterns

### 5. Backups
- Keep config.php backups in a secure location
- Include rate_limits.json in backup routine
- Test restoration process regularly

## Future Enhancements

Planned security features:
- [ ] Two-factor authentication (2FA/TOTP)
- [ ] Session hijacking prevention (token binding)
- [ ] IP whitelist for admin access
- [ ] Audit log with detailed user actions
- [ ] Automated security scanning
- [ ] Password complexity requirements
- [ ] Account lockout after multiple failures

## Support

For security issues or questions:
- Email: security@enderbit.com
- GitHub Issues: Use `[SECURITY]` tag
- For vulnerabilities: security@enderbit.com (private disclosure)

---

**Last Updated**: October 13, 2025  
**Version**: 1.0.0  
**Security Level**: Enhanced ✅
