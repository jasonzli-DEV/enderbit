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

### Step 1: Generate a Secret Key

1. Open `httpdocs/run_backup.php`
2. Find this line:
   ```php
   $BACKUP_SECRET_KEY = 'CHANGE_THIS_TO_A_RANDOM_STRING';
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

**Features:**
- ✅ Free tier: 50 executions/month
- ✅ Advanced scheduling options
- ✅ HTTP status monitoring
- ✅ Email notifications

---

### Option 3: cron-job.com
**Best for:** Simple setup, no registration needed

**Setup:**
1. Go to https://cron-job.com/
2. Sign up for free account
3. Add new cron job
4. **URL:** `https://yourdomain.com/run_backup.php?key=YOUR_SECRET_KEY`
5. **Schedule:** Choose interval from dropdown
6. Save
7. Done! ✅

---

### Option 4: UptimeRobot (As Cron Alternative)
**Best for:** Already using UptimeRobot for monitoring

**Setup:**
1. Go to https://uptimerobot.com/
2. Sign up for free account
3. Click "Add New Monitor"
4. **Monitor Type:** HTTP(s)
5. **URL:** `https://yourdomain.com/run_backup.php?key=YOUR_SECRET_KEY`
6. **Monitoring Interval:** 
   - Hourly: 60 minutes (closest to hourly)
   - Daily: Use cron-job.org instead
7. Save
8. Done! ✅

**Note:** UptimeRobot checks at fixed intervals, not exact times. Better for hourly backups than daily.

---

## 📱 Mobile Option: IFTTT

**Best for:** Triggering from phone/automation

**Setup:**
1. Download IFTTT app
2. Create new applet
3. **IF:** Date & Time (every day at 2 AM)
4. **THEN:** Webhooks
5. **URL:** `https://yourdomain.com/run_backup.php?key=YOUR_SECRET_KEY`
6. **Method:** GET
7. Save
8. Done! ✅

---

## 🔍 Verification

### Check if Backups are Running

1. **Check backup files:**
   - Go to admin panel → Backup Management
   - Look for recent backup files
   - Check timestamps

2. **Check logs:**
   - Admin panel → Logs → System Log
   - Look for `STANDALONE_BACKUP_TRIGGERED` events
   - Check timestamps match your schedule

3. **Check cron service:**
   - Log into your cron service dashboard
   - View execution history
   - Check for successful runs

### Example Log Entry
```json
{
  "timestamp": "2025-10-14 14:00:00",
  "event": "STANDALONE_BACKUP_TRIGGERED",
  "context": {
    "ip": "192.0.2.1",
    "user_agent": "cron-job.org"
  }
}
```

---

## ⚙️ Scheduling Guide

### Recommended Schedules

**For Small Sites (< 100 users):**
- **Frequency:** Daily
- **Time:** 2:00 AM (low traffic)
- **Cron:** `0 2 * * *`

**For Medium Sites (100-1000 users):**
- **Frequency:** Every 12 hours
- **Times:** 2:00 AM and 2:00 PM
- **Cron:** `0 2,14 * * *`

**For Active Sites (1000+ users):**
- **Frequency:** Every 6 hours
- **Times:** 2:00 AM, 8:00 AM, 2:00 PM, 8:00 PM
- **Cron:** `0 2,8,14,20 * * *`

**For Development/Testing:**
- **Frequency:** Hourly
- **Cron:** `0 * * * *`

### Cron Expression Reference

```
 ┌───────────── minute (0-59)
 │ ┌───────────── hour (0-23)
 │ │ ┌───────────── day of month (1-31)
 │ │ │ ┌───────────── month (1-12)
 │ │ │ │ ┌───────────── day of week (0-6, Sunday=0)
 │ │ │ │ │
 * * * * *
```

**Examples:**
- `0 * * * *` - Every hour at minute 0
- `0 2 * * *` - Every day at 2:00 AM
- `0 2 * * 0` - Every Sunday at 2:00 AM
- `*/15 * * * *` - Every 15 minutes
- `0 */6 * * *` - Every 6 hours

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

### 3. Monitor Access
- Check `system.log` for unauthorized attempts
- Look for unusual IP addresses
- Set up alerts for failed attempts

### 4. Rotate Keys Periodically
- Change key every 3-6 months
- Change immediately if exposed
- Update cron service after changing

---

## 🛠️ Troubleshooting

### "Unauthorized" Error
**Problem:** `{"error": "Unauthorized", "message": "Invalid or missing key"}`

**Solutions:**
1. Check URL has `?key=YOUR_KEY` at the end
2. Verify key matches exactly in `run_backup.php`
3. Check for typos or extra spaces
4. Key is case-sensitive

### Backups Not Running
**Problem:** No new backup files appearing

**Check:**
1. Is backup schedule enabled in admin panel?
2. Is cron service actually running? (check service dashboard)
3. Check `system.log` for errors
4. Test URL manually in browser
5. Check if enough disk space

### "500 Internal Server Error"
**Problem:** Server error when accessing URL

**Solutions:**
1. Check PHP error log
2. Verify all required files exist
3. Check file permissions (755 for directories, 644 for PHP files)
4. Test `background_tasks.php` works

### Cron Service Says "Failed"
**Problem:** External service shows failed execution

**Check:**
1. Is your website online?
2. Test URL in browser
3. Check firewall isn't blocking the service
4. Verify SSL certificate is valid
5. Check server isn't rate-limiting requests

---

## 📊 Monitoring

### Setup Email Alerts

Most cron services offer email alerts for failures:

**cron-job.org:**
1. Go to Settings
2. Enable "Send email on failure"
3. Enter your email
4. Save

**EasyCron:**
1. Edit cron job
2. Check "Alert on Failure"
3. Enter email address
4. Save

### Dashboard Monitoring

Check your admin panel regularly:
- Last backup timestamp
- Backup file count
- Log entries for backup events

---

## 💡 Advanced Setup

### Multiple Backup Frequencies

Run different backups at different times:

**Full Backup (Weekly):**
```
URL: https://yourdomain.com/run_backup.php?key=YOUR_KEY&type=full
Schedule: 0 2 * * 0 (Sunday 2 AM)
```

**Quick Backup (Daily):**
```
URL: https://yourdomain.com/run_backup.php?key=YOUR_KEY&type=quick
Schedule: 0 2 * * * (Daily 2 AM)
```

**Note:** You'll need to modify `run_backup.php` to handle `type` parameter.

### Backup Status Webhook

Get notified when backups complete:

1. Use Zapier/IFTTT webhook
2. Configure cron service to POST results
3. Receive Slack/Discord/Email notifications

---

## ✅ Final Checklist

Before you're done, verify:

- [ ] Secret key is changed from default
- [ ] Secret key is strong (30+ random characters)
- [ ] Tested backup URL in browser (works)
- [ ] Cron service is configured
- [ ] Schedule matches your needs
- [ ] First backup has run successfully
- [ ] Backup files appear in admin panel
- [ ] Log entries show successful runs
- [ ] Email alerts are configured (optional)
- [ ] Documentation is saved for future reference

---

## 🆘 Need Help?

**Issues with setup?**
- Check `system.log` in admin panel
- Test URL manually first
- Verify key is correct
- Check cron service documentation

**Still stuck?**
- Check GitHub Issues
- Contact support
- Review AUTOMATIC_FEATURES.md

---

## 📚 Related Documentation

- `AUTOMATIC_FEATURES.md` - All automatic features
- `SECURITY_IMPLEMENTATION.md` - Security details
- `README.md` - General setup

---

**You're all set! Backups will now run automatically without any action required! 🎉**
