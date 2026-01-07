<?php

require_once 'vendor/autoload.php';

use App\Models\TimingBelt;
use Illuminate\Support\Facades\DB;

// Test timing belt value calculation
echo "🧪 Testing Timing Belt Fixes\n\n";

// Test 1: Create a timing belt and check if rate is calculated
echo "Test 1: Creating timing belt and checking rate calculation\n";
echo "-------------------------------------------------------\n";

try {
    $timingBelt = new TimingBelt([
        'section' => 'XL',
        'size' => '150',
        'type' => '18',
        'total_mm' => 1000.00,
    ]);
    
    // Simulate the calculation
    $timingBelt->calculateValue();
    
    echo "Section: {$timingBelt->section}\n";
    echo "Size: {$timingBelt->size}\n";
    echo "Type: {$timingBelt->type}\n";
    echo "Total MM: {$timingBelt->total_mm}\n";
    echo "Calculated Rate: {$timingBelt->rate}\n";
    echo "Calculated Value: {$timingBelt->value}\n";
    
    if ($timingBelt->rate > 0) {
        echo "✅ Rate calculation working correctly\n";
    } else {
        echo "❌ Rate is still zero - check formula setup\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 2: Check if rate formulas exist for timing belts
echo "Test 2: Checking rate formulas in database\n";
echo "-------------------------------------------\n";

try {
    $formulas = DB::table('rate_formulas')
        ->where('category', 'timing_belts')
        ->where('is_active', 1)
        ->get();
    
    echo "Found " . $formulas->count() . " active timing belt formulas:\n";
    
    foreach ($formulas as $formula) {
        echo "- Section: {$formula->section}, Formula: {$formula->formula}\n";
    }
    
    if ($formulas->count() > 0) {
        echo "✅ Rate formulas exist\n";
    } else {
        echo "❌ No rate formulas found - need to set up formulas\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 3: Check timing belt table structure
echo "Test 3: Checking timing belt table structure\n";
echo "---------------------------------------------\n";

try {
    $columns = DB::select("DESCRIBE timing_belts");
    
    $requiredFields = ['full_sleeve', 'in_sleeve', 'out_sleeve', 'rate_per_sleeve'];
    $foundFields = [];
    
    foreach ($columns as $column) {
        if (in_array($column->Field, $requiredFields)) {
            $foundFields[] = $column->Field;
        }
    }
    
    echo "Required sleeve fields: " . implode(', ', $requiredFields) . "\n";
    echo "Found sleeve fields: " . implode(', ', $foundFields) . "\n";
    
    if (count($foundFields) === count($requiredFields)) {
        echo "✅ All sleeve fields exist in database\n";
    } else {
        echo "❌ Missing sleeve fields in database\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n🏁 Test completed!\n";