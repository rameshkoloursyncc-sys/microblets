# 🚀 PRODUCTION DEPLOYMENT GUIDE
## Inventory Summary & Stock Alert System

### ⚠️ ZERO DOWNTIME DEPLOYMENT - NO DATA LOSS
**This deployment only ADDS features. All existing data is preserved.**

---

## 📋 PRE-DEPLOYMENT CHECKLIST

### 1. **Create Complete Backup**
```bash
# 1. Database Backup
mysqldump -u [username] -p [database_name] > backup_$(date +%Y%m%d_%H%M%S).sql

# 2. Files Backup
tar -czf production_backup_$(date +%Y%m%d_%H%M%S).tar.gz \
  --exclude='node_modules' \
  --exclude='vendor' \
  --exclude='storage/logs/*' \
  /path/to/production/microbelts/

# 3. Verify backups
ls -lh backup_*.sql production_backup_*.tar.gz
```

### 2. **Check Current System Status**
```bash
# Test database connection
php artisan tinker --execute="echo 'DB Status: ' . (DB::connection()->getPdo() ? 'OK' : 'FAILED');"

# Check current email configuration
php artisan tinker --execute="echo 'Mail Config: ' . config('mail.mailers.smtp.host');"

# Verify current cron jobs
crontab -l
```

---

## 🔄 DEPLOYMENT STEPS

### Step 1: **Upload Updated Files**

**Files to Upload (Safe - No Breaking Changes):**
```
app/Http/Controllers/Api/VeeBeltController.php     # Added alert reset logic
app/Services/SmartStockAlertService.php            # Added getInventoryValueSummary()
app/Services/ExcelExportService.php                # Added addInventoryValueSummary()
app/Console/Commands/SendDailyLowStockReport.php   # Added inventory integration
app/Models/VeeBelt.php                             # Fixed stockAlert relationship
resources/views/emails/smart-stock-report-excel.blade.php  # IST timezone
resources/views/emails/low-stock-report-excel.blade.php    # IST timezone
```

**Upload Command:**
```bash
# Using rsync (recommended)
rsync -avz --exclude='.env' --exclude='storage/' --exclude='vendor/' \
  --exclude='node_modules/' --exclude='.git/' \
  /local/microbelts/ user@server:/path/to/production/microbelts/

# Or upload individual files via SCP
scp app/Http/Controllers/Api/VeeBeltController.php user@server:/production/path/app/Http/Controllers/Api/
scp app/Services/SmartStockAlertService.php user@server:/production/path/app/Services/
scp app/Services/ExcelExportService.php user@server:/production/path/app/Services/
scp app/Console/Commands/SendDailyLowStockReport.php user@server:/production/path/app/Console/Commands/
scp app/Models/VeeBelt.php user@server:/production/path/app/Models/
```

### Step 2: **Update Dependencies & Clear Caches**
```bash
# Navigate to production directory
cd /path/to/production/microbelts

# Update composer dependencies (if needed)
composer install --no-dev --optimize-autoloader

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

### Step 3: **Set Correct Permissions**
```bash
# Fix storage and cache permissions
chmod -R 775 storage/
chmod -R 775 bootstrap/cache/
chown -R www-data:www-data storage/
chown -R www-data:www-data bootstrap/cache/

# Create temp directory for Excel files
mkdir -p storage/app/temp
chmod 775 storage/app/temp
chown www-data:www-data storage/app/temp
```

---

## 📧 EMAIL CONFIGURATION VERIFICATION

### **Check Current .env Settings:**
```bash
# View current email configuration
grep -E "^MAIL_|^LOW_STOCK_EMAIL" .env
```

### **Expected Configuration:**
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=incrypto09@gmail.com
MAIL_PASSWORD=tbmpmkrqnspnkubs
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="incrypto09@gmail.com"
MAIL_FROM_NAME="Microbelts Inventory System"

LOW_STOCK_EMAIL_RECIPIENTS="rameshnda09@gmail.com,ramesh.koloursyncc@gmail.com"
```

### **Test Email Configuration:**
```bash
# Test email sending
php artisan tinker --execute="
try {
    \$emails = explode(',', env('LOW_STOCK_EMAIL_RECIPIENTS'));
    Mail::raw('Production deployment test - ' . now(), function(\$message) use (\$emails) {
        foreach (\$emails as \$email) {
            \$message->to(trim(\$email));
        }
        \$message->subject('Microbelts System - Deployment Test');
    });
    echo 'Email test: SUCCESS - Sent to ' . count(\$emails) . ' recipients';
} catch (Exception \$e) {
    echo 'Email test: FAILED - ' . \$e->getMessage();
}
"
```

