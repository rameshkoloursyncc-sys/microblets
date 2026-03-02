# Production Deployment Guide - Dashboard Snapshot System

## Pre-Deployment Checklist

### 1. Verify Files Are Ready
- ✅ Migration file: `database/migrations/2026_03_02_170217_create_dashboard_snapshots_table.php`
- ✅ Model: `app/Models/DashboardSnapshot.php`
- ✅ Command: `app/Console/Commands/CreateDailyDashboardSnapshot.php`
- ✅ Controller: `app/Http/Controllers/Api/DashboardController.php` (updated)
- ✅ Frontend: `resources/js/components/inventory/InventoryApp.vue` (updated)
- ✅ Routes: `routes/api.php` and `routes/console.php` (updated)
- ❌ Test seeder: DELETED (not needed for production)

### 2. Build Frontend Assets
```bash
npm run build
```

## Deployment Steps

### Step 1: Upload Files to Production Server
Upload these files/folders to your production server:
- `app/`
- `database/migrations/`
- `routes/`
- `resources/js/` (if not already built)
- `public/build/` (built assets from npm run build)

### Step 2: Run Migration
SSH into your server or use your hosting control panel terminal:

```bash
# Navigate to your Laravel project directory
cd /path/to/your/project

# Run the migration
php artisan migrate

# Expected output:
# Running migrations.
# 2026_03_02_170217_create_dashboard_snapshots_table ............... DONE
```

**Important:** This migration is safe - it only creates a new table, doesn't modify existing data.

### Step 3: Verify Migration
```bash
# Check if table was created
php artisan tinker --execute="echo \App\Models\DashboardSnapshot::count();"

# Expected output: 0 (no snapshots yet)
```

### Step 4: Create First Snapshot (Manual Test)
```bash
# Run the snapshot command manually to test
php artisan dashboard:snapshot

# Expected output:
# ✅ Dashboard snapshot created successfully for 2026-03-02
```

### Step 5: Verify First Snapshot
```bash
# Check if snapshot was created
php artisan tinker --execute="echo json_encode(\App\Models\DashboardSnapshot::latest()->first()->snapshot_date);"

# Should show today's date
```

## Scheduling on Shared Hosting

Since shared hosting doesn't support Laravel's scheduler directly, you need to use cron jobs.

### Option 1: cPanel Cron Jobs (Most Common)

1. **Log into cPanel**
2. **Find "Cron Jobs" in Advanced section**
3. **Add New Cron Job:**

```
Minute: 1
Hour: 0
Day: *
Month: *
Weekday: *
Command: cd /home/username/public_html && /usr/local/bin/php artisan dashboard:snapshot >> /dev/null 2>&1
```

**Replace:**
- `/home/username/public_html` with your actual project path
- `/usr/local/bin/php` with your PHP path (find it with `which php` command)

**Common PHP paths on shared hosting:**
- `/usr/local/bin/php`
- `/usr/bin/php`
- `/opt/cpanel/ea-php82/root/usr/bin/php` (for PHP 8.2)
- `/opt/cpanel/ea-php81/root/usr/bin/php` (for PHP 8.1)

### Option 2: Direct Cron Command (Alternative)

If you have SSH access, you can edit crontab directly:

```bash
# Open crontab editor
crontab -e

# Add this line (runs daily at 00:01 AM)
1 0 * * * cd /path/to/your/project && php artisan dashboard:snapshot >> /dev/null 2>&1

# Save and exit
```

### Option 3: Using Laravel Scheduler (If Supported)

Some shared hosts allow you to run Laravel's scheduler. If so:

```bash
# Add this single cron job (runs every minute)
* * * * * cd /path/to/your/project && php artisan schedule:run >> /dev/null 2>&1
```

This will run Laravel's scheduler which already has the snapshot command configured in `routes/console.php`.

### Option 4: Hosting Control Panel Cron

For other control panels (Plesk, DirectAdmin, etc.):

1. Navigate to Scheduled Tasks / Cron Jobs
2. Create new task
3. Set schedule: Daily at 00:01 (or 12:01 AM)
4. Command: `cd /path/to/project && php artisan dashboard:snapshot`

## Verifying Cron Job is Working

### Method 1: Check Logs
Create a log file to verify cron execution:

```bash
# Update cron command to log output
1 0 * * * cd /path/to/project && php artisan dashboard:snapshot >> /path/to/project/storage/logs/snapshot-cron.log 2>&1
```

