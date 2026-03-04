# 🚀 SAFE PRODUCTION MIGRATION GUIDE
## Inventory Summary & Excel Reports Update

### ⚠️ CRITICAL: This migration preserves ALL existing data
**No data will be lost. Only new features are being added.**

---

## 📋 PRE-MIGRATION CHECKLIST

### 1. **Backup Production Database**
```bash
# Create full database backup
mysqldump -u [username] -p [database_name] > backup_$(date +%Y%m%d_%H%M%S).sql

# Verify backup file exists and has content
ls -lh backup_*.sql
```

### 2. **Backup Production Files**
```bash
# Create backup of current production code
tar -czf production_backup_$(date +%Y%m%d_%H%M%S).tar.gz /path/to/production/microbelts/

# Backup critical config files
cp .env .env.backup.$(date +%Y%m%d_%H%M%S)
cp -r storage/app storage/app.backup.$(date +%Y%m%d_%H%M%S)
```

### 3. **Test Current System**
```bash
# Verify current system is working
php artisan route:list | grep dashboard
php artisan tinker --execute="echo 'System check: ' . (DB::connection()->getPdo() ? 'DB OK' : 'DB FAIL');"
```

---

## 🔄 MIGRATION STEPS

### Step 1: **Upload New Code (Safe - No Breaking Changes)**

**Files to Upload:**
```
app/Services/SmartStockAlertService.php     # Added getInventoryValueSummary() method
app/Services/ExcelExportService.php         # Added addInventoryValueSummary() method  
app/Console/Commands/SendDailyLowStockReport.php  # Added inventory data integration
resources/views/emails/smart-stock-report-excel.blade.php  # IST timezone update
resources/views/emails/low-stock-report-excel.blade.php    # IST timezone update
```

**Upload Method:**
```bash
# Using rsync (recommended)
rsync -avz --exclude='.env' --exclude='storage/' --exclude='vendor/' \
  /local/path/microbelts/ user@server:/path/to/production/microbelts/

# Or using SCP for individual files
scp app/Services/SmartStockAlertService.php user@server:/path/to/production/microbelts/app/Services/
scp app/Services/ExcelExportService.php user@server:/path/to/production/microbelts/app/Services/
# ... repeat for other files
```

### Step 2: **Update Dependencies (If Needed)**
```bash
# On production server
cd /path/to/production/microbelts
composer install --no-dev --optimize-autoloader
```

### Step 3: **Clear Caches (Safe)**
```bash
# Clear all caches
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# Rebuild optimized caches
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Step 4: **Test New Features**
```bash
# Test inventory summary API
curl -H "Authorization: Bearer YOUR_TOKEN" \
  "https://yourdomain.com/api/dashboard/inventory-stats"

# Test smart alert system
php artisan tinker --execute="
\$service = new \App\Services\SmartStockAlertService();
\$data = \$service->getInventoryValueSummary();
echo 'Inventory data: ' . (!empty(\$data) ? 'SUCCESS' : 'FAILED');
"

# Test email system (use test email)
php artisan report:low-stock --email=test@yourdomain.com
```

---

## 🕐 CRON JOB SETUP (5 PM IST Daily)

### Update Crontab:
```bash
# Edit crontab
crontab -e

# Add this line for 5 PM IST daily reports
0 17 * * * cd /path/to/production/microbelts && php artisan report:low-stock >/dev/null 2>&1

# Verify cron is set
crontab -l | grep report:low-stock
```

### Alternative: Using Laravel Scheduler
```bash
# Add to crontab (runs Laravel scheduler every minute)
* * * * * cd /path/to/production/microbelts && php artisan schedule:run >> /dev/null 2>&1
```

---

## 🧪 TESTING CHECKLIST

### 1. **Verify Existing Functionality**
- [ ] Dashboard loads correctly
- [ ] All belt types display properly
- [ ] Stock alerts work as before
- [ ] User authentication works
- [ ] All CRUD operations work

### 2. **Test New Features**
- [ ] Inventory summary appears in dashboard
- [ ] Smart alerts include inventory data
- [ ] Daily reports include inventory summary
- [ ] Excel files generate correctly
- [ ] Email sending works
- [ ] IST timezone displays correctly

### 3. **Performance Check**
```bash
# Check response times
curl -w "@curl-format.txt" -o /dev/null -s "https://yourdomain.com/api/dashboard/inventory-stats"

