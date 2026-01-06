# Production Cron Job Setup Guide

## Overview
Laravel uses a single cron entry that runs every minute and handles all scheduled tasks internally.

## Step 1: Access Your Server

### Via SSH
```bash
ssh username@your-server-ip
cd /path/to/your/laravel/project
```

### Via cPanel/Hosting Panel
Most hosting providers have a "Cron Jobs" section in their control panel.

## Step 2: Set Up Laravel Scheduler

### Method 1: Standard Linux Cron (Recommended)

1. **Open crontab editor**:
```bash
crontab -e
```

2. **Add Laravel scheduler entry**:
```bash
# Laravel Scheduler - Runs every minute
* * * * * cd /path/to/your/project && php artisan schedule:run >> /dev/null 2>&1
```

3. **Replace `/path/to/your/project`** with your actual project path:
```bash
# Example for typical hosting
* * * * * cd /home/username/public_html && php artisan schedule:run >> /dev/null 2>&1

# Example for VPS
* * * * * cd /var/www/html/your-project && php artisan schedule:run >> /dev/null 2>&1
```

### Method 2: cPanel Cron Jobs

1. **Login to cPanel**
2. **Go to "Cron Jobs"**
3. **Add New Cron Job**:
   - **Minute**: `*`
   - **Hour**: `*`
   - **Day**: `*`
   - **Month**: `*`
   - **Weekday**: `*`
   - **Command**: `/usr/local/bin/php /home/username/public_html/artisan schedule:run`

### Method 3: Shared Hosting (Alternative)

If your hosting doesn't support the scheduler, create a direct cron job:

```bash
# Direct command for stock alerts (runs daily at 8 AM)
0 8 * * * cd /path/to/your/project && php artisan report:low-stock >> /dev/null 2>&1
```

## Step 3: Verify Cron Setup

### Check if cron is running:
```bash
# List current cron jobs
crontab -l

# Check cron service status
sudo systemctl status cron
# or
sudo service cron status
```

### Test the scheduler:
```bash
# Run scheduler manually to test
php artisan schedule:run

# Check scheduled tasks
php artisan schedule:list
```

## Step 4: Monitor Cron Jobs

### Enable Logging (Optional)
```bash
# Add logging to cron entry
* * * * * cd /path/to/your/project && php artisan schedule:run >> /var/log/laravel-scheduler.log 2>&1
```

### Check Laravel Logs
```bash
# View recent logs
tail -f storage/logs/laravel.log

# Check for scheduler errors
grep -i "schedule\|cron" storage/logs/laravel.log
```

## Step 5: Production Environment Setup

### 1. Environment Configuration
Ensure your production `.env` file has:
```bash
APP_ENV=production
APP_DEBUG=false
MAIL_MAILER=smtp
# ... your mail settings
LOW_STOCK_EMAIL_RECIPIENTS="admin@company.com,manager@company.com"
```

### 2. Optimize Laravel for Production
```bash
# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Optimize autoloader
composer install --optimize-autoloader --no-dev
```

### 3. Set Proper Permissions
```bash
# Set ownership (replace www-data with your web server user)
sudo chown -R www-data:www-data /path/to/your/project

# Set permissions
sudo chmod -R 755 /path/to/your/project
sudo chmod -R 775 /path/to/your/project/storage
sudo chmod -R 775 /path/to/your/project/bootstrap/cache
```

## Common Issues & Solutions

### Issue 1: "Command not found"
**Problem**: PHP path not found in cron environment

**Solution**: Use full PHP path
```bash
# Find PHP path
which php
# or
whereis php

# Use full path in cron
* * * * * cd /path/to/project && /usr/bin/php artisan schedule:run >> /dev/null 2>&1
```

### Issue 2: "Permission denied"
**Problem**: Cron can't access files

**Solution**: Check file permissions and ownership
```bash
# Fix permissions
chmod +x /path/to/project/artisan
chown -R www-data:www-data /path/to/project
```

### Issue 3: "Class not found"
**Problem**: Autoloader not optimized

**Solution**: Run composer optimization
```bash
composer dump-autoload --optimize
php artisan config:cache
```

### Issue 4: Environment variables not loaded
**Problem**: Cron doesn't load .env file

**Solution**: Ensure .env is in project root and readable
```bash
# Check .env file exists and is readable
ls -la /path/to/project/.env
```

## Testing in Production

### 1. Test Email Configuration
```bash
# Test basic email
php artisan test:email admin@company.com

# Test stock alert
php artisan report:low-stock --email=admin@company.com
```

### 2. Test Scheduler
```bash
# Run scheduler manually
php artisan schedule:run

# Check what's scheduled
php artisan schedule:list
```

### 3. Monitor First Few Days
```bash
# Check logs for any issues
tail -f storage/logs/laravel.log

# Check if emails are being sent
grep -i "stock alert\|email" storage/logs/laravel.log
```

## Advanced Production Setup

### 1. Queue Jobs (Optional)
For better performance, use queues for email sending:

```bash
# Install Redis or database queue
composer require predis/predis

# Update .env
QUEUE_CONNECTION=redis
# or
QUEUE_CONNECTION=database
```

Update command to use queues:
```php
// In SendDailyLowStockReport.php
Mail::to($email)->queue(new LowStockReport($lowStockData));
```

### 2. Monitoring & Alerts
Set up monitoring to ensure cron jobs are running:

```bash
# Create a heartbeat endpoint
php artisan make:command HeartbeatCheck
```

### 3. Multiple Environments
Use different schedules for different environments:

```php
// In routes/console.php
if (app()->environment('production')) {
    Schedule::command('report:low-stock')
        ->dailyAt('08:00')
        ->timezone('Asia/Kolkata');
} else {
    // More frequent for testing
    Schedule::command('report:low-stock')
        ->hourly();
}
```

## Hosting-Specific Instructions

### Shared Hosting (cPanel)
- Use cPanel Cron Jobs interface
- PHP path usually: `/usr/local/bin/php`
- Project path usually: `/home/username/public_html`

### VPS/Dedicated Server
- Full control over cron
- Can use system-level monitoring
- Consider using supervisor for queue workers

### Cloud Hosting (AWS, DigitalOcean, etc.)
- Use their cron/scheduler services
- Consider AWS EventBridge or similar
- Set up proper IAM permissions

## Security Considerations

1. **Limit cron output**: Use `>> /dev/null 2>&1` to prevent email spam
2. **Secure .env file**: Ensure it's not web-accessible
3. **Use HTTPS**: For all external API calls
4. **Monitor logs**: Regularly check for suspicious activity
5. **Backup strategy**: Ensure cron jobs don't interfere with backups

## Troubleshooting Commands

```bash
# Check if cron daemon is running
ps aux | grep cron

# Check cron logs (varies by system)
tail -f /var/log/cron
# or
tail -f /var/log/syslog | grep cron

# Test cron job manually
cd /path/to/project && php artisan schedule:run

# Check Laravel scheduler status
php artisan schedule:list

# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```