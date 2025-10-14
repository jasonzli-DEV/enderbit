# Quick Setup: External Backups

## 🚀 5-Minute Setup

### 1. Create run_backup.php
Copy the template:
```bash
cp httpdocs/run_backup.php.template httpdocs/run_backup.php
```

### 2. Set Your Secret Key
Edit `httpdocs/run_backup.php` and change:
```php
$BACKUP_SECRET_KEY = 'CHANGE_THIS_TO_A_RANDOM_STRING_30_PLUS_CHARACTERS';
```
To something random like:
```php
$BACKUP_SECRET_KEY = 'xK9mP2nL5qR8wN3jT6vH4yC7bF1sA0dE9gZ2pM4r';
```

### 3. Enable Backups
1. Log in to admin panel
2. Go to **Backup Management**
3. Check "Enable Scheduled Backups"
4. Choose frequency (Daily recommended)
5. Click "Save Schedule Settings"

### 4. Test It
Visit this URL in your browser:
```
https://yoursite.com/run_backup.php?key=YOUR_SECRET_KEY
```

You should see:
```json
{
    "success": true,
    "message": "Backup check completed",
    ...
}
```

### 5. Set Up Free Cron Service
1. Go to https://cron-job.org/
2. Sign up (free, 2 minutes)
3. Click "Create Cronjob"
4. Paste your URL: `https://yoursite.com/run_backup.php?key=YOUR_KEY`
5. Set schedule: `0 2 * * *` (daily at 2 AM)
6. Save

**Done! ✅ Backups will now run automatically!**

---

## 🔍 Troubleshooting

### "Unauthorized" Error
- Check the key matches exactly in both URL and run_backup.php
- Keys are case-sensitive
- No spaces at beginning/end

### 500 Error
- Make sure `run_backup.php` exists (not just the template)
- Check file has `require_once __DIR__ . '/config.php';`
- Verify `config.php` exists

### 301 Redirect
- Use `https://` not `http://` if you have SSL
- Some servers redirect www to non-www or vice versa
- Test both versions of your URL
- Remove trailing slash if present

### Still Not Working?
Check `system.log` in admin panel for error details.

---

## 📖 Full Documentation
See `NO_ACTION_BACKUP_SETUP.md` for:
- Multiple free service options
- Advanced scheduling
- Monitoring and alerts
- Security best practices
