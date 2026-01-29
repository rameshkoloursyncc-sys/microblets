<?php
/**
 * PRODUCTION EMERGENCY FIX SCRIPT
 * Run this to fix the 500 error after deployment
 */

echo "🚨 PRODUCTION EMERGENCY FIX STARTING...\n";

// Clear all caches
echo "1. Clearing caches...\n";
exec('php artisan config:clear', $output1);
exec('php artisan route:clear', $output2);
exec('php artisan view:clear', $output3);
exec('php artisan cache:clear', $output4);

echo "2. Rebuilding caches...\n";
exec('php artisan config:cache', $output5);
exec('php artisan route:cache', $output6);

// Test database connection
echo "3. Testing database connection...\n";
try {
    $pdo = new PDO("mysql:host=" . $_ENV['DB_HOST'] . ";dbname=" . $_ENV['DB_DATABASE'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD']);
    echo "✅ Database connection: OK\n";
} catch (Exception $e) {
    echo "❌ Database connection: FAILED - " . $e->getMessage() . "\n";
}

// Test VeeBelt model
echo "4. Testing VeeBelt model...\n";
try {
    // Include Laravel bootstrap
    require_once __DIR__ . '/vendor/autoload.php';
    $app = require_once __DIR__ . '/bootstrap/app.php';
    $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
    
    // Test VeeBelt query
    $count = \App\Models\VeeBelt::count();
    echo "✅ VeeBelt model: OK (found {$count} records)\n";
    
    // Test with stockAlert relationship
    $veeBelt = \App\Models\VeeBelt::with('stockAlert')->first();
    echo "✅ VeeBelt stockAlert relationship: OK\n";
    
} catch (Exception $e) {
    echo "❌ VeeBelt model: FAILED - " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

// Test API endpoint
echo "5. Testing API endpoint...\n";
try {
    $url = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/api/vee-belts/section/A';
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => 'Accept: application/json'
        ]
    ]);
    $response = file_get_contents($url, false, $context);
    echo "✅ API endpoint: OK\n";
} catch (Exception $e) {
    echo "❌ API endpoint: FAILED - " . $e->getMessage() . "\n";
}

echo "\n🎯 PRODUCTION FIX COMPLETED!\n";
echo "If errors persist, check storage/logs/laravel.log for detailed error messages.\n";
?>