<?php

// Production Verification Script for Timing Belt Fixes
// Run this after deploying to production to verify all fixes are working

echo "🔍 Verifying Timing Belt Fixes in Production\n";
echo "=============================================\n\n";

// Include Laravel bootstrap
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\TimingBelt;
use Illuminate\Support\Facades\DB;

$allTestsPassed = true;

// Test 1: Check database table structure
echo "Test 1: Database Table Structure\n";
echo "---------------------------------\n";

try {
    $columns = DB::select("DESCRIBE timing_belts");
    
    $requiredFields = [
        'full_sleeve', 'in_sleeve', 'out_sleeve', 'rate_per_sleeve',
        'total_mm', 'in_mm', 'out_mm', 'rate', 'value'
    ];
    
    $foundFields = [];
    foreach ($columns as $column) {
        if (in_array($column->Field, $requiredFields)) {
            $foundFields[] = $column->Field;
        }
    }
    
    echo "Required fields: " . implode(', ', $requiredFields) . "\n";
    echo "Found fields: " . implode(', ', $foundFields) . "\n";
    
    if (count($foundFields) === count($requiredFields)) {
        echo "✅ All required fields exist in database\n";
    } else {
        echo "❌ Missing fields in database\n";
        $allTestsPassed = false;
    }
    
} catch (Exception $e) {
    echo "❌ Error checking database structure: " . $e->getMessage() . "\n";
    $allTestsPassed = false;
}

echo "\n";

// Test 2: Check rate formulas
echo "Test 2: Rate Formulas\n";
echo "---------------------\n";

try {
    $formulas = DB::table('rate_formulas')
        ->where('category', 'timing_belts')
        ->where('is_active', 1)
        ->get();
    
    echo "Found " . $formulas->count() . " active timing belt formulas\n";
    
    if ($formulas->count() > 0) {
        echo "✅ Rate formulas exist\n";
        
        // Show a few examples
        $examples = $formulas->take(5);
        foreach ($examples as $formula) {
            echo "  - {$formula->section}: {$formula->formula}\n";
        }
        if ($formulas->count() > 5) {
            echo "  ... and " . ($formulas->count() - 5) . " more\n";
        }
    } else {
        echo "❌ No rate formulas found\n";
        $allTestsPassed = false;
    }
    
} catch (Exception $e) {
    echo "❌ Error checking rate formulas: " . $e->getMessage() . "\n";
    $allTestsPassed = false;
}

echo "\n";

// Test 3: Value calculation
echo "Test 3: Value Calculation\n";
echo "-------------------------\n";

try {
    // Test with different scenarios
    $testCases = [
        ['section' => 'XL', 'size' => '150', 'type' => '18', 'total_mm' => 1000],
        ['section' => 'L', 'size' => '200', 'type' => '21', 'total_mm' => 500],
        ['section' => 'NEOPRENE-XL', 'size' => '100', 'type' => 'FULL SLEEVE', 'total_mm' => 800],
    ];
    
    $calculationsPassed = 0;
    
    foreach ($testCases as $i => $testCase) {
        echo "Test case " . ($i + 1) . ": {$testCase['section']}-{$testCase['size']}-{$testCase['type']}\n";
        
        $timingBelt = new TimingBelt($testCase);
        $timingBelt->calculateValue();
        
        echo "  Size: {$timingBelt->size}\n";
        echo "  Type: {$timingBelt->type}\n";
        echo "  Total MM: {$timingBelt->total_mm}\n";
        echo "  Calculated Rate: {$timingBelt->rate}\n";
        echo "  Calculated Value: {$timingBelt->value}\n";
        
        if ($timingBelt->rate > 0 && $timingBelt->value > 0) {
            echo "  ✅ Calculation working\n";
            $calculationsPassed++;
        } else {
            echo "  ❌ Calculation failed\n";
        }
        echo "\n";
    }
    
    if ($calculationsPassed === count($testCases)) {
        echo "✅ All value calculations working correctly\n";
    } else {
        echo "❌ Some value calculations failed\n";
        $allTestsPassed = false;
    }
    
} catch (Exception $e) {
    echo "❌ Error testing value calculation: " . $e->getMessage() . "\n";
    $allTestsPassed = false;
}

echo "\n";

// Test 4: Check if controller methods exist
echo "Test 4: Controller Methods\n";
echo "--------------------------\n";

