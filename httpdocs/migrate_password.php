<?php
/**
 * Password Hash Migration Script
 * 
 * This script migrates the plain-text admin password to a secure hash.
 * Run this once after updating to the new security system.
 * 
 * Usage: php migrate_password.php
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/logger.php';

echo "EnderBit Password Migration Script\n";
echo "===================================\n\n";

// Check if password is already hashed
if (isset($config['admin_password_hash'])) {
    echo "✓ Password is already hashed!\n";
    echo "  No migration needed.\n\n";
    exit(0);
}

// Check if plain password exists
if (!isset($config['admin_password'])) {
    echo "✗ Error: No admin_password found in config.php\n";
    echo "  Please check your configuration.\n\n";
    exit(1);
}

$plainPassword = $config['admin_password'];

echo "Current configuration:\n";
echo "  Plain password found: " . str_repeat('*', strlen($plainPassword)) . "\n\n";

echo "Starting migration...\n";

// Generate hash
$hash = EnderBitSecurity::hashPassword($plainPassword);
echo "✓ Generated secure hash\n";

// Read config.php
$configFile = __DIR__ . '/config.php';
$configContent = file_get_contents($configFile);

// Create backup
$backupFile = __DIR__ . '/config.php.backup.' . date('Y-m-d_His');
file_put_contents($backupFile, $configContent);
echo "✓ Created backup: " . basename($backupFile) . "\n";

// Update config content
// Add new hash line after admin_password
$pattern = "/('admin_password'\s*=>\s*'[^']*')/";
$replacement = "$1,\n    'admin_password_hash' => '" . $hash . "'";
$newContent = preg_replace($pattern, $replacement, $configContent);

if ($newContent === null || $newContent === $configContent) {
    echo "✗ Error: Could not update config.php automatically\n";
    echo "\nPlease add this line manually after 'admin_password':\n";
    echo "    'admin_password_hash' => '" . $hash . "',\n\n";
    echo "And optionally comment out or remove the old 'admin_password' line.\n\n";
    
    // Log the attempt
    if (class_exists('EnderBitLogger')) {
        EnderBitLogger::logSecurity('PASSWORD_MIGRATION_MANUAL', 'HIGH', [
            'status' => 'requires_manual_update',
            'hash_generated' => true
        ]);
    }
    
    exit(1);
}

// Save updated config
file_put_contents($configFile, $newContent);
echo "✓ Updated config.php with hashed password\n";

// Log the migration
if (class_exists('EnderBitLogger')) {
    EnderBitLogger::logSecurity('PASSWORD_MIGRATION_COMPLETE', 'HIGH', [
        'timestamp' => date('Y-m-d H:i:s'),
        'backup_created' => basename($backupFile)
    ]);
}

echo "\n✓ Migration complete!\n\n";
echo "Important notes:\n";
echo "1. Your old password still works\n";
echo "2. The system now uses the secure hash instead\n";
echo "3. You can optionally remove the 'admin_password' line from config.php\n";
echo "4. A backup was created: " . basename($backupFile) . "\n\n";
echo "Next steps:\n";
echo "- Test admin login to verify it works\n";
echo "- If successful, you can delete the backup file\n";
echo "- Optionally remove 'admin_password' from config.php (keep hash only)\n\n";
