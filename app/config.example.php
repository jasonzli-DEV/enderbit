<?php
/**
 * EnderBit App Portal Configuration
 * app.enderbit.com - Client portal for server management and credits
 * 
 * SETUP: Copy this file to config.php and fill in your API keys
 */

return [
    // Database files
    'credits_file' => __DIR__ . '/credits.json',
    'transactions_file' => __DIR__ . '/transactions.json',
    'servers_file' => __DIR__ . '/servers.json',
    'users_file' => __DIR__ . '/../httpdocs/users.json', // Shared with main site
    
    // AyeT Studios API Configuration
    'ayetstudios' => [
        'enabled' => true,
        'app_id' => '', // Your AyeT Studios App ID
        'secret_key' => '', // Your AyeT Studios Secret Key
        'offerwall_url' => 'https://www.ayetstudios.com/offers/web_offerwall/',
        'api_url' => 'https://www.ayetstudios.com/api/',
    ],
    
    // Pterodactyl Panel Configuration
    'pterodactyl' => [
        'url' => 'https://panel.enderbit.com',
        'api_key' => '', // Application API key from Pterodactyl
        'admin_api_key' => '', // Admin API key for server creation
    ],
    
    // Credit System
    'credits' => [
        'currency_symbol' => '⚡', // Lightning bolt for credits
        'currency_name' => 'Credits',
        'free_signup_credits' => 100, // Free credits on signup
        'minimum_balance' => 10, // Minimum credits to keep server running
    ],
    
    // Server Pricing (credits per hour)
    'server_pricing' => [
        'minecraft' => [
            'basic' => [
                'name' => 'Basic (1GB RAM, 1 CPU)',
                'ram' => 1024,
                'cpu' => 100,
                'disk' => 5000,
                'cost_per_hour' => 1,
            ],
            'standard' => [
                'name' => 'Standard (2GB RAM, 2 CPU)',
                'ram' => 2048,
                'cpu' => 200,
                'disk' => 10000,
                'cost_per_hour' => 2,
            ],
            'premium' => [
                'name' => 'Premium (4GB RAM, 4 CPU)',
                'ram' => 4096,
                'cpu' => 400,
                'disk' => 20000,
                'cost_per_hour' => 4,
            ],
            'ultra' => [
                'name' => 'Ultra (6GB RAM, 6 CPU)',
                'ram' => 6144,
                'cpu' => 600,
                'disk' => 30000,
                'cost_per_hour' => 6,
            ],
        ],
        'rust' => [
            'standard' => [
                'name' => 'Standard (4GB RAM, 4 CPU)',
                'ram' => 4096,
                'cpu' => 400,
                'disk' => 20000,
                'cost_per_hour' => 4,
            ],
            'ultra' => [
                'name' => 'Ultra (6GB RAM, 6 CPU)',
                'ram' => 6144,
                'cpu' => 600,
                'disk' => 30000,
                'cost_per_hour' => 6,
            ],
        ],
        'valheim' => [
            'basic' => [
                'name' => 'Basic (2GB RAM, 2 CPU)',
                'ram' => 2048,
                'cpu' => 200,
                'disk' => 5000,
                'cost_per_hour' => 2,
            ],
            'ultra' => [
                'name' => 'Ultra (6GB RAM, 6 CPU)',
                'ram' => 6144,
                'cpu' => 600,
                'disk' => 30000,
                'cost_per_hour' => 6,
            ],
        ],
    ],
    
    // Server Games Available
    'games' => [
        'minecraft' => 'Minecraft',
        'rust' => 'Rust',
        'valheim' => 'Valheim',
        'terraria' => 'Terraria',
        'ark' => 'ARK: Survival Evolved',
    ],
    
    // Timezone
    'timezone' => 'America/New_York',
    
    // Session
    'session_timeout' => 3600, // 1 hour
];
