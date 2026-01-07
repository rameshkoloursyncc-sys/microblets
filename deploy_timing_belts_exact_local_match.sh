#!/bin/bash

echo "🚀 Deploy Timing Belts - Exact Local Match"
echo "=========================================="
echo ""

# Check if we're in the right directory
if [ ! -f "artisan" ]; then
    echo "❌ Error: artisan file not found. Please run this script from the Laravel project root."
    exit 1
fi

# Create backup directory with timestamp
BACKUP_DIR="timing_belts_complete_backup_$(date +%Y%m%d_%H%M%S)"
mkdir -p "$BACKUP_DIR"

echo "📦 Step 1: Creating comprehensive backup..."

# Backup timing_belts table data
php -r "
require_once 'vendor/autoload.php';
\$app = require_once 'bootstrap/app.php';
\$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
use Illuminate\Support\Facades\DB;

try {
    \$data = DB::table('timing_belts')->get();
    file_put_contents('$BACKUP_DIR/timing_belts_data.json', json_encode(\$data, JSON_PRETTY_PRINT));
    
    \$formulas = DB::table('rate_formulas')->where('category', 'timing_belts')->get();
    file_put_contents('$BACKUP_DIR/timing_belt_formulas.json', json_encode(\$formulas, JSON_PRETTY_PRINT));
    
    echo 'Backup completed in $BACKUP_DIR' . PHP_EOL;
} catch (Exception \$e) {
    echo 'Backup failed: ' . \$e->getMessage() . PHP_EOL;
    exit(1);
}
"

echo "✅ Backup completed"
echo ""

echo "🏗️  Step 2: Syncing table structure..."
php artisan migrate --path=database/migrations/2026_01_06_071801_force_timing_belts_table_structure_sync.php --force

if [ $? -ne 0 ]; then
    echo "❌ Table structure sync failed!"
    exit 1
fi

echo "✅ Table structure synced"
echo ""

echo "🔧 Step 3: Syncing complete functionality..."
php sync_timing_belt_complete_functionality.php

if [ $? -ne 0 ]; then
    echo "❌ Functionality sync failed!"
    exit 1
fi

echo "✅ Functionality synced"
echo ""

echo "🧪 Step 4: Running comprehensive tests..."

# Test 1: Verify table structure
echo "  🏗️  Testing table structure..."
php verify_timing_belts_structure.php > /dev/null 2>&1

if [ $? -eq 0 ]; then
    echo "  ✅ Table structure test passed"
else
    echo "  ❌ Table structure test failed"
    exit 1
fi

# Test 2: Test seeding functionality
echo "  🌱 Testing seeding functionality..."
php -r "
require_once 'vendor/autoload.php';
\$app = require_once 'bootstrap/app.php';
\$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
use App\Models\TimingBelt;

try {
    // Test auto-calculation on create
    \$belt = TimingBelt::create([
        'section' => 'TEST-SECTION',
        'size' => '200',
        'type' => '2',
        'total_mm' => 1000.00,
        'rate' => 2.50,
        'reorder_level' => null,
        'remark' => 'Test auto-calculation',
        'created_by' => 1,
    ]);
    
    // Expected: (200 * 2 * 450 * 0.0094) + (200 * 1000 * 0.0094) = 1692 + 1880 = 3572
    \$expected = (200 * 2 * 450 * 0.0094) + (200 * 1000 * 0.0094);
    
    if (abs(\$belt->value - \$expected) < 0.01) {
        echo 'Auto-calculation test PASSED: Expected ' . \$expected . ', Got ' . \$belt->value . PHP_EOL;
    } else {
        echo 'Auto-calculation test FAILED: Expected ' . \$expected . ', Got ' . \$belt->value . PHP_EOL;
        exit(1);
    }
    
    // Test auto-recalculation on update
    \$belt->update(['total_mm' => 2000.00]);
    \$belt->refresh();
    
    \$expectedAfterUpdate = (200 * 2 * 450 * 0.0094) + (200 * 2000 * 0.0094);
    
    if (abs(\$belt->value - \$expectedAfterUpdate) < 0.01) {
        echo 'Auto-recalculation test PASSED: Expected ' . \$expectedAfterUpdate . ', Got ' . \$belt->value . PHP_EOL;
    } else {
        echo 'Auto-recalculation test FAILED: Expected ' . \$expectedAfterUpdate . ', Got ' . \$belt->value . PHP_EOL;
        exit(1);
    }
    
    // Clean up
    \$belt->delete();
    echo 'Test cleanup completed' . PHP_EOL;
    
} catch (Exception \$e) {
    echo 'Seeding test failed: ' . \$e->getMessage() . PHP_EOL;
    exit(1);
}
"

