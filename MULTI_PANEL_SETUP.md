# EnderBit Multi-Panel System Setup Guide

## 🎯 System Overview

EnderBit now consists of three interconnected panels:

1. **enderbit.com** - Main marketing website
2. **app.enderbit.com** - Client portal for server management & credits
3. **panel.enderbit.com** - Pterodactyl panel for actual server control

## 📋 Architecture

```
User Flow:
┌─────────────────┐
│  enderbit.com   │  → Marketing, signup, support
│  (Main Site)    │
└────────┬────────┘
         │
         ▼
┌──────────────────┐
│ app.enderbit.com │  → Dashboard, create servers, earn credits
│ (Client Portal)  │
└────────┬─────────┘
         │
         ▼
┌─────────────────────┐
│ panel.enderbit.com  │  → Pterodactyl panel (console, files, etc.)
│ (Game Panel)        │
└─────────────────────┘
```

## 🛠️ Prerequisites

### 1. Pterodactyl Panel Installation
First, you need to install Pterodactyl on `panel.enderbit.com`:

```bash
# Follow official guide: https://pterodactyl.io/panel/1.0/getting_started.html
# Or use automated installer:
curl -sSL https://pterodactyl-installer.se | sudo bash -s -- panel
```

### 2. Domain Setup
Configure three subdomains:
- `enderbit.com` → Main site (existing)
- `app.enderbit.com` → Client portal (new `/app` directory)
- `panel.enderbit.com` → Pterodactyl installation

### 3. Web Server Configuration

**For Apache:**
```apache
# /etc/apache2/sites-available/app.enderbit.com.conf
<VirtualHost *:80>
    ServerName app.enderbit.com
    DocumentRoot /var/www/enderbit.com/app
    
    <Directory /var/www/enderbit.com/app>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

**For Nginx:**
```nginx
# /etc/nginx/sites-available/app.enderbit.com
server {
    listen 80;
    server_name app.enderbit.com;
    root /var/www/enderbit.com/app;
    index index.php;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
    }
}
```

## ⚙️ Configuration

### 1. Pterodactyl API Keys

Generate API keys in Pterodactyl:
1. Go to `https://panel.enderbit.com/admin`
2. **Application Keys** (for user management):
   - Navigate to: Admin Panel → Application API
   - Click "Create New"
   - Description: "EnderBit Client Portal"
   - Copy the key

3. **Admin Keys** (for server creation):
   - Navigate to: Admin Panel → Application API
   - Click "Create New"  
   - Description: "EnderBit Server Management"
   - Copy the key

### 2. Update app/config.php

Edit `/app/config.php`:

```php
'pterodactyl' => [
    'url' => 'https://panel.enderbit.com',
    'api_key' => 'ptla_YOUR_APPLICATION_API_KEY_HERE',
    'admin_api_key' => 'ptla_YOUR_ADMIN_API_KEY_HERE',
],
```

### 3. AyeT Studios Setup

1. Register at https://www.ayetstudios.com/account/login/register-publisher
2. Create a new app/website
3. Get your App ID and Secret Key
4. Update `app/config.php`:

```php
'ayetstudios' => [
    'enabled' => true,
    'app_id' => 'YOUR_AYET_APP_ID',
    'secret_key' => 'YOUR_AYET_SECRET_KEY',
    'offerwall_url' => 'https://www.ayetstudios.com/offers/web_offerwall/',
    'api_url' => 'https://www.ayetstudios.com/api/',
],
```

5. Set callback URL in AyeT Studios dashboard:
   - Callback URL: `https://app.enderbit.com/ayetstudios_callback.php`
   - This is where AyeT Studios will send reward notifications

## 💳 Credit System

### How It Works:
- **1 Credit = 1 hour of basic server hosting**
- Users earn credits through:
  - Signup bonus (100 credits free)
  - AyeT Studios offerwall/videos/surveys
  - Direct purchases (optional - you can add payment gateway)

### Pricing Structure:
```php
'minecraft' => [
    'basic' => 1 credit/hour (1GB RAM)
    'standard' => 2 credits/hour (2GB RAM)
    'premium' => 4 credits/hour (4GB RAM)
],
'rust' => [
    'basic' => 2 credits/hour (2GB RAM)
    'standard' => 4 credits/hour (4GB RAM)
],
```

## 🔄 Hourly Billing System

### Automatic Billing - No Cron Required!

Billing is **fully automatic** and integrated into the existing background tasks system. It runs on **any page visit** (public or admin pages) when at least 1 hour has passed since the last billing run.

**How It Works:**
1. Any page on the site is visited
2. `background_tasks.php` is called
3. System checks if 1+ hour passed since last billing
4. If yes, billing runs automatically
5. Credits deducted, servers suspended if needed
6. Logs written to `billing.log`

**No configuration needed!** The billing system is already integrated and will start working automatically once the app is set up.

**Manual Testing:**
```bash
# Test billing manually if needed
php /var/www/enderbit.com/app/billing.php
```

## 🎮 Pterodactyl Configuration

### 1. Create Eggs for Each Game

In Pterodactyl admin panel:
- Install eggs for: Minecraft, Rust, Valheim, Terraria, ARK
- Configure startup parameters
- Set resource limits

