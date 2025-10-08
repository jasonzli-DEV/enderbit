<?php
/**
 * Initialize sample log data for demonstration
 * This file demonstrates the logging system and can be run once to seed log data
 */

require_once __DIR__ . '/logger.php';

echo "Initializing EnderBit logging system...\n";

// Sample authentication logs
EnderBitLogger::logAuth('ADMIN_LOGIN_SUCCESS', ['admin' => true, 'session_id' => 'demo123']);
EnderBitLogger::logAuth('ADMIN_LOGIN_FAILED', ['admin' => true, 'reason' => 'Invalid password']);

// Sample registration logs  
EnderBitLogger::logRegistration('REGISTRATION_ATTEMPT', 'demo@example.com', ['username' => 'demouser']);
EnderBitLogger::logRegistration('REGISTRATION_SUCCESS', 'demo@example.com', ['case' => 'email_verification_required']);

// Sample ticket logs
EnderBitLogger::logTicket('TICKET_CREATED', 'TICKET-DEMO123', 'user@example.com', ['subject' => 'Server Setup Help', 'category' => 'support']);
EnderBitLogger::logTicket('TICKET_REPLY_ADDED', 'TICKET-DEMO123', 'user@example.com', ['is_admin' => false]);
EnderBitLogger::logTicket('TICKET_CLOSED', 'TICKET-DEMO123', null, ['closed_by' => 'admin']);

// Sample email logs
EnderBitLogger::logEmail('VERIFICATION_EMAIL_SENT', 'demo@example.com', 'Verify your Enderbit account');
EnderBitLogger::logEmail('TICKET_NOTIFICATION_SENT', 'admin@enderbit.com', 'New Ticket: Server Setup Help');

// Sample Pterodactyl API logs
EnderBitLogger::logPterodactyl('USER_CREATION_SUCCESS', '/api/application/users', ['email' => 'demo@example.com', 'user_id' => 123]);
EnderBitLogger::logPterodactyl('USER_CREATION_FAILED', '/api/application/users', ['email' => 'fail@example.com', 'error' => 'Email already exists']);

// Sample admin logs
EnderBitLogger::logAdmin('USER_APPROVAL_SUCCESS', 'APPROVE_USER', ['email' => 'pending@example.com']);
EnderBitLogger::logAdmin('SETTINGS_UPDATED', 'UPDATE_SETTINGS', ['require_email_verify' => true, 'require_admin_approve' => false]);

// Sample security logs
EnderBitLogger::logSecurity('INVALID_LOGIN_ATTEMPT', 'MEDIUM', ['attempted_email' => 'hacker@bad.com']);
EnderBitLogger::logSecurity('SUSPICIOUS_FILE_UPLOAD', 'HIGH', ['filename' => 'malware.exe', 'blocked' => true]);

// Sample system logs
EnderBitLogger::logSystem('APPLICATION_STARTUP', ['version' => '1.0.0', 'php_version' => PHP_VERSION]);
EnderBitLogger::logSystem('DATABASE_CONNECTION_FAILED', ['error' => 'Connection timeout']);

// Sample performance logs
EnderBitLogger::logPerformance('PAGE_LOAD_COMPLETE', ['page' => 'index', 'render_time' => 0.245]);
EnderBitLogger::logPerformance('TICKET_CREATION_PROCESSING', ['ticket_id' => 'TICKET-DEMO123', 'has_attachment' => true]);
EnderBitLogger::logPerformance('DATABASE_QUERY_SLOW', ['query_type' => 'ticket_search', 'execution_time' => 2.5]);

// Sample upload logs
EnderBitLogger::logUpload('UPLOAD_SUCCESS', 'screenshot.png', ['ticket_id' => 'TICKET-DEMO123', 'file_size' => 245760]);
EnderBitLogger::logUpload('UPLOAD_REJECTED_TYPE', 'malware.exe', ['reason' => 'Invalid file type']);

echo "Sample log data initialized successfully!\n";
echo "Visit /logs.php in the admin panel to view the logs.\n";
?>