try {
    $controllerFile = file_get_contents('app/Http/Controllers/Api/TimingBeltController.php');
    
    $requiredMethods = [
        'recalculateAllRates',
        'recalculateSectionRates',
        'inOutOperation'
    ];
    
    $foundMethods = [];
    foreach ($requiredMethods as $method) {
        if (strpos($controllerFile, "function $method") !== false) {
            $foundMethods[] = $method;
        }
    }
    
    echo "Required methods: " . implode(', ', $requiredMethods) . "\n";
    echo "Found methods: " . implode(', ', $foundMethods) . "\n";
    
    if (count($foundMethods) === count($requiredMethods)) {
        echo "✅ All required controller methods exist\n";
        
        // Check if inOutOperation supports unit_type
        if (strpos($controllerFile, 'unit_type') !== false) {
            echo "✅ inOutOperation supports unit_type parameter\n";
        } else {
            echo "❌ inOutOperation missing unit_type support\n";
            $allTestsPassed = false;
        }
    } else {
        echo "❌ Missing controller methods\n";
        $allTestsPassed = false;
    }
    
} catch (Exception $e) {
    echo "❌ Error checking controller methods: " . $e->getMessage() . "\n";
    $allTestsPassed = false;
}

echo "\n";

// Test 5: Check routes
echo "Test 5: API Routes\n";
echo "------------------\n";

try {
    if (file_exists('routes/api_timing_belts.php')) {
        $routesFile = file_get_contents('routes/api_timing_belts.php');
        
        $requiredRoutes = [
            'recalculate-all-rates',
            'recalculate-section-rates',
            'in-out'
        ];
        
        $foundRoutes = [];
        foreach ($requiredRoutes as $route) {
            if (strpos($routesFile, $route) !== false) {
                $foundRoutes[] = $route;
            }
        }
        
        echo "Required routes: " . implode(', ', $requiredRoutes) . "\n";
        echo "Found routes: " . implode(', ', $foundRoutes) . "\n";
        
        if (count($foundRoutes) === count($requiredRoutes)) {
            echo "✅ All required routes exist\n";
        } else {
            echo "❌ Missing routes\n";
            $allTestsPassed = false;
        }
    } else {
        echo "❌ API routes file not found\n";
        $allTestsPassed = false;
    }
    
} catch (Exception $e) {
    echo "❌ Error checking routes: " . $e->getMessage() . "\n";
    $allTestsPassed = false;
}

echo "\n";

// Test 6: Check frontend files
echo "Test 6: Frontend Files\n";
echo "----------------------\n";

try {
    $frontendFiles = [
        'resources/js/composables/useTimingBelts.ts' => 'unit_type',
        'resources/js/components/inventory/TimingBeltTable.vue' => 'unit_type'
    ];
    
    $frontendPassed = 0;
    
    foreach ($frontendFiles as $file => $searchTerm) {
        if (file_exists($file)) {
            $content = file_get_contents($file);
            if (strpos($content, $searchTerm) !== false) {
                echo "✅ $file contains $searchTerm\n";
                $frontendPassed++;
            } else {
                echo "❌ $file missing $searchTerm\n";
            }
        } else {
            echo "❌ $file not found\n";
        }
    }
    
    if ($frontendPassed === count($frontendFiles)) {
        echo "✅ All frontend files updated correctly\n";
    } else {
        echo "❌ Some frontend files need updates\n";
        $allTestsPassed = false;
    }
    
} catch (Exception $e) {
    echo "❌ Error checking frontend files: " . $e->getMessage() . "\n";
    $allTestsPassed = false;
}

echo "\n";

// Final result
echo "🏁 Verification Complete!\n";
echo "=========================\n";

if ($allTestsPassed) {
    echo "🎉 ALL TESTS PASSED! Timing belt fixes are working correctly in production.\n\n";
    echo "✅ Rate calculation working\n";
    echo "✅ Database structure correct\n";
    echo "✅ Controller methods updated\n";
    echo "✅ API routes configured\n";
    echo "✅ Frontend files updated\n";
    echo "✅ Formula system operational\n\n";
    echo "🚀 Production deployment successful!\n";
    exit(0);
} else {
    echo "❌ SOME TESTS FAILED! Please review the issues above.\n\n";
    echo "🔧 You may need to:\n";
    echo "   1. Re-run the deployment script\n";
    echo "   2. Check file permissions\n";
    echo "   3. Verify database migrations\n";
    echo "   4. Rebuild frontend assets\n\n";
    echo "⚠️  Do not use the system until all tests pass!\n";
    exit(1);
}
?>