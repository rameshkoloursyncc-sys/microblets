# Laravel Scheduler Setup for Automatic Daily Snapshots

## How It Works

The dashboard snapshot command is scheduled to run automatically every day at 00:01 AM (midnight) using Laravel's built-in scheduler.

## Setup Options

### Option 1: Using System Cron (Recommended for Production)

Add this single cron entry to your server's crontab:

```bash
* * * * * cd /path/to/your/project && php artisan schedule:run >> /dev/null 2>&1
```

This runs every minute and Laravel's scheduler decides which commands to execute based on their schedule.

**To add it:**
```bash
crontab -e
```

Then add the line above (replace `/path/to/your/project` with your actual project path).

### Option 2: Using Laravel's Schedule Work Command (Development)

For local development or testing, you can run:

```bash
php artisan schedule:work
```

This keeps running in the terminal and executes scheduled tasks. Good for testing but not for production.

### Option 3: Using Supervisor (Production Alternative)

Create a supervisor config file `/etc/supervisor/conf.d/laravel-scheduler.conf`:

```ini
[program:laravel-scheduler]
process_name=%(program_name)s
command=php /path/to/your/project/artisan schedule:work
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/path/to/your/project/storage/logs/scheduler.log
```

Then:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-scheduler
```

## Scheduled Tasks

Currently scheduled:
1. **Dashboard Snapshot** - Runs daily at 00:01 AM
   - Command: `php artisan dashboard:snapshot`
   - Logs: `storage/logs/dashboard-snapshots.log`

2. **Low Stock Report** - Runs daily at 20:10 (8:10 PM)
   - Command: `php artisan report:low-stock`
   - Logs: `storage/logs/stock-alerts.log`

## Manual Execution

You can manually create snapshots for any date:

```bash
# Today's snapshot
php artisan dashboard:snapshot

# Specific date
php artisan dashboard:snapshot "2026-02-28"

# Yesterday
php artisan dashboard:snapshot "$(date -d 'yesterday' +%Y-%m-%d)"
```

## Verify Scheduler is Working

Check if scheduler is configured:
```bash
php artisan schedule:list
```

Test the scheduler (runs all due tasks):
```bash
php artisan schedule:test
```

## Logs

Check logs to verify snapshots are being created:
```bash
tail -f storage/logs/dashboard-snapshots.log
```

## Database

Snapshots are stored in the `dashboard_snapshots` table with:
- Daily inventory values (finished goods + raw materials)
- Stock counts (total, in stock, low stock, out of stock)
- Category-wise breakdown
- Die requirements
- Historical data for date filtering

## Troubleshooting

If snapshots aren't being created automatically:

1. Check if cron is running:
   ```bash
   sudo service cron status
   ```

2. Check crontab entry:
   ```bash
   crontab -l
   ```

3. Check Laravel logs:
   ```bash
   tail -f storage/logs/laravel.log
   ```

4. Manually test the command:
   ```bash
   php artisan dashboard:snapshot
   ```

5. Check scheduler configuration:
   ```bash
   php artisan schedule:list
   ```
