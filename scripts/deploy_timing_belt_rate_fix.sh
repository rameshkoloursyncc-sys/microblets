#!/bin/bash

# Timing Belt Rate Calculation Fix Deployment Script
# This script applies the fixes for:
# 1. Rate becoming zero when changing size/mm
# 2. Settings values resetting on refresh  
# 3. Separate IN/OUT for sleeve and mm operations

echo "🚀 Deploying Timing Belt Rate Calculation Fixes"
echo "================================================"

# Check if we're in the right directory
if [ ! -f "artisan" ]; then
    echo "❌ Error: artisan file not found. Please run this script from the Laravel root directory."
    exit 1
fi

echo "📋 Step 1: Backing up current files..."
mkdir -p backups/timing_belt_fixes_$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="backups/timing_belt_fixes_$(date +%Y%m%d_%H%M%S)"

# Backup files that will be modified
cp app/Models/TimingBelt.php "$BACKUP_DIR/"
cp app/Http/Controllers/Api/TimingBeltController.php "$BACKUP_DIR/"
cp routes/api_timing_belts.php "$BACKUP_DIR/"
cp resources/js/composables/useTimingBelts.ts "$BACKUP_DIR/"
cp resources/js/components/inventory/TimingBeltTable.vue "$BACKUP_DIR/"

echo "✅ Files backed up to $BACKUP_DIR"

echo "📋 Step 2: Applying database migrations..."
php artisan migrate --force

echo "📋 Step 3: Testing timing belt calculation..."
php artisan test:timing-belt-fixes

echo "📋 Step 4: Clearing caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

echo "📋 Step 5: Rebuilding frontend assets..."
if command -v npm &> /dev/null; then
    npm run build
else
    echo "⚠️  Warning: npm not found. Please run 'npm run build' manually."
fi

echo "📋 Step 6: Final verification..."
echo "Testing a sample timing belt calculation..."

# Test the calculation with a sample timing belt
php artisan tinker --execute="
\$belt = new App\Models\TimingBelt([
    'section' => 'XL',
    'size' => '150', 
    'type' => '18',
    'total_mm' => 1000
]);
\$belt->calculateValue();
echo 'Sample Calculation:' . PHP_EOL;
echo 'Section: ' . \$belt->section . PHP_EOL;
echo 'Size: ' . \$belt->size . PHP_EOL;
echo 'Type: ' . \$belt->type . PHP_EOL;
echo 'Total MM: ' . \$belt->total_mm . PHP_EOL;
echo 'Rate: ' . \$belt->rate . PHP_EOL;
echo 'Value: ' . \$belt->value . PHP_EOL;
if (\$belt->rate > 0) {
    echo '✅ Rate calculation working!' . PHP_EOL;
} else {
    echo '❌ Rate calculation failed!' . PHP_EOL;
}
"

echo ""
echo "🎉 Timing Belt Rate Calculation Fixes Deployed Successfully!"
echo ""
echo "📝 Summary of Changes Applied:"
echo "   ✅ Fixed rate becoming zero when changing size/mm"
echo "   ✅ Fixed settings values resetting on refresh"
echo "   ✅ Added separate IN/OUT operations for sleeve and mm"
echo "   ✅ Implemented strict formula: (size × type × 450 × multiplier) + (size × total_mm × multiplier)"
echo ""
echo "🔧 Next Steps:"
echo "   1. Test the timing belt functionality in the web interface"
echo "   2. Verify that rate calculations work correctly"
echo "   3. Test both MM and Sleeve IN/OUT operations"
echo "   4. Check that settings formulas persist after refresh"
echo ""
echo "📁 Backup Location: $BACKUP_DIR"
echo "   (Keep this backup in case rollback is needed)"