# 🎮 EnderBit Hosting

A modern hosting panel integration for Pterodactyl with support ticket system.

## Features

✨ **User Management**
- User registration with email verification
- Optional admin approval workflow
- Pterodactyl Panel API integration
- Secure session handling with automatic time-based validation

🔒 **Security Features**
- Automatic password hashing (Argon2ID)
- CSRF protection on all admin forms
- Rate limiting (5 attempts per 5 minutes)
- Security headers (CSP, X-Frame-Options, etc.)
- Input sanitization and validation
- Comprehensive security logging

💾 **Automatic Backups**
- Scheduled backups (hourly/daily/weekly)
- No cron jobs or server access required
- Works with free external services
- Zero-action setup with external triggers
- Automatic on admin page loads OR standalone URL
- See `NO_ACTION_BACKUP_SETUP.md` for details

🎫 **Support Ticket System**
- Create and manage support tickets
- Email notifications for all ticket events
- Client and admin reply functionality
- Ticket status management (open/closed/reopen)
- Real-time emoji support in emails

🎨 **Modern UI**
- Dark/Light theme toggle
- Blue-tinted design aesthetic
- Responsive layout
- Professional navigation
- Consistent styling across all pages

🔄 **Git Deployment**
- One-click updates from GitHub
- Deployment logs and history
- Git status checker
- Admin-only access

## Project Structure

```
enderbit.com/
├── httpdocs/                # Web root directory
│   ├── index.php           # Homepage
│   ├── services.php        # Services page
│   ├── support.php         # Support ticket submission
│   ├── view_ticket.php     # View individual tickets
│   ├── signup.php          # User registration
│   ├── admin.php           # Admin panel
│   ├── update.php          # Git deployment interface
│   ├── config.php          # Configuration (NOT in Git)
│   ├── tokens.json         # User tokens (NOT in Git)
│   ├── tickets.json        # Support tickets (NOT in Git)
│   ├── settings.json       # Admin settings (NOT in Git)
│   ├── style.css           # Main stylesheet
│   └── icon.png            # Site icon
├── .gitignore              # Git ignore rules
├── .gitattributes          # Git line ending rules
├── README.md               # This file
└── GIT_SETUP_GUIDE.md      # Git deployment setup guide

```

## Requirements

- PHP 7.4 or higher
- Web server (Apache/Nginx)
- Git (for deployment features)
- Pterodactyl Panel instance
- SMTP server or sendmail (for emails)

## Installation

### 1. Clone Repository

```bash
git clone https://github.com/YOUR_USERNAME/enderbit-hosting.git
cd enderbit-hosting
```

### 2. Configure

Create `httpdocs/config.php`:

```php
<?php
$config = [
    'admin_password' => 'your_secure_password',
    'ptero_url' => 'https://panel.yourdomain.com',
    'ptero_key' => 'your_pterodactyl_api_key',
    'recaptcha_site' => 'your_recaptcha_site_key',
    'recaptcha_secret' => 'your_recaptcha_secret_key',
    'smtp_host' => 'mail.yourdomain.com',
    'smtp_port' => 465,
    'smtp_user' => 'noreply@yourdomain.com',
    'smtp_pass' => 'your_smtp_password',
    'from_email' => 'noreply@yourdomain.com',
    'from_name' => 'EnderBit Hosting',
    'admin_email' => 'support@yourdomain.com'
];
?>
```

### 3. Set Permissions

```bash
chmod 755 httpdocs/
chmod 644 httpdocs/*.php
chmod 666 httpdocs/*.json
```

### 4. Initialize Data Files

The following files will be auto-created:
- `tokens.json` - User registration data
- `tickets.json` - Support tickets
- `settings.json` - Admin settings
- `deployment.log` - Git deployment logs

### 5. Security Features

**Automatic Password Protection:**
- On your first admin login, the system automatically converts your plain text password to a secure hash
- No manual migration needed - just log in normally
- A backup of your config.php is created automatically
- See `SECURITY_IMPLEMENTATION.md` for full security documentation

