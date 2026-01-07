#!/bin/bash

# Production Rollback Script for Timing Belt Fixes
# Use this script if the deployment causes issues in production

echo "🔄 Rolling Back Timing Belt Fixes in PRODUCTION"
echo "==============================================="

# Safety check
read -p "⚠️  Are you sure you want to ROLLBACK the timing belt fixes? (yes/no): " confirm
if [ "$confirm" != "yes" ]; then
    echo "❌ Rollback cancelled."
    exit 1
fi

# Check if we're in the right directory
if [ ! -f "artisan" ]; then
    echo "❌ Error: artisan file not found. Please run this script from the Laravel root directory."
    exit 1
fi

# Find the most recent backup
BACKUP_DIR=$(ls -td backups/timing_belt_fixes_production_* 2>/dev/null | head -1)

if [ -z "$BACKUP_DIR" ]; then
    echo "❌ Error: No backup directory found. Cannot rollback."
    echo "   Looking for: backups/timing_belt_fixes_production_*"
    exit 1
fi

echo "📁 Found backup directory: $BACKUP_DIR"

echo "📋 Step 1: Restoring files from backup..."

# Restore files if they exist in backup
if [ -f "$BACKUP_DIR/TimingBelt.php" ]; then
    cp "$BACKUP_DIR/TimingBelt.php" app/Models/
    echo "✅ Restored TimingBelt.php"
else
    echo "⚠️  TimingBelt.php backup not found"
fi

if [ -f "$BACKUP_DIR/TimingBeltController.php" ]; then
    cp "$BACKUP_DIR/TimingBeltController.php" app/Http/Controllers/Api/
    echo "✅ Restored TimingBeltController.php"
else
    echo "⚠️  TimingBeltController.php backup not found"
fi

if [ -f "$BACKUP_DIR/api_timing_belts.php" ]; then
    cp "$BACKUP_DIR/api_timing_belts.php" routes/
    echo "✅ Restored api_timing_belts.php"
else
    echo "⚠️  api_timing_belts.php backup not found"
fi

if [ -f "$BACKUP_DIR/useTimingBelts.ts" ]; then
    cp "$BACKUP_DIR/useTimingBelts.ts" resources/js/composables/
    echo "✅ Restored useTimingBelts.ts"
else
    echo "⚠️  useTimingBelts.ts backup not found"
fi

if [ -f "$BACKUP_DIR/TimingBeltTable.vue" ]; then
    cp "$BACKUP_DIR/TimingBeltTable.vue" resources/js/components/inventory/
    echo "✅ Restored TimingBeltTable.vue"
else
    echo "⚠️  TimingBeltTable.vue backup not found"
fi

echo "📋 Step 2: Clearing caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

echo "📋 Step 3: Rebuilding frontend assets..."
if command -v npm &> /dev/null; then
    npm run build
else
    echo "⚠️  Warning: npm not found. Please run 'npm run build' manually."
fi

echo "📋 Step 4: Testing rollback..."
# Simple test to see if the system is working
php artisan tinker --execute="
try {
    \$belt = new App\Models\TimingBelt();
    echo 'TimingBelt model loaded successfully' . PHP_EOL;
    echo '✅ Rollback appears successful' . PHP_EOL;
} catch (Exception \$e) {
    echo '❌ Error after rollback: ' . \$e->getMessage() . PHP_EOL;
}
"

echo ""
echo "🔄 Rollback Complete!"
echo "===================="
echo ""
echo "📝 What was rolled back:"
echo "   - TimingBelt model restored to previous version"
echo "   - TimingBeltController restored to previous version"
echo "   - API routes restored to previous version"
echo "   - Frontend files restored to previous version"
echo ""
echo "🔧 Next Steps:"
echo "   1. Test the timing belt functionality"
echo "   2. Verify that the system is working as before"
echo "   3. If issues persist, check the application logs"
echo "   4. Consider investigating the root cause of the deployment issue"
echo ""
echo "📁 Backup used: $BACKUP_DIR"
echo "   (This backup is preserved for reference)"
echo ""
echo "⚠️  IMPORTANT: Test all functionality to ensure rollback was successful!"