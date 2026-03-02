# cPanel Cron Job Setup Guide - Step by Step

## Visual Guide for Setting Up Daily Snapshot Cron Job

### Step 1: Log into cPanel
1. Go to your hosting control panel (usually `yourdomain.com/cpanel` or `yourdomain.com:2083`)
2. Enter your cPanel username and password

### Step 2: Find Cron Jobs
1. Scroll down to the **"Advanced"** section
2. Click on **"Cron Jobs"** icon

### Step 3: Configure Cron Job Settings

You'll see a form with these fields:

#### Common Settings (Dropdown)
- Select: **"Once Per Day (0 0 * * *)"** or **"Custom"**

#### Minute
```
1
```
(Runs at 1 minute past the hour)

#### Hour
```
0
```
(Runs at midnight/12 AM)

#### Day
```
*
```
(Every day)

#### Month
```
*
```
(Every month)

#### Weekday
```
*
```
(Every day of the week)

#### Command
```bash
cd /home/USERNAME/public_html && /usr/local/bin/php artisan dashboard:snapshot >> /dev/null 2>&1
```

**IMPORTANT: Replace these values:**
- `USERNAME` → Your cPanel username (e.g., `microbe12`)
- `/home/USERNAME/public_html` → Your Laravel project path
- `/usr/local/bin/php` → Your PHP path (see below)

### Step 4: Find Your PHP Path

#### Method 1: SSH Terminal (Recommended)
```bash
which php
```

#### Method 2: cPanel Terminal
1. In cPanel, find **"Terminal"** in Advanced section
2. Type: `which php`
3. Copy the output (e.g., `/usr/local/bin/php`)

#### Method 3: Common Paths
Try these paths in order:
- `/usr/local/bin/php` (most common)
- `/usr/bin/php`
- `/opt/cpanel/ea-php82/root/usr/bin/php` (PHP 8.2)
- `/opt/cpanel/ea-php81/root/usr/bin/php` (PHP 8.1)

### Step 5: Find Your Project Path

#### Method 1: File Manager
1. Open **"File Manager"** in cPanel
2. Navigate to your Laravel project
3. Look at the path in the address bar
4. Common paths:
   - `/home/username/public_html`
   - `/home/username/domains/yourdomain.com/public_html`
   - `/home/username/microbelts`

#### Method 2: SSH/Terminal
```bash
pwd
```
(Shows current directory)

### Step 6: Complete Command Examples

#### Example 1: Standard Setup
```bash
cd /home/microbe12/public_html && /usr/local/bin/php artisan dashboard:snapshot >> /dev/null 2>&1
```

#### Example 2: Subdomain/Addon Domain
```bash
cd /home/microbe12/domains/inventory.microbelts.com/public_html && /usr/local/bin/php artisan dashboard:snapshot >> /dev/null 2>&1
```

#### Example 3: With Specific PHP Version
```bash
cd /home/microbe12/public_html && /opt/cpanel/ea-php82/root/usr/bin/php artisan dashboard:snapshot >> /dev/null 2>&1
```

#### Example 4: With Logging (For Testing)
```bash
cd /home/microbe12/public_html && /usr/local/bin/php artisan dashboard:snapshot >> /home/microbe12/snapshot-cron.log 2>&1
```

### Step 7: Add the Cron Job
1. Fill in all the fields as shown above
2. Click **"Add New Cron Job"** button
3. You should see a success message

### Step 8: Verify Cron Job Was Added

Scroll down to **"Current Cron Jobs"** section. You should see:

```
Minute: 1
Hour: 0
Day: *
Month: *
Weekday: *
Command: cd /home/USERNAME/public_html && /usr/local/bin/php artisan dashboard:snapshot >> /dev/null 2>&1
```

## Testing Your Cron Job

### Immediate Test (Don't Wait 24 Hours)

#### Method 1: Run Command Manually via SSH
```bash
cd /home/USERNAME/public_html
php artisan dashboard:snapshot
```

Expected output:
```
✅ Dashboard snapshot created successfully for 2026-03-02
```

#### Method 2: Check if Command Works
```bash
cd /home/USERNAME/public_html && /usr/local/bin/php artisan dashboard:snapshot
```