### 2. Configure Nodes

Add game server nodes:
- Set up FQDN (e.g., `node1.enderbit.com`)
- Configure allocations (ports for game servers)
- Set memory and disk limits

### 3. Map Egg IDs

After creating eggs, update `app/pterodactyl_api.php`:

```php
private static function getEggId($game) {
    $eggMap = [
        'minecraft' => 1,  // Replace with actual egg ID
        'rust' => 2,       // Replace with actual egg ID
        'valheim' => 3,    // Replace with actual egg ID
        'terraria' => 4,   // Replace with actual egg ID
        'ark' => 5,        // Replace with actual egg ID
    ];
    return $eggMap[$game] ?? 1;
}
```

To find egg IDs:
```bash
# Via Pterodactyl API:
curl -X GET "https://panel.enderbit.com/api/application/nests" \
     -H "Authorization: Bearer YOUR_ADMIN_API_KEY" \
     -H "Accept: application/json"
```

## 🔐 Security

### 1. File Permissions
```bash
cd /var/www/enderbit.com/app
chmod 644 config.php
chmod 666 credits.json transactions.json servers.json
chmod 644 *.php
```

### 2. Update .gitignore
Already configured to exclude:
- `app/config.php`
- `app/credits.json`
- `app/transactions.json`
- `app/servers.json`

### 3. SSL Certificates
```bash
# Install Let's Encrypt SSL for all domains
certbot --apache -d app.enderbit.com
certbot --apache -d panel.enderbit.com
```

## 📊 Testing

### 1. Test Credit System
```bash
# Grant signup bonus to test user
php -r "
require 'app/credits.php';
EnderBitCredits::grantSignupBonus('test_user_123');
echo 'Balance: ' . EnderBitCredits::getBalance('test_user_123');
"
```

### 2. Test Server Creation
1. Log in to `app.enderbit.com`
2. Ensure you have credits
3. Click "Create Server"
4. Select game and plan
5. Verify server appears in Pterodactyl

### 3. Test Billing
```bash
# Run billing manually
php /var/www/enderbit.com/app/billing.php

# Check logs
tail -f /var/www/enderbit.com/app/billing.log
```

## 🚀 User Journey

1. **Sign Up** on `enderbit.com`
   - User creates account
   - Receives 100 free credits

2. **Access Client Portal** at `app.enderbit.com`
   - View credit balance
   - See dashboard

3. **Earn More Credits**
   - Complete offers on offerwall
   - Watch videos
   - Take surveys

4. **Create Server**
   - Select game type
   - Choose plan (resource tier)
   - Server created in Pterodactyl

5. **Manage Server** at `panel.enderbit.com`
   - Full Pterodactyl access
   - Console, files, schedules, etc.

6. **Auto Billing**
   - Every hour, credits deducted
   - If insufficient: server suspended
   - Add credits to unsuspend

## 📝 File Structure

```
enderbit.com/
├── httpdocs/          # Main site
│   ├── index.php
│   ├── services.php
│   ├── support.php
│   └── ...
│
├── app/               # Client portal (app.enderbit.com)
│   ├── config.php                 # Configuration
│   ├── credits.php                # Credit management
│   ├── ayetstudios.php           # AyeT Studios integration
│   ├── ayetstudios_callback.php  # Reward webhook
│   ├── pterodactyl_api.php       # Pterodactyl API
│   ├── billing.php                # Hourly billing system
│   ├── index.php                  # Dashboard
│   ├── create_server.php         # Server creation
│   ├── earn_credits.php          # Offerwall page
│   ├── unsuspend_server.php      # Unsuspend handler
│   ├── delete_server.php         # Delete handler
│   ├── get_balance.php           # Balance API
│   ├── credits.json              # Credit balances
│   ├── transactions.json         # Transaction history
│   └── servers.json              # Server database
│
└── panel/             # Pterodactyl (panel.enderbit.com)
    └── (Pterodactyl installation)
```

## 🎯 Next Steps

1. ✅ Install Pterodactyl on `panel.enderbit.com`
2. ✅ Configure domains and web server
3. ✅ Generate API keys
4. ✅ Update `app/config.php` with API keys
5. ✅ Register with AyeT Studios
6. ✅ Set up offerwall callback URL
7. ✅ Create game eggs in Pterodactyl
8. ✅ Test server creation flow
9. ✅ Launch!

**Note:** Hourly billing runs automatically - no cron setup needed!

## 🆘 Support

- **Pterodactyl Docs**: https://pterodactyl.io/panel/1.0/getting_started.html
- **AyeT Studios Docs**: https://docs.ayetstudios.com/
- **EnderBit Support**: Create ticket at enderbit.com/support.php

## 💡 Optional Enhancements

1. **Add Payment Gateway** for buying credits
   - Stripe integration
   - PayPal integration
   - Cryptocurrency payments

2. **Email Notifications**
   - Low credit warnings
   - Server suspension alerts
   - Billing receipts

3. **Referral System**
   - Earn credits for referring friends
   - Track referral signups

4. **Server Analytics**
   - CPU/RAM usage graphs
   - Player activity tracking
   - Uptime monitoring

5. **Promo Codes**
   - Discount codes for credits
   - Special promotions
