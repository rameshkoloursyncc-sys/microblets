<?php

require_once 'vendor/autoload.php';

// Load Laravel app
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🧪 Testing session functionality...\n\n";

// Start session
session_start();

// Test session storage
$testData = [
    'user' => [
        'id' => 999,
        'name' => 'test_user',
        'role' => 'user'
    ],
    'timestamp' => time()
];

// Store test data
session($testData);
session()->save();

echo "📝 Stored test session data\n";
echo "Session ID: " . session()->getId() . "\n";
echo "Session Driver: " . config('session.driver') . "\n";
echo "Session Lifetime: " . config('session.lifetime') . " minutes\n\n";

// Retrieve and verify
$retrievedUser = session('user');
$retrievedTimestamp = session('timestamp');

echo "📖 Retrieved session data:\n";
echo "User: " . json_encode($retrievedUser) . "\n";
echo "Timestamp: " . $retrievedTimestamp . "\n";
echo "Time difference: " . (time() - $retrievedTimestamp) . " seconds\n\n";

// Test session persistence
if ($retrievedUser && $retrievedUser['name'] === 'test_user') {
    echo "✅ Session storage: Working correctly\n";
} else {
    echo "❌ Session storage: Failed\n";
}

// Clean up test session
session()->forget('user');
session()->forget('timestamp');
session()->save();

echo "🧹 Cleaned up test data\n";
echo "✅ Session test complete!\n";