#### Method 3: Verify Snapshot in Database
```bash
cd /home/USERNAME/public_html
php artisan tinker --execute="echo \App\Models\DashboardSnapshot::count();"
```

Should show: `1` (or more if you ran it multiple times)

### Wait for Automatic Run

1. **Wait until 00:01 AM** (12:01 AM) the next day
2. **Check if new snapshot was created:**
   ```bash
   php artisan tinker --execute="echo \App\Models\DashboardSnapshot::count();"
   ```
3. Count should increase by 1 each day

## Troubleshooting

### Issue: Cron Job Not Running

#### Check 1: Email Notifications
- cPanel sends email when cron jobs fail
- Check your cPanel email or the email associated with your account

#### Check 2: Test Command Manually
```bash
cd /home/USERNAME/public_html
php artisan dashboard:snapshot
```

If this works but cron doesn't, the issue is with the cron command.

#### Check 3: Verify PHP Path
```bash
which php
```

Update your cron command with the correct path.

#### Check 4: Check Permissions
```bash
cd /home/USERNAME/public_html
chmod +x artisan
chmod -R 775 storage
chmod -R 775 bootstrap/cache
```

### Issue: Permission Denied

```bash
cd /home/USERNAME/public_html
chown -R USERNAME:USERNAME storage
chown -R USERNAME:USERNAME bootstrap/cache
```

Replace `USERNAME` with your cPanel username.

### Issue: Command Not Found

Make sure you're using the full path to PHP:
```bash
# Wrong
php artisan dashboard:snapshot

# Correct
/usr/local/bin/php artisan dashboard:snapshot
```

## Alternative: Using Email for Logs

If you want to receive email notifications:

```bash
cd /home/USERNAME/public_html && /usr/local/bin/php artisan dashboard:snapshot
```

Remove the `>> /dev/null 2>&1` part. You'll get an email each time the cron runs.

## Alternative: Using Log File

To save output to a log file:

```bash
cd /home/USERNAME/public_html && /usr/local/bin/php artisan dashboard:snapshot >> /home/USERNAME/snapshot-cron.log 2>&1
```

Then check the log:
```bash
cat /home/USERNAME/snapshot-cron.log
```

## Editing Existing Cron Job

1. Go to cPanel → Cron Jobs
2. Scroll to **"Current Cron Jobs"**
3. Find your snapshot cron job
4. Click **"Edit"** button
5. Make changes
6. Click **"Edit Line"** to save

## Deleting Cron Job

1. Go to cPanel → Cron Jobs
2. Scroll to **"Current Cron Jobs"**
3. Find your snapshot cron job
4. Click **"Delete"** button
5. Confirm deletion

## Best Practices

1. **Test manually first** before relying on cron
2. **Use logging initially** to debug issues
3. **Remove logging** once working (to avoid large log files)
4. **Check daily** for the first week to ensure it's working
5. **Set up monitoring** (check snapshot count weekly)

## Quick Reference

**Cron Schedule:**
- `1 0 * * *` = Daily at 00:01 AM (12:01 AM)
- `0 1 * * *` = Daily at 01:00 AM (1:00 AM)
- `30 2 * * *` = Daily at 02:30 AM (2:30 AM)

**Command Template:**
```bash
cd /home/USERNAME/PROJECT_PATH && /path/to/php artisan dashboard:snapshot >> /dev/null 2>&1
```

**Verification:**
```bash
php artisan tinker --execute="echo \App\Models\DashboardSnapshot::count();"
```

## Summary Checklist

- [ ] Found PHP path using `which php`
- [ ] Found project path using File Manager or `pwd`
- [ ] Created cron job in cPanel
- [ ] Set schedule to `1 0 * * *`
- [ ] Entered correct command with paths
- [ ] Saved cron job
- [ ] Tested command manually
- [ ] Verified snapshot was created
- [ ] Waiting for automatic run at 00:01 AM
- [ ] Will check tomorrow if count increased

## Need Help?

If you're stuck:
1. Take a screenshot of your cPanel Cron Jobs page
2. Run `which php` and share the output
3. Run `pwd` from your project directory and share the output
4. Try running the command manually and share any error messages
