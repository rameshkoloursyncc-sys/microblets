<?php

// Debug script for timing belt seeding issues in production
// Run this on the production server: php debug_timing_belt_seeding.php

echo "🔍 Debugging Timing Belt Seeding Issues\n";
echo "=====================================\n\n";

// 1. Check if we're in the right directory
echo "1. Current directory: " . getcwd() . "\n";
echo "2. Laravel app path: " . (defined('LARAVEL_START') ? 'Laravel loaded' : 'Laravel not loaded') . "\n\n";

// Load Laravel if not already loaded
if (!defined('LARAVEL_START')) {
    require_once __DIR__ . '/vendor/autoload.php';
    $app = require_once __DIR__ . '/bootstrap/app.php';
    $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
}

echo "3. Laravel loaded successfully ✅\n\n";

// 2. Check database connection
try {
    DB::connection()->getPdo();
    echo "4. Database connection: ✅ Connected\n";
} catch (Exception $e) {
    echo "4. Database connection: ❌ Failed - " . $e->getMessage() . "\n";
    exit(1);
}

// 3. Check if timing_belts table exists
try {
    $tableExists = Schema::hasTable('timing_belts');
    echo "5. timing_belts table exists: " . ($tableExists ? '✅ Yes' : '❌ No') . "\n";
    
    if ($tableExists) {
        $columns = Schema::getColumnListing('timing_belts');
        echo "   Columns: " . implode(', ', $columns) . "\n";
        
        // Check section column length
        $sectionColumn = DB::select("SHOW COLUMNS FROM timing_belts WHERE Field = 'section'")[0];
        echo "   Section column type: " . $sectionColumn->Type . "\n";
    }
} catch (Exception $e) {
    echo "5. Table check failed: ❌ " . $e->getMessage() . "\n";
}

// 4. Check if rate_formulas table has timing belt formulas
try {
    $formulaCount = DB::table('rate_formulas')->where('category', 'timing_belts')->count();
    echo "6. Timing belt formulas in database: " . $formulaCount . "\n";
    
    if ($formulaCount > 0) {
        $formulas = DB::table('rate_formulas')
            ->where('category', 'timing_belts')
            ->select('section', 'formula')
            ->get();
        echo "   Sections with formulas: " . $formulas->pluck('section')->implode(', ') . "\n";
    }
} catch (Exception $e) {
    echo "6. Formula check failed: ❌ " . $e->getMessage() . "\n";
}

// 5. Check JSON file access
echo "\n7. JSON File Tests:\n";
$testFiles = [
    'NeopreneT10Products.json',
    'Timing3MProducts.json',
    'NeopreneXLProducts.json'
];

foreach ($testFiles as $filename) {
    $jsonPath = resource_path("js/mock/{$filename}");
    echo "   {$filename}: ";
    
    if (!file_exists($jsonPath)) {
        echo "❌ File not found\n";
        continue;
    }
    
    if (!is_readable($jsonPath)) {
        echo "❌ File not readable\n";
        continue;
    }
    
    $content = file_get_contents($jsonPath);
    if ($content === false) {
        echo "❌ Cannot read file content\n";
        continue;
    }
    
    $jsonData = json_decode($content, true);
    if ($jsonData === null) {
        echo "❌ Invalid JSON format - " . json_last_error_msg() . "\n";
        continue;
    }
    
    echo "✅ OK (" . count($jsonData) . " items)\n";
}

// 6. Test actual seeding process
echo "\n8. Testing Seeding Process:\n";
try {
    // Test with a small JSON file
    $testFile = 'NeopreneT10Products.json';
    $jsonPath = resource_path("js/mock/{$testFile}");
    
    if (file_exists($jsonPath)) {
        $jsonData = json_decode(file_get_contents($jsonPath), true);
        echo "   Loaded JSON data: " . count($jsonData) . " items\n";
        
        // Test database insertion
        DB::beginTransaction();
        
        $testItem = $jsonData[0] ?? null;
        if ($testItem) {
            echo "   Test item: section=" . ($testItem['section'] ?? 'N/A') . 
                 ", size=" . ($testItem['size'] ?? 'N/A') . 
                 ", type=" . ($testItem['type'] ?? 'N/A') . "\n";
            
            // Check if item already exists
            $existing = DB::table('timing_belts')
                ->where('section', $testItem['section'] ?? '')
                ->where('size', (string)($testItem['size'] ?? ''))
                ->where('type', $testItem['type'] ?? '')
                ->first();
            
            if ($existing) {
                echo "   ⚠️  Test item already exists in database\n";
            } else {
                // Try to insert test item
                $insertData = [
                    'section' => $testItem['section'] ?? 'TEST',
                    'size' => (string)($testItem['size'] ?? '100'),
                    'type' => $testItem['type'] ?? 'FULL SLEEVE',
                    'total_mm' => $testItem['total_mm'] ?? 0,
                    'rate' => $testItem['rate'] ?? 0,
                    'value' => $testItem['value'] ?? 0,
                    'reorder_level' => $testItem['reorder_level'] ?? null,
                    'remark' => $testItem['remark'] ?? null,
                    'created_by' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                
                DB::table('timing_belts')->insert($insertData);
                echo "   ✅ Test insertion successful\n";
                
                // Clean up test data
                DB::table('timing_belts')
                    ->where('section', $insertData['section'])
                    ->where('size', $insertData['size'])
                    ->where('type', $insertData['type'])
                    ->delete();
                echo "   🧹 Test data cleaned up\n";
            }
        }
        
        DB::rollBack();
        echo "   ✅ Database transaction test completed\n";
    }
    
} catch (Exception $e) {
    DB::rollBack();
    echo "   ❌ Seeding test failed: " . $e->getMessage() . "\n";
    echo "   Stack trace:\n" . $e->getTraceAsString() . "\n";
}

// 7. Check migrations status
echo "\n9. Migration Status:\n";
try {
    $migrations = DB::table('migrations')
        ->where('migration', 'like', '%timing%')
        ->orWhere('migration', 'like', '%rate_formula%')
        ->orderBy('batch')
        ->get();
    
    foreach ($migrations as $migration) {
        echo "   ✅ {$migration->migration} (batch {$migration->batch})\n";
    }
    
    if ($migrations->isEmpty()) {
        echo "   ⚠️  No timing belt related migrations found\n";
    }
} catch (Exception $e) {
    echo "   ❌ Migration check failed: " . $e->getMessage() . "\n";
}

echo "\n🎯 Debug Summary:\n";
echo "================\n";
echo "If all checks above show ✅, the seeding should work.\n";
echo "If you see ❌ errors, those need to be fixed first.\n";
echo "\nTo run this script on production:\n";
echo "1. Upload this file to /var/www/microbelts_ima/\n";
echo "2. Run: cd /var/www/microbelts_ima && php debug_timing_belt_seeding.php\n";