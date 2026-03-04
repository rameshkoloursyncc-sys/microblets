<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

echo "🔍 Verifying timing_belts table structure...\n\n";

try {
    // Check if table exists
    if (!Schema::hasTable('timing_belts')) {
        echo "❌ timing_belts table does not exist!\n";
        exit(1);
    }
    
    echo "✅ timing_belts table exists\n\n";
    
    // Get column information
    $columns = DB::select('SHOW COLUMNS FROM timing_belts');
    
    echo "📋 Current table structure:\n";
    echo sprintf("%-20s %-20s %-8s %-8s %-15s %s\n", 'Field', 'Type', 'Null', 'Key', 'Default', 'Extra');
    echo str_repeat('-', 80) . "\n";
    
    $requiredColumns = [
        'id', 'section', 'size', 'type', 'mm', 'total_mm', 'in_mm', 'out_mm',
        'full_sleeve', 'in_sleeve', 'out_sleeve', 'rate_per_sleeve', 
        'reorder_level', 'rate', 'value', 'remark', 'created_by', 'updated_by',
        'created_at', 'updated_at'
    ];
    
    $foundColumns = [];
    
    foreach ($columns as $column) {
        echo sprintf("%-20s %-20s %-8s %-8s %-15s %s\n", 
            $column->Field, 
            $column->Type, 
            $column->Null, 
            $column->Key, 
            $column->Default ?? 'NULL', 
            $column->Extra ?? ''
        );
        $foundColumns[] = $column->Field;
    }
    
    echo "\n🔍 Checking required columns:\n";
    
    $missing = [];
    foreach ($requiredColumns as $required) {
        if (in_array($required, $foundColumns)) {
            echo "✅ {$required}\n";
        } else {
            echo "❌ {$required} - MISSING\n";
            $missing[] = $required;
        }
    }
    
    // Check for unwanted columns
    $unwantedColumns = ['category'];
    echo "\n🔍 Checking for unwanted columns:\n";
    
    foreach ($unwantedColumns as $unwanted) {
        if (in_array($unwanted, $foundColumns)) {
            echo "❌ {$unwanted} - SHOULD NOT EXIST\n";
        } else {
            echo "✅ {$unwanted} - correctly removed\n";
        }
    }
    
    // Check specific column requirements
    echo "\n🔍 Checking specific column requirements:\n";
    
    foreach ($columns as $column) {
        if ($column->Field === 'section') {
            if (strpos($column->Type, 'varchar(20)') !== false) {
                echo "✅ section column has correct length (20)\n";
            } else {
                echo "❌ section column should be varchar(20), found: {$column->Type}\n";
            }
        }
        
        if ($column->Field === 'value') {
            if (strpos($column->Type, 'decimal') !== false) {
                echo "✅ value column exists and is decimal type\n";
            } else {
                echo "❌ value column should be decimal type, found: {$column->Type}\n";
            }
        }
        
        if ($column->Field === 'reorder_level') {
            if ($column->Null === 'YES') {
                echo "✅ reorder_level is nullable\n";
            } else {
                echo "❌ reorder_level should be nullable\n";
            }
        }
    }
    
    if (empty($missing)) {
        echo "\n🎉 Table structure verification PASSED!\n";
        echo "The timing_belts table is now ready for seeding.\n";
    } else {
        echo "\n❌ Table structure verification FAILED!\n";
        echo "Missing columns: " . implode(', ', $missing) . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Verification failed: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}