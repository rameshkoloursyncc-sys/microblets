# Production Email Fix & Cron Job Setup

## Issue Analysis

The email appears to send successfully in the frontend but doesn't actually reach recipients in production. This is typically due to:

1. **SMTP Authentication Issues** in production environment
2. **Firewall/Port Blocking** on production server
3. **Gmail Security Settings** blocking the app password
4. **Queue Processing** not working properly
5. **Laravel Mail Configuration** issues

## Step 1: Debug Production Email Issue

### Check Current Email Configuration
```bash
# On production server, check if mail configuration is loaded correctly
php artisan tinker --execute="
echo 'Mail Configuration:' . PHP_EOL;
echo 'MAIL_MAILER: ' . config('mail.default') . PHP_EOL;
echo 'MAIL_HOST: ' . config('mail.mailers.smtp.host') . PHP_EOL;
echo 'MAIL_PORT: ' . config('mail.mailers.smtp.port') . PHP_EOL;
echo 'MAIL_USERNAME: ' . config('mail.mailers.smtp.username') . PHP_EOL;
echo 'MAIL_ENCRYPTION: ' . config('mail.mailers.smtp.encryption') . PHP_EOL;
echo 'MAIL_FROM_ADDRESS: ' . config('mail.from.address') . PHP_EOL;
"
```

### Test Email Sending Directly
```bash
# Test email sending with detailed error output
php artisan tinker --execute="
try {
    \Mail::raw('Test email from production server', function(\$message) {
        \$message->to('rameshnda09@gmail.com')
                ->subject('Production Email Test - ' . now());
    });
    echo 'Email sent successfully!' . PHP_EOL;
} catch (Exception \$e) {
    echo 'Email failed: ' . \$e->getMessage() . PHP_EOL;
    echo 'File: ' . \$e->getFile() . ':' . \$e->getLine() . PHP_EOL;
}
"
```

## Step 2: Fix Production Email Configuration

### Option A: Update .env.production (Recommended)
```bash
# Update your .env.production file with these settings:
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=incrypto09@gmail.com
MAIL_PASSWORD=tbmpmkrqnspnkubs
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="incrypto09@gmail.com"
MAIL_FROM_NAME="Microbelts Inventory System"

# Add these additional settings for better reliability
MAIL_TIMEOUT=60
MAIL_LOCAL_DOMAIN=microbelts.koloursyncc.in

# Ensure queue is set to sync for immediate email sending
QUEUE_CONNECTION=sync
```

### Option B: Alternative SMTP Settings (if Gmail fails)
```bash
# Try with different port and encryption
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=465
MAIL_USERNAME=incrypto09@gmail.com
MAIL_PASSWORD=tbmpmkrqnspnkubs
MAIL_ENCRYPTION=ssl
MAIL_FROM_ADDRESS="incrypto09@gmail.com"
MAIL_FROM_NAME="Microbelts Inventory System"
```

## Step 3: Gmail Security Configuration

### Enable App Password (if not done)
1. Go to Google Account settings
2. Enable 2-Factor Authentication
3. Generate App Password for "Mail"
4. Use the 16-character app password (no spaces)

### Check Gmail Settings
```bash
# Verify the app password is correct (16 characters, no spaces)
echo "Current password length: $(echo 'tbmpmkrqnspnkubs' | wc -c)"
# Should output: 17 (16 chars + newline)
```

## Step 4: Server-Level Fixes

### Check if SMTP ports are open
```bash
# Test SMTP connectivity from production server
telnet smtp.gmail.com 587
# Should connect successfully

# Alternative test
nc -zv smtp.gmail.com 587
# Should show: Connection to smtp.gmail.com 587 port [tcp/submission] succeeded!
```

### Install required PHP extensions
```bash
# Ensure required extensions are installed
php -m | grep -E "(openssl|mbstring|curl)"

# If missing, install them:
sudo apt-get update
sudo apt-get install php-openssl php-mbstring php-curl
```

## Step 5: Laravel Configuration Cache

### Clear and rebuild config cache
```bash
# Clear all caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Rebuild config cache with production settings
php artisan config:cache
```

## Step 6: Enhanced Email Debugging

### Create a debug email command
```bash
# Create debug command
php artisan make:command DebugEmail
```

### Add this to the command:
```php
<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class DebugEmail extends Command
{
    protected $signature = 'debug:email {email}';
    protected $description = 'Debug email sending with detailed output';

    public function handle()
    {
        $email = $this->argument('email');
        
        $this->info('Testing email configuration...');
        $this->info('MAIL_MAILER: ' . config('mail.default'));
        $this->info('MAIL_HOST: ' . config('mail.mailers.smtp.host'));
        $this->info('MAIL_PORT: ' . config('mail.mailers.smtp.port'));
        $this->info('MAIL_USERNAME: ' . config('mail.mailers.smtp.username'));
        $this->info('MAIL_ENCRYPTION: ' . config('mail.mailers.smtp.encryption'));
        
        try {
            Mail::raw('Debug email test from production - ' . now(), function($message) use ($email) {
                $message->to($email)
                        ->subject('Production Email Debug Test');
            });
            
            $this->info('✅ Email sent successfully to: ' . $email);
            return 0;
        } catch (\Exception $e) {
            $this->error('❌ Email failed: ' . $e->getMessage());
            $this->error('File: ' . $e->getFile() . ':' . $e->getLine());
            return 1;
        }
    }
}
```

### Test the debug command
```bash
php artisan debug:email rameshnda09@gmail.com
```

## Step 7: Cron Job Setup

### Method 1: Laravel Scheduler (Recommended)
```bash
# Add this single cron entry to run Laravel scheduler
crontab -e

# Add this line:
* * * * * cd /path/to/your/project && php artisan schedule:run >> /dev/null 2>&1
```

