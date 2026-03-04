# Quick Deployment Commands - Dashboard Snapshot

## 🚀 Deployment Steps (Copy & Paste)

### 1. Build Frontend
```bash
npm run build
```

### 2. Run Migration
```bash
php artisan migrate
```

### 3. Test Snapshot Creation
```bash
php artisan dashboard:snapshot
```

### 4. Verify Snapshot
```bash
php artisan tinker --execute="echo \App\Models\DashboardSnapshot::count();"
```

---

## ⏰ Cron Job Setup

### Find Your PHP Path
```bash
which php
```

### cPanel Cron Job Settings
```
Minute: 1
Hour: 0
Day: *
Month: *
Weekday: *
Command: cd /home/USERNAME/public_html && /usr/local/bin/php artisan dashboard:snapshot >> /dev/null 2>&1
```

**Replace:**
- `USERNAME` with your cPanel username
- `/home/USERNAME/public_html` with your project path
- `/usr/local/bin/php` with your PHP path from `which php`

### Direct Crontab (SSH)
```bash
crontab -e
```

Add this line:
```
1 0 * * * cd /path/to/project && php artisan dashboard:snapshot >> /dev/null 2>&1
```

---

## ✅ Verification Commands

### Check Migration Status
```bash
php artisan migrate:status | grep dashboard_snapshots
```

### Check Snapshot Count
```bash
php artisan tinker --execute="echo \App\Models\DashboardSnapshot::count();"
```

### Check Latest Snapshot
```bash
php artisan tinker --execute="echo json_encode(\App\Models\DashboardSnapshot::latest()->first());"
```

### View All Snapshot Dates
```bash
php artisan tinker --execute="echo json_encode(\App\Models\DashboardSnapshot::pluck('snapshot_date')->toArray());"
```

---

## 🔧 Common PHP Paths on Shared Hosting

Try these in order:
```bash
/usr/local/bin/php
/usr/bin/php
/opt/cpanel/ea-php82/root/usr/bin/php
/opt/cpanel/ea-php81/root/usr/bin/php
/opt/cpanel/ea-php80/root/usr/bin/php
```

---

## 🐛 Troubleshooting

### Test Command Manually
```bash
cd /path/to/project && php artisan dashboard:snapshot
```

### Check Laravel Logs
```bash
tail -f storage/logs/laravel.log
```

### Fix Permissions
```bash
chmod -R 775 storage bootstrap/cache
```

### Check Database Connection
```bash
php artisan tinker --execute="echo DB::connection()->getPdo() ? 'Connected' : 'Not connected';"
```

---

## 🗑️ Cleanup (Optional)

### Delete Test Snapshots
```bash
php artisan tinker --execute="\App\Models\DashboardSnapshot::truncate();"
```

### Delete Old Snapshots (Keep Last 90 Days)
```bash
php artisan tinker --execute="\App\Models\DashboardSnapshot::where('snapshot_date', '<', now()->subDays(90))->delete();"
```

---

## 📋 Quick Checklist

- [ ] Run `npm run build`
- [ ] Upload files to server
- [ ] Run `php artisan migrate`
- [ ] Test `php artisan dashboard:snapshot`
- [ ] Verify snapshot created
- [ ] Set up cron job in cPanel
- [ ] Wait 24 hours and verify cron ran
- [ ] Test date picker in dashboard UI

---

## 🆘 Emergency Rollback

If something goes wrong:
```bash
php artisan migrate:rollback --step=1
```

This only removes the snapshot feature, your existing data is safe.
