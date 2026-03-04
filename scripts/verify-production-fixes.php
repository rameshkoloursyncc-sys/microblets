<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🔍 Verifying production fixes...\n\n";

// Check 1: Environment Configuration
echo "1. Checking environment configuration...\n";
$sessionDriver = config('session.driver');
$sessionLifetime = config('session.lifetime');
$sessionDomain = config('session.domain');
$sessionHttpOnly = config('session.http_only');

echo "   - Session driver: {$sessionDriver}\n";
echo "   - Session lifetime: {$sessionLifetime} minutes\n";
echo "   - Session domain: " . ($sessionDomain ?: 'empty (correct)') . "\n";
echo "   - HTTP Only: " . ($sessionHttpOnly ? 'true' : 'false (correct for fallback)') . "\n";

if ($sessionDriver === 'database' && $sessionLifetime == 1440 && empty($sessionDomain) && !$sessionHttpOnly) {
    echo "   ✅ Session configuration is correct\n\n";
} else {
    echo "   ❌ Session configuration needs adjustment\n\n";
}

// Check 2: Database Connection
echo "2. Checking database connection...\n";
try {
    $userCount = DB::table('users')->count();
    echo "   ✅ Database connected successfully, found {$userCount} users\n\n";
} catch (Exception $e) {
    echo "   ❌ Database connection failed: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Check 3: Sessions Table
echo "3. Checking sessions table...\n";
try {
    $sessionCount = DB::table('sessions')->count();
    echo "   ✅ Sessions table exists with {$sessionCount} active sessions\n\n";
} catch (Exception $e) {
    echo "   ❌ Sessions table issue: " . $e->getMessage() . "\n\n";
}

// Check 4: Inventory Transactions Table Structure
echo "4. Checking inventory_transactions table structure...\n";
try {
    $columns = DB::select('DESCRIBE inventory_transactions');
    $columnNames = array_column($columns, 'Field');
    
    $requiredColumns = ['id', 'category', 'product_id', 'type', 'quantity', 'stock_before', 'stock_after', 'rate', 'description', 'user_id', 'created_at', 'updated_at'];
    $missingColumns = array_diff($requiredColumns, $columnNames);
    
    if (empty($missingColumns)) {
        echo "   ✅ All required columns present in inventory_transactions table\n";
        
        // Check rate column specifically
        $rateColumn = collect($columns)->firstWhere('Field', 'rate');
        if ($rateColumn) {
            echo "   ✅ Rate column: {$rateColumn->Type}, Null: {$rateColumn->Null}, Default: {$rateColumn->Default}\n\n";
        }
    } else {
        echo "   ❌ Missing columns: " . implode(', ', $missingColumns) . "\n\n";
    }
} catch (Exception $e) {
    echo "   ❌ Table structure check failed: " . $e->getMessage() . "\n\n";
}

// Check 5: Test Session Creation
echo "5. Testing session functionality...\n";
try {
    // Start session
    session_start();
    
    // Test session write
    session(['test_verification' => 'working_' . time()]);
    session()->save();
    
    // Test session read
    $testValue = session('test_verification');
    if ($testValue && strpos($testValue, 'working_') === 0) {
        echo "   ✅ Session read/write working correctly\n";
    } else {
        echo "   ❌ Session read/write not working\n";
    }
    
    // Clean up
    session()->forget('test_verification');
    session()->save();
    echo "   ✅ Session cleanup successful\n\n";
} catch (Exception $e) {
    echo "   ❌ Session test failed: " . $e->getMessage() . "\n\n";
}

// Check 6: Test User Authentication Flow
echo "6. Testing user authentication flow...\n";
try {
    // Find a test user (preferably admin)
    $testUser = App\Models\User::where('role', 'admin')->first();
    if (!$testUser) {
        $testUser = App\Models\User::first();
    }
    
    if ($testUser) {
        // Simulate session creation
        $userData = [
            'id' => $testUser->id,
            'name' => $testUser->name,
            'role' => $testUser->role ?? 'user'
        ];
        
        session(['user' => $userData]);
        session()->save();
        
        // Test session retrieval
        $sessionUser = session('user');
        if ($sessionUser && $sessionUser['id'] == $testUser->id) {
            echo "   ✅ User session creation and retrieval working\n";
        } else {
            echo "   ❌ User session not working properly\n";
        }
        
        // Clean up
        session()->forget('user');
        session()->save();
        echo "   ✅ User session cleanup successful\n\n";
    } else {
        echo "   ⚠️ No users found for testing\n\n";
    }
} catch (Exception $e) {
    echo "   ❌ User authentication test failed: " . $e->getMessage() . "\n\n";
}

// Check 7: Test Inventory Transaction Model
echo "7. Testing inventory transaction model...\n";
try {
    // Create a test transaction (without saving)
    $transaction = new App\Models\InventoryTransaction([
        'category' => 'test_category',
        'product_id' => 1,
        'type' => 'IN',
        'quantity' => 10.50,
        'stock_before' => 0.00,
        'stock_after' => 10.50,
        'rate' => 25.75,
        'description' => 'Test transaction for verification',
        'user_id' => 1,
    ]);
    
    // Check if all required fields are fillable
    $fillable = $transaction->getFillable();
    $requiredFields = ['category', 'product_id', 'type', 'quantity', 'stock_before', 'stock_after', 'rate', 'description', 'user_id'];
    $missingFillable = array_diff($requiredFields, $fillable);
    
    if (empty($missingFillable)) {
        echo "   ✅ All required fields are fillable in InventoryTransaction model\n";
    } else {
        echo "   ❌ Missing fillable fields: " . implode(', ', $missingFillable) . "\n";
    }
    
    // Test attribute casting
    $casts = $transaction->getCasts();
    if (isset($casts['rate']) && $casts['rate'] === 'decimal:2') {
        echo "   ✅ Rate field casting is correct\n\n";
    } else {
        echo "   ❌ Rate field casting may be incorrect\n\n";
    }
} catch (Exception $e) {
    echo "   ❌ Inventory transaction model test failed: " . $e->getMessage() . "\n\n";
}

// Final Summary
echo "🎯 Verification Summary:\n";
echo "   - If all checks show ✅, the fixes should be working\n";
echo "   - If any checks show ❌, those issues need to be addressed\n";
echo "   - Test with actual UI user creation and login to confirm\n\n";

echo "📋 Next Steps:\n";
echo "   1. Create a new user via the UI\n";
echo "   2. Login with that user\n";
echo "   3. Refresh the page multiple times\n";
echo "   4. Perform inventory operations (IN/OUT)\n";
echo "   5. Check logs for any errors\n\n";

echo "🔍 If issues persist, check:\n";
echo "   - storage/logs/laravel.log for detailed error messages\n";
echo "   - Browser developer console for JavaScript errors\n";
echo "   - Network tab to see if session cookies are being sent\n";