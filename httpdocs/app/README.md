# EnderBit Client Portal (app.enderbit.com)

## Overview
This is the client portal for EnderBit's game server hosting platform. Users can create servers, manage credits, and earn free credits through AyeT Studios offerwall.

## Features

### 💰 Credit System
- **Earn credits** through offers, videos, and surveys
- **Spend credits** on game server hosting (hourly billing)
- **Track transactions** with complete history
- **Automatic billing** every hour for active servers

### 🎮 Server Management
- **Create servers** for Minecraft, Rust, Valheim, and more
- **Multiple plans** with different resource tiers
- **Auto-suspend** when credits run out
- **Easy unsuspend** when credits are added
- **Direct access** to Pterodactyl panel

### ⚡ Free Credits
- **Signup bonus**: 100 credits free
- **Offerwall**: Complete offers for credits
- **Videos**: Watch ads for quick credits
- **Surveys**: Share opinions for rewards

## Installation

See [MULTI_PANEL_SETUP.md](../MULTI_PANEL_SETUP.md) for complete setup instructions.

### Quick Start

1. **Configure Pterodactyl API**:
   ```php
   // Edit config.php
   'pterodactyl' => [
       'url' => 'https://panel.enderbit.com',
       'api_key' => 'YOUR_API_KEY',
       'admin_api_key' => 'YOUR_ADMIN_KEY',
   ],
   ```

2. **Configure AyeT Studios**:
   ```php
   // Edit config.php
   'ayetstudios' => [
       'enabled' => true,
       'app_id' => 'YOUR_APP_ID',
       'secret_key' => 'YOUR_SECRET_KEY',
   ],
   ```

3. **Set file permissions**:
   ```bash
   chmod 666 credits.json transactions.json servers.json
   ```

## File Structure

```
app/
├── config.php                 # Main configuration
├── credits.php                # Credit management system
├── ayetstudios.php           # AyeT Studios integration
├── ayetstudios_callback.php  # Webhook for rewards
├── pterodactyl_api.php       # Pterodactyl API wrapper
├── billing.php                # Hourly billing system
├── index.php                  # Dashboard
├── create_server.php         # Server creation page
├── earn_credits.php          # Offerwall page
├── unsuspend_server.php      # Unsuspend handler
├── delete_server.php         # Delete handler
├── get_balance.php           # Balance API endpoint
├── credits.json              # Credit balances (auto-created)
├── transactions.json         # Transaction log (auto-created)
└── servers.json              # Server database (auto-created)
```

## Credit Pricing

| Game       | Resource Tier | RAM  | CPU | Disk  | Cost/Hour |
|------------|--------------|------|-----|-------|-----------|
| Minecraft  | Basic        | 1GB  | 1   | 5GB   | ⚡1        |
| Minecraft  | Standard     | 2GB  | 2   | 10GB  | ⚡2        |
| Minecraft  | Premium      | 4GB  | 4   | 20GB  | ⚡4        |
| Minecraft  | Ultra        | 6GB  | 6   | 30GB  | ⚡6        |
| Rust       | Standard     | 4GB  | 4   | 20GB  | ⚡4        |
| Rust       | Ultra        | 6GB  | 6   | 30GB  | ⚡6        |
| Valheim    | Basic        | 2GB  | 2   | 5GB   | ⚡2        |
| Valheim    | Ultra        | 6GB  | 6   | 30GB  | ⚡6        |

## API Endpoints

### Get Balance
```bash
GET /get_balance.php
Response: {"balance": 150, "formatted": "⚡150"}
```

### AyeT Studios Callback
```bash
POST /ayetstudios_callback.php
Params: user_id, amount, transaction_id, signature, offer_name
Response: {"status": "success", "message": "Reward processed"}
```

## Security

- All sensitive data in `config.php` (excluded from git)
- Credit balances and transactions in JSON files (excluded from git)
- API signature verification for AyeT Studios callbacks
- Session-based authentication
- Input sanitization on all forms

## Integration with Main Site

- Shares `users.json` with main site (httpdocs/)
- Uses same session system
- Unified authentication
- Consistent branding and styling

## Troubleshooting

### Credits not appearing after offer completion
1. Check `ayetstudios_callbacks.log` for incoming webhooks
2. Verify signature in AyeT Studios dashboard
3. Ensure callback URL is correct: `https://app.enderbit.com/ayetstudios_callback.php`
4. Credits may take 5-15 minutes to appear

### Server creation fails
1. Check Pterodactyl API keys in `config.php`
2. Verify egg IDs match your Pterodactyl installation
3. Ensure allocations (ports) are available
4. Check PHP error logs

### Billing not running
1. Check `billing.log` for execution history
2. Billing runs automatically on any page visit (every hour)
3. Verify `billing.php` exists in app directory
4. Check system logs: `httpdocs/system.log`
5. Test manually: `php billing.php`

## Support

For issues and questions:
- Create a ticket at https://enderbit.com/support.php
- Check main setup guide: [MULTI_PANEL_SETUP.md](../MULTI_PANEL_SETUP.md)
- Pterodactyl docs: https://pterodactyl.io/
- AyeT Studios docs: https://docs.ayetstudios.com/