---

## ⏰ CRON JOB SETUP (5 PM IST Daily)

### **Method 1: Direct Cron Entry (Recommended)**
```bash
# Edit crontab
crontab -e

# Add this line for 5 PM IST daily stock reports
# This will automatically use emails from LOW_STOCK_EMAIL_RECIPIENTS in .env
0 17 * * * cd /path/to/production/microbelts && php artisan report:low-stock >/dev/null 2>&1

# Save and verify
crontab -l | grep "report:low-stock"
```

### **Method 2: Using Laravel Scheduler**
```bash
# Add Laravel scheduler to crontab (runs every minute)
* * * * * cd /path/to/production/microbelts && php artisan schedule:run >> /dev/null 2>&1

# Then add to app/Console/Kernel.php (if not already present):
```

**Add to `app/Console/Kernel.php`:**
```php
protected function schedule(Schedule $schedule)
{
    // Daily stock report at 5 PM IST
    $schedule->command('report:low-stock')
             ->dailyAt('17:00')
             ->timezone('Asia/Kolkata');
}
```

### **Method 3: Custom Cron Script (Advanced)**
```bash
# Create cron script
cat > /path/to/production/microbelts/daily-stock-report.sh << 'EOF'
#!/bin/bash
cd /path/to/production/microbelts
php artisan report:low-stock
EOF

# Make executable
chmod +x /path/to/production/microbelts/daily-stock-report.sh

# Add to crontab
0 17 * * * /path/to/production/microbelts/daily-stock-report.sh >/dev/null 2>&1
```

### **Cron Job Features:**
- ✅ **Automatic Email Pickup**: Uses `LOW_STOCK_EMAIL_RECIPIENTS` from .env
- ✅ **IST Timezone**: Reports generated in Indian Standard Time
- ✅ **Inventory Summary**: Includes complete inventory value breakdown
- ✅ **Excel Attachments**: Professional Excel reports with die requirements
- ✅ **Error Handling**: Graceful failure handling with logging

---

## 🧪 POST-DEPLOYMENT TESTING

### **1. Test New Features**
```bash
# Test inventory summary API
curl -H "Authorization: Bearer YOUR_TOKEN" \
  "https://yourdomain.com/api/dashboard/inventory-stats"

# Test smart alert system
php artisan tinker --execute="
\$service = new \App\Services\SmartStockAlertService();
\$data = \$service->getInventoryValueSummary();
echo 'Inventory Summary: ' . (!empty(\$data) ? 'SUCCESS' : 'FAILED') . PHP_EOL;
echo 'Total Value: ₹' . number_format(\$data['totals']['total_value'] ?? 0, 2) . PHP_EOL;
"

# Test VeeBelt alert reset functionality
php artisan tinker --execute="
\$veeBelt = \App\Models\VeeBelt::with('stockAlert')->first();
echo 'VeeBelt stockAlert relationship: ' . (\$veeBelt && method_exists(\$veeBelt, 'stockAlert') ? 'OK' : 'FAILED') . PHP_EOL;
"
```

### **2. Test Email System**
```bash
# Test daily report (manual trigger)
php artisan report:low-stock

# Test smart alert system
php artisan tinker --execute="
\$service = new \App\Services\SmartStockAlertService();
\$result = \$service->sendSmartAlerts();
var_dump(\$result);
"
```

### **3. Verify Cron Job**
```bash
# Check if cron is running
service cron status

# View cron logs (if available)
tail -f /var/log/cron.log | grep "report:low-stock"

# Test cron job manually
cd /path/to/production/microbelts && php artisan report:low-stock
```

---

## 📊 MONITORING & VERIFICATION

### **1. System Health Check**
```bash
# Comprehensive system check
php artisan tinker --execute="
echo '=== PRODUCTION HEALTH CHECK ===' . PHP_EOL;
echo 'Database: ' . (DB::connection()->getPdo() ? 'OK' : 'FAILED') . PHP_EOL;
echo 'Storage Writable: ' . (is_writable(storage_path()) ? 'OK' : 'FAILED') . PHP_EOL;
echo 'Temp Directory: ' . (is_dir(storage_path('app/temp')) ? 'OK' : 'FAILED') . PHP_EOL;
echo 'Email Config: ' . (config('mail.mailers.smtp.host') ? 'OK' : 'FAILED') . PHP_EOL;
echo 'Recipients: ' . env('LOW_STOCK_EMAIL_RECIPIENTS') . PHP_EOL;
"
```