# Monitor logs for errors
tail -f storage/logs/laravel.log
```

---

## 📧 EMAIL CONFIGURATION VERIFICATION

### Current Settings (from .env):
```
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=incrypto09@gmail.com
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="incrypto09@gmail.com"
MAIL_FROM_NAME="Microbelts Inventory System"

LOW_STOCK_EMAIL_RECIPIENTS="rameshnda09@gmail.com,ramesh.koloursyncc@gmail.com"
```

### Test Email Sending:
```bash
# Test email configuration
php artisan tinker --execute="
try {
    Mail::raw('Test email from production', function(\$message) {
        \$message->to('rameshnda09@gmail.com')->subject('Production Test');
    });
    echo 'Email test: SUCCESS';
} catch (Exception \$e) {
    echo 'Email test: FAILED - ' . \$e->getMessage();
}
"
```

---

## 🔧 TROUBLESHOOTING

### Common Issues & Solutions:

#### 1. **"Class not found" errors**
```bash
# Regenerate autoloader
composer dump-autoload --optimize
```

#### 2. **Permission issues**
```bash
# Fix storage permissions
chmod -R 775 storage/
chmod -R 775 bootstrap/cache/
chown -R www-data:www-data storage/
chown -R www-data:www-data bootstrap/cache/
```

#### 3. **Email not sending**
```bash
# Check mail configuration
php artisan tinker --execute="dd(config('mail'));"

# Test SMTP connection
telnet smtp.gmail.com 587
```

#### 4. **Excel generation fails**
```bash
# Check temp directory permissions
mkdir -p storage/app/temp
chmod 775 storage/app/temp
```

---

## 🔄 ROLLBACK PLAN (If Needed)

### Quick Rollback Steps:
```bash
# 1. Restore previous code
tar -xzf production_backup_YYYYMMDD_HHMMSS.tar.gz -C /

# 2. Restore database (if needed)
mysql -u [username] -p [database_name] < backup_YYYYMMDD_HHMMSS.sql

# 3. Clear caches
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

---

## ✅ POST-MIGRATION VERIFICATION

### 1. **System Health Check**
```bash
# Run comprehensive test
php artisan tinker --execute="
echo '=== SYSTEM HEALTH CHECK ===' . PHP_EOL;
echo 'Database: ' . (DB::connection()->getPdo() ? 'OK' : 'FAILED') . PHP_EOL;
echo 'Cache: ' . (Cache::store()->getStore() ? 'OK' : 'FAILED') . PHP_EOL;
echo 'Storage: ' . (is_writable(storage_path()) ? 'OK' : 'FAILED') . PHP_EOL;
"
```

### 2. **Feature Verification**
- [ ] Send test smart alert email
- [ ] Verify inventory summary in Excel
- [ ] Check IST timezone in emails
- [ ] Confirm cron job is scheduled
- [ ] Monitor first automated report at 5 PM

### 3. **Monitor for 24 Hours**
```bash
# Monitor logs
tail -f storage/logs/laravel.log | grep -E "(ERROR|CRITICAL|ALERT)"

# Check system resources
top -p $(pgrep -f "php-fpm\|apache\|nginx")
```

---

## 📞 SUPPORT CONTACTS

**If issues occur:**
1. Check logs: `storage/logs/laravel.log`
2. Verify .env configuration
3. Test database connectivity
4. Check file permissions

**Emergency Rollback:** Use the backup files created in pre-migration steps.

---

## 🎉 SUCCESS INDICATORS

✅ **Migration Successful When:**
- Dashboard loads with inventory summary
- Smart alerts include inventory data in Excel
- Daily 5 PM emails include inventory summary
- All existing functionality works unchanged
- No errors in logs
- Email recipients receive reports with IST timestamps

---

**⚠️ IMPORTANT: This migration only ADDS features. No existing data or functionality is modified or removed.**