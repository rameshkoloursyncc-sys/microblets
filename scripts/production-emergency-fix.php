<?php
/**
 * PRODUCTION EMERGENCY FIX SCRIPT
 * Fixes email config and die configurations issues
 */

echo "🚨 PRODUCTION EMERGENCY FIX STARTING...\n";

// Include Laravel
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Fix 1: Check and fix email configuration
echo "1. Checking email configuration...\n";
$envRecipients = env('LOW_STOCK_EMAIL_RECIPIENTS');
$configRecipients = config('mail.low_stock_recipients');

echo "Env recipients: " . ($envRecipients ?: 'EMPTY') . "\n";
echo "Config recipients: " . (is_array($configRecipients) ? 'ARRAY' : $configRecipients) . "\n";

if (empty($envRecipients)) {
    echo "❌ Environment variable is empty!\n";
    echo "Please add this line to .env file:\n";
    echo "LOW_STOCK_EMAIL_RECIPIENTS=\"ramesh.koloursyncc@gmail.com,microbelts@gmail.com\"\n";
} else {
    echo "✅ Environment variable is set\n";
}

// Fix 2: Check die_configurations table
echo "\n2. Checking die_configurations table...\n";
try {
    $columns = \DB::getSchemaBuilder()->getColumnListing('die_configurations');
    echo "Table columns: " . implode(', ', $columns) . "\n";
    
    if (!in_array('belt_type', $columns)) {
        echo "❌ Missing belt_type column!\n";
        echo "Need to recreate table structure\n";
    } else {
        echo "✅ Table structure looks correct\n";
        
        // Try to seed data
        try {
            $count = \App\Models\DieConfiguration::count();
            echo "Current records: $count\n";
            
            if ($count === 0) {
                echo "Seeding default data...\n";
                \App\Models\DieConfiguration::seedDefaults();
                $newCount = \App\Models\DieConfiguration::count();
                echo "✅ Seeded $newCount configurations\n";
            }
        } catch (Exception $e) {
            echo "❌ Seeding error: " . $e->getMessage() . "\n";
        }
    }
} catch (Exception $e) {
    echo "❌ Table error: " . $e->getMessage() . "\n";
}

// Fix 3: Test API endpoints
echo "\n3. Testing API endpoints...\n";
try {
    // Test die configurations
    $configs = \App\Models\DieConfiguration::getAllGrouped();
    echo "✅ DieConfiguration::getAllGrouped() works\n";
} catch (Exception $e) {
    echo "❌ DieConfiguration error: " . $e->getMessage() . "\n";
}

// Fix 4: Clear caches
echo "\n4. Clearing caches...\n";
\Artisan::call('config:clear');
\Artisan::call('cache:clear');
\Artisan::call('route:clear');
echo "✅ Caches cleared\n";

echo "\n🎯 FIX COMPLETED!\n";
echo "Next steps:\n";
echo "1. Add LOW_STOCK_EMAIL_RECIPIENTS to .env if not done\n";
echo "2. Run: php artisan config:cache\n";
echo "3. Test the frontend inventory page\n";
?>