### Method 2: Direct Cron Jobs with Custom Timers

#### Daily at 8 AM (Current)
```bash
# Edit crontab
crontab -e

# Add this line for daily at 8 AM IST
0 8 * * * cd /path/to/your/project && php artisan report:low-stock --email=rameshnda09@gmail.com --email=ramesh.koloursyncc@gmail.com >> /var/log/stock-alerts.log 2>&1
```

#### Custom Timer Options

##### Every 6 Hours
```bash
0 */6 * * * cd /path/to/your/project && php artisan report:low-stock --email=rameshnda09@gmail.com --email=ramesh.koloursyncc@gmail.com >> /var/log/stock-alerts.log 2>&1
```

##### Twice Daily (8 AM and 6 PM)
```bash
0 8,18 * * * cd /path/to/your/project && php artisan report:low-stock --email=rameshnda09@gmail.com --email=ramesh.koloursyncc@gmail.com >> /var/log/stock-alerts.log 2>&1
```

##### Every Monday at 9 AM
```bash
0 9 * * 1 cd /path/to/your/project && php artisan report:low-stock --email=rameshnda09@gmail.com --email=ramesh.koloursyncc@gmail.com >> /var/log/stock-alerts.log 2>&1
```

##### Every Weekday at 8 AM
```bash
0 8 * * 1-5 cd /path/to/your/project && php artisan report:low-stock --email=rameshnda09@gmail.com --email=ramesh.koloursyncc@gmail.com >> /var/log/stock-alerts.log 2>&1
```

##### Custom: Every 3 hours during business hours (8 AM to 8 PM)
```bash
0 8,11,14,17,20 * * * cd /path/to/your/project && php artisan report:low-stock --email=rameshnda09@gmail.com --email=ramesh.koloursyncc@gmail.com >> /var/log/stock-alerts.log 2>&1
```

### Method 3: Enhanced Cron with Error Handling
```bash
# Create a wrapper script for better error handling
nano /path/to/your/project/send-stock-alert.sh
```

```bash
#!/bin/bash
# Stock Alert Cron Script with Error Handling

PROJECT_PATH="/path/to/your/project"
LOG_FILE="/var/log/stock-alerts.log"
ERROR_LOG="/var/log/stock-alerts-error.log"

cd $PROJECT_PATH

echo "$(date): Starting stock alert report..." >> $LOG_FILE

# Run the command and capture output
if php artisan report:low-stock --email=rameshnda09@gmail.com --email=ramesh.koloursyncc@gmail.com >> $LOG_FILE 2>&1; then
    echo "$(date): Stock alert completed successfully" >> $LOG_FILE
else
    echo "$(date): Stock alert failed with exit code $?" >> $ERROR_LOG
    # Optional: Send error notification
    echo "Stock alert cron job failed at $(date)" | mail -s "Cron Job Error" rameshnda09@gmail.com
fi

echo "---" >> $LOG_FILE
```

```bash
# Make script executable
chmod +x /path/to/your/project/send-stock-alert.sh

# Add to crontab
crontab -e

# Add this line (daily at 8 AM)
0 8 * * * /path/to/your/project/send-stock-alert.sh
```

## Step 8: Monitoring and Logs

### Check cron job logs
```bash
# View cron job execution logs
tail -f /var/log/stock-alerts.log

# View Laravel logs
tail -f /path/to/your/project/storage/logs/laravel.log

# View system cron logs
tail -f /var/log/cron.log
```

### Test cron job manually
```bash
# Test the exact command that cron will run
cd /path/to/your/project && php artisan report:low-stock --email=rameshnda09@gmail.com --email=ramesh.koloursyncc@gmail.com
```

## Step 9: Alternative Email Providers (Backup)

If Gmail continues to fail, consider these alternatives:

### SendGrid
```bash
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=your_sendgrid_api_key
MAIL_ENCRYPTION=tls
```

### Mailgun
```bash
MAIL_MAILER=mailgun
MAILGUN_DOMAIN=your-domain.com
MAILGUN_SECRET=your_mailgun_secret
```

## Quick Fix Commands

### 1. Immediate Email Test
```bash
php artisan tinker --execute="
\Mail::raw('Production email test - ' . now(), function(\$m) {
    \$m->to('rameshnda09@gmail.com')->subject('Production Test');
});
echo 'Test email sent!';
"
```

### 2. Setup Daily Cron (8 AM IST)
```bash
(crontab -l 2>/dev/null; echo "0 8 * * * cd $(pwd) && php artisan report:low-stock --email=rameshnda09@gmail.com --email=ramesh.koloursyncc@gmail.com >> /var/log/stock-alerts.log 2>&1") | crontab -
```

### 3. Setup Twice Daily Cron (8 AM and 6 PM IST)
```bash
(crontab -l 2>/dev/null; echo "0 8,18 * * * cd $(pwd) && php artisan report:low-stock --email=rameshnda09@gmail.com --email=ramesh.koloursyncc@gmail.com >> /var/log/stock-alerts.log 2>&1") | crontab -
```

### 4. View Current Cron Jobs
```bash
crontab -l
```

### 5. Remove All Cron Jobs (if needed)
```bash
crontab -r
```

## Troubleshooting Checklist

- [ ] Gmail app password is correct (16 characters)
- [ ] SMTP ports (587/465) are not blocked by firewall
- [ ] PHP openssl extension is installed
- [ ] Laravel config cache is cleared and rebuilt
- [ ] .env.production file has correct email settings
- [ ] Cron job has correct path to project
- [ ] Log files are writable
- [ ] Test email command works manually
- [ ] Check Laravel logs for email errors
- [ ] Verify cron job is running (check cron logs)

This comprehensive guide should resolve your production email issues and set up reliable automated stock alerts.