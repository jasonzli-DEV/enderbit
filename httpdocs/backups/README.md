# Backup Directory

This directory contains backup files of important JSON data:
- `tokens_*.json` - User registration tokens and pending approvals
- `tickets_*.json` - Support ticket data and replies
- `settings_*.json` - Admin configuration settings

**Important Notes:**
- These files contain sensitive data and should be protected
- Backups are automatically timestamped when created
- Use the backup management interface to restore or delete backups
- Regular backups are recommended before major changes

## File Naming Convention
Files are named with the format: `{type}_{YYYY-MM-DD_HH-mm-ss}.json`

Example: `tokens_2025-10-08_14-30-45.json`