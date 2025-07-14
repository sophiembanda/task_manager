# Email Setup Guide

This guide explains how to configure email notifications for the Task Manager system.

## Overview

The system automatically sends email notifications when:
- New tasks are assigned to users
- Task assignments include full details and deadlines

## Configuration Options

The system supports two email modes via the `.env` file:
- **Development**: MailHog for testing (SMTP_ENABLED=false)
- **Production**: Real SMTP server (SMTP_ENABLED=true)

## Development Setup (MailHog)

### Step 1: Install MailHog
1. **Download**: https://github.com/mailhog/MailHog/releases/download/v1.0.0/MailHog_windows_amd64.exe
2. **Run MailHog**: Double-click the executable
3. **Keep running** during development

### Step 2: Configure .env for Development
```env
# Email Configuration (Development)
SMTP_ENABLED=false
SMTP_HOST=localhost
SMTP_PORT=1025
FROM_EMAIL=noreply@taskmanager.local
FROM_NAME=Task Manager System
```

### Step 3: Update php.ini
Edit `C:\xampp\php\php.ini`:
```ini
[mail function]
SMTP = localhost
smtp_port = 1025
sendmail_from = noreply@taskmanager.local
```

### Step 4: Test
- **Send test email**: Admin → Test Email
- **View emails**: http://localhost:8025

## Production Setup

### Option 1: Gmail SMTP
1. **Enable 2FA** on Gmail account
2. **Generate app password**: Google Account → Security → App passwords
3. **Configure .env**:
   ```env
   SMTP_ENABLED=true
   GMAIL_USERNAME=your-email@gmail.com
   GMAIL_PASSWORD=your-16-char-app-password
   FROM_EMAIL=your-email@gmail.com
   FROM_NAME=Your Company Name
   ```

### Option 2: Professional Email Service
Use services like SendGrid, Mailgun, or Amazon SES:
```env
SMTP_ENABLED=true
SMTP_HOST=smtp.sendgrid.net
SMTP_PORT=587
GMAIL_USERNAME=apikey
GMAIL_PASSWORD=your-api-key
FROM_EMAIL=noreply@yourdomain.com
FROM_NAME=Your Company Name
```

## Email Templates

The system includes responsive HTML email templates featuring:
- Professional branding
- Task details (title, description, deadline)
- Direct link to task manager
- Mobile-friendly design

## Security Notes

- **Never commit** `.env` file to version control
- **Use app passwords** for Gmail (not main password)
- **Use environment variables** for sensitive data
- **Configure SPF/DKIM** records for production

## Troubleshooting

### Common Issues
1. **Emails not sending**: Check php.ini configuration and restart Apache
2. **Emails in spam**: Expected for development, configure DNS records for production
3. **SMTP errors**: Verify credentials and server settings

### Testing Commands
```bash
# Test email functionality
http://localhost/task_manager/admin/email_test.php

# View MailHog interface (development)
http://localhost:8025
```

## File Structure
- `config/email.php` - Email service class
- `config/env.php` - Environment variable loader
- `.env` - Configuration (not in git)
- `.env.example` - Template for configuration