### **2. Feature Verification Checklist**
- [ ] Dashboard loads with inventory summary
- [ ] Smart alerts include inventory data in Excel
- [ ] Daily reports include inventory summary
- [ ] VeeBelt IN/OUT operations reset alerts correctly
- [ ] IST timezone displays in email reports
- [ ] Cron job is scheduled and running
- [ ] Email recipients receive reports

### **3. Monitor Logs**
```bash
# Monitor application logs
tail -f storage/logs/laravel.log | grep -E "(ERROR|CRITICAL|stock|alert)"

# Monitor cron execution
tail -f /var/log/syslog | grep CRON

# Check email queue (if using queues)
php artisan queue:work --once
```

---

## 🔧 TROUBLESHOOTING

### **Common Issues & Solutions:**

#### **1. Email Not Sending**
```bash
# Check SMTP connection
telnet smtp.gmail.com 587

# Verify email configuration
php artisan config:clear
php artisan config:cache

# Test with specific email
php artisan report:low-stock --email=test@yourdomain.com
```

#### **2. Cron Job Not Running**
```bash
# Check cron service
sudo service cron restart
sudo service cron status

# Verify crontab entry
crontab -l | grep microbelts

# Check permissions
ls -la /path/to/production/microbelts/artisan
```

#### **3. Excel Generation Issues**
```bash
# Check temp directory
ls -la storage/app/temp/
chmod 775 storage/app/temp/

# Test Excel generation
php artisan tinker --execute="
\$service = new \App\Services\ExcelExportService();
echo 'ExcelExportService loaded: OK' . PHP_EOL;
"
```

#### **4. Permission Issues**
```bash
# Fix all permissions
sudo chown -R www-data:www-data /path/to/production/microbelts/
sudo chmod -R 755 /path/to/production/microbelts/
sudo chmod -R 775 /path/to/production/microbelts/storage/
sudo chmod -R 775 /path/to/production/microbelts/bootstrap/cache/
```

---

## 🔄 ROLLBACK PLAN (Emergency Only)

### **Quick Rollback Steps:**
```bash
# 1. Stop cron job
crontab -e  # Remove the report:low-stock line

# 2. Restore previous files
tar -xzf production_backup_YYYYMMDD_HHMMSS.tar.gz -C /

# 3. Clear caches
php artisan config:clear
php artisan route:clear
php artisan view:clear

# 4. Restart web server
sudo service nginx restart  # or apache2
```

---

## ✅ SUCCESS INDICATORS

**🎉 Deployment Successful When:**
- ✅ Dashboard shows inventory summary with belt-wise breakdown
- ✅ Smart alerts include inventory data in Excel attachments
- ✅ Daily 5 PM emails include inventory summary section
- ✅ VeeBelt IN/OUT operations reset alert status correctly
- ✅ Email timestamps show IST timezone
- ✅ Cron job executes daily at 5 PM IST
- ✅ Recipients receive emails at configured addresses
- ✅ No errors in application logs
- ✅ All existing functionality works unchanged

---

## 📞 SUPPORT & MAINTENANCE

### **Daily Monitoring:**
```bash
# Check daily at 5:30 PM IST if emails were sent
tail -20 storage/logs/laravel.log | grep -i "stock.*alert\|excel.*report"

# Weekly system health check
php artisan tinker --execute="
echo 'Weekly Health Check - ' . now() . PHP_EOL;
echo 'Total Inventory Value: ₹' . number_format(\App\Services\SmartStockAlertService::class::getInventoryValueSummary()['totals']['total_value'] ?? 0, 2) . PHP_EOL;
"
```

### **Email Recipients Management:**
To change email recipients, update `.env` file:
```env
LOW_STOCK_EMAIL_RECIPIENTS="new@email.com,another@email.com"
```
Then run: `php artisan config:cache`

---

**⚠️ IMPORTANT NOTES:**
- This deployment is 100% safe - only adds features
- No database migrations required
- All existing data preserved
- Cron job automatically uses .env email configuration
- IST timezone ensures reports are sent at correct local time
- Excel reports include comprehensive inventory summary matching dashboard

**🎯 RESULT:** Enhanced inventory management with automated daily reports including complete inventory value analysis!