Then check the log file after the scheduled time.

### Method 2: Check Database
```bash
# Check snapshot count (should increase daily)
php artisan tinker --execute="echo \App\Models\DashboardSnapshot::count();"

# Check latest snapshot date
php artisan tinker --execute="echo \App\Models\DashboardSnapshot::latest()->first()->snapshot_date;"
```

### Method 3: Dashboard UI
- Log into your application
- Go to Dashboard
- Select a date from the date picker
- If data appears, snapshots are being created

## Troubleshooting

### Issue: Migration Fails
```bash
# Check if table already exists
php artisan tinker --execute="echo Schema::hasTable('dashboard_snapshots') ? 'exists' : 'not exists';"

# If exists, migration already ran
```

### Issue: Cron Job Not Running
1. **Check PHP path:**
   ```bash
   which php
   # Use the output path in your cron command
   ```

2. **Check permissions:**
   ```bash
   chmod +x artisan
   ```

3. **Test command manually:**
   ```bash
   cd /path/to/project && php artisan dashboard:snapshot
   ```

4. **Check cron logs:**
   ```bash
   # On most systems
   tail -f /var/log/cron
   
   # Or check mail
   mail
   ```

### Issue: Command Runs But No Data
```bash
# Check for errors
php artisan dashboard:snapshot

# Check Laravel logs
tail -f storage/logs/laravel.log
```

### Issue: Permission Denied
```bash
# Fix storage permissions
chmod -R 775 storage
chmod -R 775 bootstrap/cache

# Fix ownership (replace 'username' with your user)
chown -R username:username storage
chown -R username:username bootstrap/cache
```

## Production Environment Variables

Make sure your `.env` file has:

```env
APP_ENV=production
APP_DEBUG=false
LOG_CHANNEL=stack
LOG_LEVEL=error

# Database settings
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

## Post-Deployment Verification

### 1. Check Migration Status
```bash
php artisan migrate:status

# Should show:
# | Ran? | Migration | Batch |
# | Yes  | 2026_03_02_170217_create_dashboard_snapshots_table | X |
```

### 2. Test API Endpoints
```bash
# Test snapshot endpoint (should return empty or latest snapshot)
curl https://yourdomain.com/api/dashboard/snapshot

# Test available dates endpoint
curl https://yourdomain.com/api/dashboard/available-dates
```

### 3. Test Frontend
1. Log into dashboard
2. Open browser console (F12)
3. Select a date from date picker
4. Check console for errors
5. Verify data displays correctly

## Maintenance

### View All Snapshots
```bash
php artisan tinker --execute="echo json_encode(\App\Models\DashboardSnapshot::select('id', 'snapshot_date')->get());"
```

### Delete Old Snapshots (Optional)
If you want to keep only last 90 days:

```bash
php artisan tinker --execute="\App\Models\DashboardSnapshot::where('snapshot_date', '<', now()->subDays(90))->delete();"
```

### Manual Snapshot Creation
```bash
# Create snapshot for today
php artisan dashboard:snapshot

# Create snapshot for specific date (if needed for backfill)
# Note: This will use current data, not historical data
php artisan dashboard:snapshot
```

## Rollback Plan (If Needed)

If something goes wrong:

```bash
# Rollback the migration
php artisan migrate:rollback --step=1

# This will drop the dashboard_snapshots table
# Your existing data is safe - this only affects the new snapshot feature
```

## Summary

**Migration Command:**
```bash
php artisan migrate
```

**Cron Job (Daily at 00:01 AM):**
```bash
1 0 * * * cd /path/to/project && php artisan dashboard:snapshot >> /dev/null 2>&1
```

**Verification:**
```bash
php artisan tinker --execute="echo \App\Models\DashboardSnapshot::count();"
```

## Support

If you encounter issues:
1. Check Laravel logs: `storage/logs/laravel.log`
2. Check cron logs: `/var/log/cron` or cPanel cron email
3. Test command manually: `php artisan dashboard:snapshot`
4. Verify database connection: `php artisan tinker`

## Notes

- Snapshots are created at 00:01 AM daily (configurable in cron)
- Each snapshot stores complete dashboard state
- Old snapshots can be deleted manually if needed
- Frontend automatically falls back to real-time data if snapshot not found
- Date pickers work independently for Finished Goods and Raw Materials
