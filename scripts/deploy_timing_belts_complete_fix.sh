#!/bin/bash

echo "🚀 Complete Timing Belts Production Fix Deployment"
echo "=================================================="
echo ""

# Check if we're in the right directory
if [ ! -f "artisan" ]; then
    echo "❌ Error: artisan file not found. Please run this script from the Laravel project root."
    exit 1
fi

# Create backup directory with timestamp
BACKUP_DIR="timing_belts_backup_$(date +%Y%m%d_%H%M%S)"
mkdir -p "$BACKUP_DIR"

echo "📦 Step 1: Creating comprehensive backup..."

# Backup timing_belts table data
echo "  📊 Backing up timing_belts table data..."
php -r "
require_once 'vendor/autoload.php';
\$app = require_once 'bootstrap/app.php';
\$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
use Illuminate\Support\Facades\DB;

try {
    \$data = DB::table('timing_belts')->get();
    file_put_contents('$BACKUP_DIR/timing_belts_data.json', json_encode(\$data, JSON_PRETTY_PRINT));
    echo 'Timing belts data backed up to $BACKUP_DIR/timing_belts_data.json' . PHP_EOL;
} catch (Exception \$e) {
    echo 'Backup failed: ' . \$e->getMessage() . PHP_EOL;
    exit(1);
}
"

# Backup rate_formulas table data
echo "  📐 Backing up rate_formulas table data..."
php -r "
require_once 'vendor/autoload.php';
\$app = require_once 'bootstrap/app.php';
\$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
use Illuminate\Support\Facades\DB;

try {
    \$data = DB::table('rate_formulas')->where('category', 'timing_belts')->get();
    file_put_contents('$BACKUP_DIR/timing_belt_formulas.json', json_encode(\$data, JSON_PRETTY_PRINT));
    echo 'Timing belt formulas backed up to $BACKUP_DIR/timing_belt_formulas.json' . PHP_EOL;
} catch (Exception \$e) {
    echo 'Formula backup failed: ' . \$e->getMessage() . PHP_EOL;
    exit(1);
}
"

# Backup table structure
echo "  🏗️  Backing up table structure..."
php -r "
require_once 'vendor/autoload.php';
\$app = require_once 'bootstrap/app.php';
\$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
use Illuminate\Support\Facades\DB;

try {
    \$structure = DB::select('SHOW CREATE TABLE timing_belts');
    file_put_contents('$BACKUP_DIR/timing_belts_structure.sql', \$structure[0]->{'Create Table'});
    echo 'Table structure backed up to $BACKUP_DIR/timing_belts_structure.sql' . PHP_EOL;
} catch (Exception \$e) {
    echo 'Structure backup failed: ' . \$e->getMessage() . PHP_EOL;
    exit(1);
}
"

echo "✅ Backup completed in directory: $BACKUP_DIR"
echo ""

echo "🔧 Step 2: Syncing table structure with local database..."
echo "  🏗️  Running table structure migration..."

# Run the table structure sync migration
php artisan migrate --path=database/migrations/2026_01_06_071801_force_timing_belts_table_structure_sync.php --force

if [ $? -eq 0 ]; then
    echo "✅ Table structure sync completed successfully!"
else
    echo "❌ Table structure sync failed!"
    echo "🔄 Restore from backup if needed: $BACKUP_DIR"
    exit 1
fi

echo ""
echo "📐 Step 3: Updating timing belt formulas..."

# Run the formula update script
php update_timing_belt_formulas_production.php

if [ $? -eq 0 ]; then
    echo "✅ Formula update completed successfully!"
else
    echo "❌ Formula update failed!"
    echo "🔄 Restore from backup if needed: $BACKUP_DIR"
    exit 1
fi

echo ""
echo "🧪 Step 4: Running comprehensive tests..."

# Test table structure
echo "  🏗️  Testing table structure..."
php verify_timing_belts_structure.php

if [ $? -eq 0 ]; then
    echo "✅ Table structure verification passed!"
else
    echo "❌ Table structure verification failed!"
    exit 1
fi

# Test seeding functionality
echo "  🌱 Testing seeding functionality..."
php -r "
require_once 'vendor/autoload.php';
\$app = require_once 'bootstrap/app.php';
\$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
use Illuminate\Support\Facades\DB;

try {
    // Test insert with all required fields
    DB::table('timing_belts')->insert([
        'section' => 'TEST-SECTION',
        'size' => '999',
        'type' => '0',
        'total_mm' => 0.00,
        'rate' => 1.00,
        'value' => 0.00,
        'reorder_level' => null,
        'remark' => 'Test seeding functionality',
        'created_by' => 1,
        'created_at' => now(),
        'updated_at' => now()
    ]);
    
    echo '✅ Test insert successful!' . PHP_EOL;
    
    // Clean up test record
    DB::table('timing_belts')->where('section', 'TEST-SECTION')->delete();
    echo '✅ Test cleanup successful!' . PHP_EOL;
    
} catch (Exception \$e) {
    echo '❌ Seeding test failed: ' . \$e->getMessage() . PHP_EOL;
    exit(1);
}
"

if [ $? -eq 0 ]; then
    echo "✅ Seeding functionality test passed!"
else
    echo "❌ Seeding functionality test failed!"
    exit 1
fi

# Test formula retrieval
echo "  📐 Testing formula retrieval..."
php -r "
require_once 'vendor/autoload.php';
\$app = require_once 'bootstrap/app.php';
\$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
use Illuminate\Support\Facades\DB;

try {
    \$formulas = DB::table('rate_formulas')
        ->where('category', 'timing_belts')
        ->where('is_active', 1)
        ->count();
    
    if (\$formulas >= 26) { // Should have at least 26 timing belt sections
        echo '✅ Formula retrieval test passed! Found ' . \$formulas . ' active formulas.' . PHP_EOL;
    } else {
        echo '❌ Formula retrieval test failed! Only found ' . \$formulas . ' formulas (expected at least 26).' . PHP_EOL;
        exit(1);
    }
    
} catch (Exception \$e) {
    echo '❌ Formula test failed: ' . \$e->getMessage() . PHP_EOL;
    exit(1);
}
"

if [ $? -eq 0 ]; then
    echo "✅ Formula retrieval test passed!"
else
    echo "❌ Formula retrieval test failed!"
    exit 1
fi

echo ""
echo "🎉 DEPLOYMENT COMPLETED SUCCESSFULLY!"
echo "===================================="
echo ""
echo "📋 Summary of changes:"
echo "  ✅ Table structure synced with local database"
echo "  ✅ Removed 'category' column requirement"
echo "  ✅ Added 'value' column for formula calculations"
echo "  ✅ Updated all timing belt formulas with correct multipliers"
echo "  ✅ All tests passed successfully"
echo ""
echo "📊 Formula Details:"
echo "  📐 Formula: value = (size × type × 450 × multiplier) + (size × total_mm × multiplier)"
echo "  🔢 L sections multiplier: 0.0045"
echo "  🔢 All other sections multiplier: 0.0094"
echo "  🔢 Fixed constant: 450"
echo ""
echo "🔗 Next Steps:"
echo "  1. Test the Settings page seeding functionality"
echo "  2. Verify timing belt value calculations"
echo "  3. Check that all sections are working properly"
echo ""
echo "💾 Backup Location: $BACKUP_DIR"
echo "   (Keep this backup until you confirm everything works correctly)"
echo ""
echo "🚀 Your timing belts system is now fully operational!"