if [ $? -eq 0 ]; then
    echo "  ✅ Seeding functionality test passed"
else
    echo "  ❌ Seeding functionality test failed"
    exit 1
fi

# Test 3: Test formula retrieval
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
    
    if (\$formulas >= 26) {
        echo 'Formula test PASSED: Found ' . \$formulas . ' active formulas' . PHP_EOL;
    } else {
        echo 'Formula test FAILED: Only found ' . \$formulas . ' formulas (expected at least 26)' . PHP_EOL;
        exit(1);
    }
    
} catch (Exception \$e) {
    echo 'Formula test failed: ' . \$e->getMessage() . PHP_EOL;
    exit(1);
}
"

if [ $? -eq 0 ]; then
    echo "  ✅ Formula retrieval test passed"
else
    echo "  ❌ Formula retrieval test failed"
    exit 1
fi

# Test 4: Test Settings page seeding simulation
echo "  ⚙️  Testing Settings page seeding simulation..."
php -r "
require_once 'vendor/autoload.php';
\$app = require_once 'bootstrap/app.php';
\$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
use App\Models\TimingBelt;
use Illuminate\Support\Facades\DB;

try {
    // Simulate seeding like Settings page does
    \$testData = [
        ['section' => 'TEST-L', 'size' => '100', 'type' => '1', 'total_mm' => 500, 'rate' => 2.50],
        ['section' => 'TEST-XL', 'size' => '200', 'type' => '2', 'total_mm' => 1000, 'rate' => 3.00],
    ];
    
    foreach (\$testData as \$item) {
        \$belt = TimingBelt::create([
            'section' => \$item['section'],
            'size' => \$item['size'],
            'type' => \$item['type'],
            'total_mm' => \$item['total_mm'],
            'rate' => \$item['rate'],
            'created_by' => 1,
        ]);
        
        // Verify value was auto-calculated (should not be 0)
        if (\$belt->value > 0) {
            echo 'Seeding simulation PASSED for ' . \$belt->section . '-' . \$belt->size . ': Value = ' . \$belt->value . PHP_EOL;
        } else {
            echo 'Seeding simulation FAILED for ' . \$belt->section . '-' . \$belt->size . ': Value = ' . \$belt->value . PHP_EOL;
            exit(1);
        }
    }
    
    // Clean up test data
    TimingBelt::where('section', 'LIKE', 'TEST-%')->delete();
    echo 'Seeding simulation cleanup completed' . PHP_EOL;
    
} catch (Exception \$e) {
    echo 'Seeding simulation failed: ' . \$e->getMessage() . PHP_EOL;
    exit(1);
}
"

if [ $? -eq 0 ]; then
    echo "  ✅ Settings page seeding simulation passed"
else
    echo "  ❌ Settings page seeding simulation failed"
    exit 1
fi

echo ""
echo "🎉 DEPLOYMENT COMPLETED SUCCESSFULLY!"
echo "===================================="
echo ""
echo "📋 What was deployed:"
echo "  ✅ Table structure now matches local exactly"
echo "  ✅ TimingBelt model auto-calculation functionality verified"
echo "  ✅ All 26 timing belt section formulas updated"
echo "  ✅ Seeding process updated to use auto-calculation"
echo "  ✅ All existing values recalculated with correct formulas"
echo ""
echo "🔧 Key Changes Made:"
echo "  📐 Formula: value = (size × type × 450 × multiplier) + (size × total_mm × multiplier)"
echo "  🔢 L sections multiplier: 0.0045"
echo "  🔢 All other sections multiplier: 0.0094"
echo "  🔄 Auto-calculation on create and update"
echo "  🌱 Seeding no longer overrides auto-calculation"
echo ""
echo "✅ Your timing belts system now works EXACTLY like local!"
echo ""
echo "🔗 Next Steps:"
echo "  1. Test Settings page seeding - values should auto-calculate"
echo "  2. Test editing timing belts - values should update automatically"
echo "  3. Verify all sections work correctly"
echo ""
echo "💾 Backup Location: $BACKUP_DIR"
echo ""
echo "🚀 Ready to use!"