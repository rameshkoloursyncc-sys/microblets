# Production Deployment Guide - Timing Belts Update

## Overview
This guide shows how to deploy the updated timing belts structure to production.

## Database Migration Steps

### 1. Backup Production Database (CRITICAL!)
```bash
# SSH into production server
ssh your-production-server

# Create backup
mysqldump -u your_db_user -p your_production_db > timing_belts_backup_$(date +%Y%m%d_%H%M%S).sql
```

### 2. Upload Code Changes
```bash
# On your local machine, upload the files
scp -r . your-server:/path/to/your/app/

# Or if using git
git push origin main
# Then on server: git pull origin main
```

### 3. Run Migration on Production
```bash
# SSH into production server
ssh your-production-server
cd /path/to/your/app

# Run the migration
php artisan migrate

# This will execute:
# database/migrations/2025_12_22_071239_update_timing_belts_table_structure.php
```

### 4. Clear Caches
```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Rebuild optimized files
php artisan config:cache
php artisan route:cache
```

### 5. Build Frontend Assets
```bash
# Install dependencies (if needed)
npm install

# Build production assets
npm run build
```

## Migration Details

The migration will:
1. **Drop columns**: `balance_stock`, `in_stock`, `out_stock`
2. **Add columns**: 
   - `mm` (decimal 10,2) - Individual piece length
   - `total_mm` (decimal 12,2) - Total inventory in mm
   - `in_mm` (decimal 12,2) - IN tracking
   - `out_mm` (decimal 12,2) - OUT tracking
3. **Update type column**: Default to "1 (FULL SLEEVE)"

## Data Migration (If Needed)

If you have existing data that needs to be converted:

```sql
-- Example: Convert existing balance_stock to total_mm
-- Run this BEFORE the migration if you want to preserve data
UPDATE timing_belts SET total_mm = balance_stock * 100 WHERE balance_stock > 0;
```

## Rollback Plan (If Something Goes Wrong)

```bash
# Rollback the migration
php artisan migrate:rollback --step=1

# Restore from backup
mysql -u your_db_user -p your_production_db < timing_belts_backup_YYYYMMDD_HHMMSS.sql
```

## Verification Steps

After deployment, verify:

1. **Check table structure**:
```sql
DESCRIBE timing_belts;
```

2. **Test API endpoints**:
```bash
curl -X GET "https://your-domain.com/api/timing-belts"
```

3. **Test frontend**: Visit timing belts page and verify:
   - Table shows: Section, Size, Type, MM, Total MM, Value, Remark, IN/OUT, Actions
   - Create/Edit functionality works
   - IN/OUT operations work

## New Table Structure

After migration, timing_belts table will have:
- `section` (string) - Belt section (XL, L, H, etc.)
- `size` (string) - Belt size
- `type` (string) - "1 (FULL SLEEVE)" or "2 (HALF SLEEVE)"
- `mm` (decimal) - Individual piece length in mm
- `total_mm` (decimal) - Total inventory in mm
- `in_mm` (decimal) - Total IN quantity tracking
- `out_mm` (decimal) - Total OUT quantity tracking
- `rate` (decimal) - Rate per mm
- `value` (decimal) - Calculated value (total_mm * rate)
- `reorder_level` (integer) - Minimum stock level
- `remark` (text) - Notes
- `created_by`, `updated_by` - User tracking
- `created_at`, `updated_at` - Timestamps

## Frontend Features

The updated timing belts interface includes:
- ✅ Section, Size, Type (1=FULL SLEEVE), MM, Total MM, Value columns
- ✅ IN/OUT operations with mm tracking
- ✅ Inline editing for all fields
- ✅ Create new timing belts
- ✅ Import/Export JSON and Excel
- ✅ Search and filtering
- ✅ Transaction history
- ✅ Real-time statistics

## Troubleshooting

### Migration Fails
- Check database permissions
- Verify table exists
- Check for foreign key constraints

### Frontend Errors
- Clear browser cache
- Check browser console for errors
- Verify assets are built correctly

### API Errors
- Check Laravel logs: `tail -f storage/logs/laravel.log`
- Verify database connection
- Check route caching