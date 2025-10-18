# Setting Up app.enderbit.com Subdomain in Plesk

## Step-by-Step Guide

### Step 1: Create Subdomain in Plesk

1. **Log into Plesk** control panel

2. **Navigate to Websites & Domains**
   - Find your `enderbit.com` domain
   - Click on it

3. **Add Subdomain**
   - Click the **"Subdomains"** button or **"Add Subdomain"** option
   - Fill in the details:
     - **Subdomain name:** `app`
     - **Document root:** `app` (just the folder name, relative to httpdocs)
   - Click **OK**

4. **Verify DNS**
   - Plesk automatically creates the DNS record
   - Go to **DNS Settings** under your domain
   - You should see a new record:
     ```
     app.enderbit.com  →  A record or CNAME pointing to your server
     ```

### Step 2: Configure Document Root (Important!)

Make sure the subdomain points to the correct folder:

**Correct Document Root (in Plesk):**
```
app
```

**Important Notes:**
- Plesk's base directory is already at `enderbit.com/httpdocs/`
- So you only need to specify: `app` (the subfolder name)
- NOT `httpdocs/app` or `/app` - just `app`

### Step 3: Enable SSL/HTTPS (Recommended)

1. In Plesk, go to the **app** subdomain
2. Click **SSL/TLS Certificates**
3. Click **Install** or **Get it free** for Let's Encrypt
4. Select **Secure the domain name and www subdomain**
5. Enter your email address
6. Click **Get it free** or **Install**

### Step 4: Test Access

After DNS propagates (5-30 minutes), test:
```
https://app.enderbit.com
```

You should see the app login/dashboard.

---

## Alternative: Using .htaccess Redirect (Temporary Solution)

If you can't create a subdomain immediately, you can set up a redirect:

**In your main `enderbit.com/httpdocs/.htaccess`:**
```apache
# Redirect app subdomain to app folder
RewriteEngine On
RewriteCond %{HTTP_HOST} ^app\.enderbit\.com$ [NC]
RewriteCond %{REQUEST_URI} !^/app/
RewriteRule ^(.*)$ /app/$1 [L]
```

---

## Troubleshooting

### Getting 404 Error with nginx?
**Solution:** Check the document root in Plesk:
1. Go to **Websites & Domains** → **app.enderbit.com**
2. Click **Hosting Settings**
3. Verify **Document root** is set to: `httpdocs/app`
4. Make sure **nginx** is enabled
5. Click **OK** to save

### DNS not propagating?
- Check DNS settings in Plesk: **Websites & Domains > DNS Settings**
- Make sure there's an A or CNAME record for `app`
- Wait 15-30 minutes for propagation
- Test with: `nslookup app.enderbit.com` or `dig app.enderbit.com`

### Still getting 404?
- Verify document root is correct
- Check that the `/app` folder exists and has an `index.php`
- Check file permissions (should be 755 for folders, 644 for files)

### Getting redirected to main site?
- Update authentication redirects in app PHP files
- Make sure session handling works across the subdomain

---

## Update Authentication After Subdomain is Live

Once `app.enderbit.com` is working, you'll need to update the redirect paths in the app files from `/httpdocs/index.php` to `https://enderbit.com/` for login redirects.

Let me know when the subdomain is set up and I can update those paths!
