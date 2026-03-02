# Dashboard Snapshot System - Summary

## ✅ What Was Created

### 1. Database Table: `dashboard_snapshots`
Stores daily inventory snapshots with:
- **Finished Goods**: Total products, in stock, low stock, out of stock, total value
- **Finished Goods by Category**: Vee Belts, Cogged Belts, Poly Belts, TPU Belts, Timing Belts, Special Belts
- **Raw Materials**: Total materials, available, low stock, out of stock, total value
- **Raw Materials by Category** (13+ categories):
  - Carbon, Chemical, Oil, Others, Resin, Rubber, TPU
  - Fibre Glass Cord, Steel Wire, Packing Material, Open
  - Cord (All) - Combined value of all cord subsections
  - Fabric (All) - Combined value of all fabric subsections
- **Die Requirements**: JSON data of die needs by section
- **Total Alerts**: Combined low stock alerts count

### 2. Model: `DashboardSnapshot`
Laravel model with proper casts for dates, decimals, and JSON fields.

### 3. Command: `dashboard:snapshot`
Artisan command to generate snapshots:
```bash
# Create today's snapshot
php artisan dashboard:snapshot

# Create snapshot for specific date
php artisan dashboard:snapshot "2026-02-28"
```

### 4. Scheduled Task
Automatically runs daily at 00:01 AM (midnight) via Laravel scheduler.

## 📊 Current Snapshots

We've created 5 test snapshots:
- 2026-03-02 (today)
- 2026-03-01
- 2026-02-28
- 2026-02-27
- 2026-02-26

Each snapshot captures:
- Finished Goods Value: ₹39,526,226.43
- Raw Materials Value: ₹17,877,410.30

## 🔄 How It Runs Automatically

### Without Cron Job (Development)
Run this command in a terminal (keeps running):
```bash
php artisan schedule:work
```

### With Cron Job (Production - Recommended)
Add this single line to your server's crontab:
```bash
* * * * * cd /path/to/microbelts_ima && php artisan schedule:run >> /dev/null 2>&1
```

This runs every minute, and Laravel decides when to execute scheduled tasks.

**To set it up:**
```bash
crontab -e
# Add the line above
```

## 🎯 Benefits

1. **Fast Dashboard Loading**: Pre-calculated stats instead of real-time queries
2. **Historical Data**: Track inventory changes over time
3. **Date Filtering**: Dashboard can show data for any past date
4. **Trend Analysis**: Compare values across days/weeks/months
5. **Reporting**: Generate reports from historical snapshots

## 🔍 Verify It's Working

Check scheduled tasks:
```bash
php artisan schedule:list
```

View recent snapshots:
```bash
php artisan tinker --execute="App\Models\DashboardSnapshot::latest('snapshot_date')->take(5)->get(['snapshot_date', 'finished_total_value', 'raw_total_value']);"
```

Check logs:
```bash
tail -f storage/logs/dashboard-snapshots.log
```

## 📝 Next Steps

1. **Set up cron job** on production server (see SCHEDULER_SETUP.md)
2. **Update DashboardController** to use snapshots for date filtering
3. **Add date picker** to frontend dashboard
4. **Create trend charts** using historical snapshot data

## 🛠️ Maintenance

- Snapshots are created automatically daily
- Old snapshots are kept for historical analysis
- You can manually create snapshots for any date
- No cleanup needed - storage is minimal (~1KB per snapshot)

## 📂 Files Created

1. `database/migrations/2026_03_02_170217_create_dashboard_snapshots_table.php`
2. `app/Models/DashboardSnapshot.php`
3. `app/Console/Commands/CreateDailyDashboardSnapshot.php`
4. `routes/console.php` (updated with schedule)
5. `SCHEDULER_SETUP.md` (setup guide)
6. `SNAPSHOT_SUMMARY.md` (this file)
