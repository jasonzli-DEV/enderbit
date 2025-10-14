# No-Action-Required Backup Setup Guide

This guide shows you how to set up automatic backups that run **without requiring any admin logins or page visits**.

## 🎯 Goal

Backups should run automatically on schedule (hourly/daily/weekly) even if:
- No one visits the website
- Admin doesn't log in
- Server is idle

## 🔧 Solution: External Cron Services

Use free external services to ping your backup URL on a schedule. These services work from anywhere and don't require server access.

---

## 📋 Setup Instructions

### Step 0: Create the Backup Runner File

1. Go to `httpdocs/` directory
2. Copy `run_backup.php.template` to `run_backup.php`:
   ```bash
   cp httpdocs/run_backup.php.template httpdocs/run_backup.php
   ```
   Or just duplicate the file and rename it.

### Step 1: Generate a Secret Key

1. Open `httpdocs/run_backup.php`
2. Find this line:
   ```php
   $BACKUP_SECRET_KEY = 'CHANGE_THIS_TO_A_RANDOM_STRING_30_PLUS_CHARACTERS';
   ```
3. Change it to a random string (30+ characters recommended):
   ```php
   $BACKUP_SECRET_KEY = 'k9mP2xL5qR8wN3jT6vH4yC7bF1sA0dE9gZ';
   ```

**Generate a random key:**
- Website: https://www.random.org/strings/
- Or use password generator
- Or mash keyboard randomly 😄

### Step 2: Test the Backup URL

Visit this URL in your browser (replace with your key):
```
https://yourdomain.com/run_backup.php?key=YOUR_SECRET_KEY
```

**You should see:**
```json
{
    "success": true,
    "message": "Backup check completed",
    "schedule": {
        "enabled": true,
        "frequency": "daily",
        "last_run": 1697294400,
        "last_run_human": "2025-10-14 12:00:00"
    },
    "timestamp": "2025-10-14 12:05:30"
}
```

### Step 3: Choose an External Cron Service

Pick one of these **FREE** services:

---

## 🌐 Recommended Services

### Option 1: cron-job.org (Recommended)
**Best for:** Most users, very reliable, completely free

**Setup:**
1. Go to https://cron-job.org/
2. Sign up for free account
3. Click "Create Cronjob"
4. **URL:** `https://yourdomain.com/run_backup.php?key=YOUR_SECRET_KEY`
5. **Schedule:** 
   - Hourly: `0 * * * *` (every hour at :00)
   - Daily: `0 2 * * *` (every day at 2 AM)
   - Weekly: `0 2 * * 0` (every Sunday at 2 AM)
6. **Title:** "EnderBit Backups"
7. Click "Create Cronjob"
8. Done! ✅

**Features:**
- ✅ 100% Free forever
- ✅ No credit card required
- ✅ Runs from multiple locations
- ✅ Email alerts on failures
- ✅ Execution history logs

---

### Option 2: EasyCron
**Best for:** More control, advanced scheduling

**Setup:**
1. Go to https://www.easycron.com/
2. Sign up for free account (50 tasks/month free)
3. Click "Add Cron Job"
4. **URL:** `https://yourdomain.com/run_backup.php?key=YOUR_SECRET_KEY`
5. **Cron Expression:**
   - Hourly: `0 * * * *`
   - Daily: `0 2 * * *`
   - Weekly: `0 2 * * 0`
6. **Name:** "EnderBit Backups"
7. Click "Create Cron Job"
8. Done! ✅

---

## 🛠️ Troubleshooting

### "Unauthorized" Error
**Problem:** `{"error": "Unauthorized", "message": "Invalid or missing key"}`

**Solutions:**
1. Check URL has `?key=YOUR_KEY` at the end
2. Verify key matches exactly in `run_backup.php`
3. Check for typos or extra spaces
4. Key is case-sensitive

### 500 Internal Server Error
**Problem:** Server error when accessing URL

**Solutions:**
1. Make sure `run_backup.php` exists (not just the template)
2. Check it has `require_once __DIR__ . '/config.php';` at the top
3. Verify `config.php`, `background_tasks.php`, and `logger.php` exist
4. Check file permissions (644 for PHP files)
5. Look at PHP error log for details

### 301 Redirect Error
**Problem:** Cron service shows 301 redirect

**Solutions:**
1. Use `https://` instead of `http://` if you have SSL
2. Add or remove `www.` from domain (test both)
3. Make sure URL doesn't have trailing slash
4. Check `.htaccess` for redirect rules

**Examples:**
- ❌ `http://example.com/run_backup.php?key=abc` (if you have HTTPS)
- ✅ `https://example.com/run_backup.php?key=abc`
- ❌ `https://example.com/run_backup.php/?key=abc` (extra slash)
- ✅ `https://example.com/run_backup.php?key=abc`

### Backups Not Running
**Problem:** No new backup files appearing

**Check:**
1. Is backup schedule enabled in admin panel?
2. Is cron service actually running? (check service dashboard)
3. Check `system.log` for errors
4. Test URL manually in browser
5. Check if enough disk space

---

## 📊 Monitoring

### Setup Email Alerts

Most cron services offer email alerts for failures:

**cron-job.org:**
1. Go to Settings
2. Enable "Send email on failure"
3. Enter your email
4. Save

### Dashboard Monitoring

Check your admin panel regularly:
- Last backup timestamp
- Backup file count
- Log entries for backup events

---

## 🔐 Security Best Practices

### 1. Keep Your Key Secret
- ❌ Don't share it in public repos
- ❌ Don't include in screenshots
- ✅ Treat it like a password
- ✅ Change it if compromised

### 2. Use Strong Keys
- ❌ Weak: `backup123`
- ❌ Weak: `mysite_backup`
- ✅ Strong: `k9mP2xL5qR8wN3jT6vH4yC7bF1sA0dE9gZ`
- ✅ Strong: `7B!x9@mQ2#nK5$pL8^tR3&vC6*wF1-hD4`

---

## ✅ Final Checklist

Before you're done, verify:

- [ ] Created `run_backup.php` from template
- [ ] Secret key is changed from default
- [ ] Secret key is strong (30+ random characters)
- [ ] Tested backup URL in browser (works)
- [ ] Backups enabled in admin panel
- [ ] Cron service is configured
- [ ] Schedule matches your needs
- [ ] First backup has run successfully
- [ ] Log entries show successful runs

---

**You're all set! Backups will now run automatically without any action required! 🎉**
