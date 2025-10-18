# QUICK FIX: 404 Error on app.enderbit.com

## The Problem
You're getting a **404 Not Found (nginx)** error on app.enderbit.com

## The Solution
The subdomain is created but pointing to the wrong folder. Here's how to fix it:

### Step 1: Check/Update Document Root in Plesk

1. **Log into Plesk**

2. **Go to Websites & Domains**

3. **Find and click on `app.enderbit.com`** (in the subdomains list)

4. **Click "Hosting Settings"**

5. **Update the Document root to:**
   ```
   app
   ```
   
   **IMPORTANT:** Just `app` - NOT `httpdocs/app` or `/app`
   
   Plesk's base directory is already at `/enderbit.com/httpdocs/`, so you only need to specify the subfolder name.

6. **Make sure these are enabled:**
   - ✅ **nginx** is enabled
   - ✅ **PHP** support is enabled
   - ✅ **Access to the site** is NOT restricted

7. **Click OK** to save

### Step 2: Verify Files Exist

In Plesk File Manager, navigate to:
```
enderbit.com/httpdocs/app/
```

You should see these files:
- ✅ `index.php`
- ✅ `config.php` (or `config.example.php`)
- ✅ `.htaccess`
- ✅ Other PHP files (credits.php, billing.php, etc.)

If these files are missing, you need to pull the latest code from GitHub:
```bash
cd /var/www/vhosts/enderbit.com
git pull
```

### Step 3: Check File Permissions

Make sure the files have correct permissions:
- **Folders:** 755
- **PHP files:** 644

### Step 4: Test Again

Visit: `https://app.enderbit.com`

You should now see the app (might redirect to login if not authenticated).

---

## Still Not Working?

### Alternative: Check nginx Configuration

In Plesk, go to:
**Websites & Domains** → **app.enderbit.com** → **Apache & nginx Settings**

Make sure:
- ✅ **Proxy mode** is enabled (nginx proxies to Apache)
- ✅ **Smart static files processing** is enabled

---

## Emergency Alternative: Use Direct Path

If the subdomain setup is problematic, you can access the app at:
```
https://enderbit.com/app/
```

This should work immediately without subdomain configuration.
