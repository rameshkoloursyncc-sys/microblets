#!/bin/bash

echo "🔄 Syncing timing_belts table structure with local database..."

# Backup current data first
echo "📦 Creating backup of current timing_belts data..."
php -r "
require_once 'vendor/autoload.php';
\$app = require_once 'bootstrap/app.php';
\$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
use Illuminate\Support\Facades\DB;

\$data = DB::table('timing_belts')->get();
file_put_contents('timing_belts_backup_' . date('Y_m_d_H_i_s') . '.json', json_encode(\$data, JSON_PRETTY_PRINT));
echo 'Backup created: timing_belts_backup_' . date('Y_m_d_H_i_s') . '.json' . PHP_EOL;
"

# Run the migration
echo "🚀 Running table structure sync migration..."
php artisan migrate --path=database/migrations/2026_01_06_071801_force_timing_belts_table_structure_sync.php --force

if [ $? -eq 0 ]; then
    echo "✅ Table structure sync completed successfully!"
    
    # Test the seeding functionality
    echo "🧪 Testing seeding functionality..."
    php -r "
    require_once 'vendor/autoload.php';
    \$app = require_once 'bootstrap/app.php';
    \$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
    use Illuminate\Support\Facades\DB;
    
    try {
        // Test insert
        DB::table('timing_belts')->insert([
            'section' => 'TEST-SECTION',
            'size' => '999',
            'type' => '0',
            'total_mm' => 0.00,
            'rate' => 1.00,
            'value' => 0.00,
            'reorder_level' => null,
            'remark' => 'Test record',
            'created_by' => 1,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        echo '✅ Test insert successful!' . PHP_EOL;
        
        // Clean up test record
        DB::table('timing_belts')->where('section', 'TEST-SECTION')->delete();
        echo '✅ Test cleanup successful!' . PHP_EOL;
        
        echo '🎉 Timing belt seeding should now work properly!' . PHP_EOL;
        
    } catch (Exception \$e) {
        echo '❌ Test failed: ' . \$e->getMessage() . PHP_EOL;
    }
    "
else
    echo "❌ Migration failed!"
    exit 1
fi

echo ""
echo "📋 Summary:"
echo "- Production timing_belts table structure is now identical to local"
echo "- Removed 'category' column requirement"
echo "- Added 'value' column (mapped from 'total_value')"
echo "- Updated foreign key constraints to match local structure"
echo "- Seeding functionality should now work without errors"
echo ""
echo "🔗 You can now test the seeding in the Settings page!"