## Configuration

### Admin Settings

Access the admin panel at `/admin.php`:
- **Require Email Verification** - Users must verify email before approval
- **Require Admin Approval** - Manually approve new registrations

### Pterodactyl Integration

The system creates Pterodactyl users automatically with:
- Email from registration
- Username from signup form
- Auto-generated secure password (8 characters)

### Email System

Emails are sent for:
- ✅ Email verification
- 🎫 New ticket creation
- 💬 Ticket replies
- 🔒 Ticket closed
- 🔓 Ticket reopened

### reCAPTCHA

Protected forms:
- User registration (`signup.php`)
- Support ticket submission (`support.php`)

## Git Deployment

See [GIT_SETUP_GUIDE.md](GIT_SETUP_GUIDE.md) for complete setup instructions.

**Quick start:**
1. Log in to admin panel
2. Click "🔄 Pull Latest Updates"
3. Click "⬇️ Pull Latest Changes"

## Theme System

The site supports dark/light themes with localStorage persistence.

**Theme Variables:**
```css
/* Dark Theme */
--bg: #0d1117
--card: #161b22
--accent: #58a6ff
--text: #e6eef8

/* Light Theme */
--bg: #eff6ff
--card: #ffffff
--accent: #3b82f6
--text: #1e3a8a
```

Toggle button in navigation switches themes.

## Security Features

✅ Session-based authentication  
✅ Password hashing (Pterodactyl)  
✅ reCAPTCHA protection  
✅ Admin-only routes  
✅ Email verification  
✅ Input sanitization  
✅ Secure file permissions  
✅ Config files excluded from Git  

## Support Ticket Workflow

### For Clients:
1. Submit ticket via `/support.php`
2. Receive email with ticket link
3. View ticket at `/view_ticket.php?id=TICKET_ID`
4. Reply to ticket via form
5. Receive email notifications for admin responses

### For Admins:
1. Receive email notification for new tickets
2. View all tickets in `/admin.php`
3. Reply to tickets via admin panel
4. Close/reopen tickets as needed
5. Clients receive emails for all actions

## API Endpoints

### Internal APIs

**Create Ticket:** `create_ticket.php`
- Method: POST
- Fields: name, email, subject, message, captcha

**Reply to Ticket:** `reply_ticket.php`
- Method: POST
- Actions: reply, close, reopen
- Fields: ticket_id, message, is_admin

**User Registration:** `register.php`
- Method: POST
- Fields: email, username, password, captcha

**Email Verification:** `verify.php`
- Method: GET
- Param: token

## Development

### Making Changes

```bash
# Make your changes
vim httpdocs/index.php

# Commit
git add .
git commit -m "Description of changes"

# Push to GitHub
git push origin main
```

### Deploy to Server

Use the admin panel's "Pull Latest Updates" button or SSH:

```bash
cd /path/to/enderbit.com
git pull origin main
```

## Troubleshooting

### Email Not Sending

Check SMTP configuration in `config.php`:
```php
'smtp_host' => 'mail.yourdomain.com',
'smtp_port' => 465,
'smtp_user' => 'noreply@yourdomain.com',
'smtp_pass' => 'your_password',
```

### Pterodactyl Connection Failed

Verify API key has correct permissions:
- Users: Read & Write
- Check panel URL is correct (no trailing slash)

### Git Pull Not Working

Check file permissions:
```bash
sudo chown -R www-data:www-data /path/to/enderbit.com
```

See [GIT_SETUP_GUIDE.md](GIT_SETUP_GUIDE.md) for detailed troubleshooting.

## License

This project is proprietary software for EnderBit Hosting.

## Credits

- Built with PHP and vanilla JavaScript
- Icons: Unicode emoji
- Theme inspiration: GitHub's color palette
- No external dependencies

## Support

For issues or questions, create a support ticket through the system! 🎫

---

**EnderBit Hosting** - Professional hosting management made simple.
