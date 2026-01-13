<?php

require_once 'vendor/autoload.php';

// Load Laravel app
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🔍 Verifying session configuration...\n\n";

// Check environment variables
echo "📋 Current session configuration:\n";
echo "SESSION_DRIVER: " . env('SESSION_DRIVER') . "\n";
echo "SESSION_LIFETIME: " . env('SESSION_LIFETIME') . " minutes\n";
echo "SESSION_DOMAIN: " . (env('SESSION_DOMAIN') ?: 'null') . "\n";
echo "SESSION_SECURE_COOKIE: " . (env('SESSION_SECURE_COOKIE') ? 'true' : 'false') . "\n";
echo "CACHE_STORE: " . env('CACHE_STORE') . "\n\n";

// Check if sessions table exists
try {
    $pdo = new PDO(
        'mysql:host=' . env('DB_HOST') . ';port=' . env('DB_PORT') . ';dbname=' . env('DB_DATABASE'),
        env('DB_USERNAME'),
        env('DB_PASSWORD')
    );
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'sessions'");
    $sessionTableExists = $stmt->rowCount() > 0;
    
    echo "🗄️ Database connection: ✅ Connected\n";
    echo "📊 Sessions table: " . ($sessionTableExists ? "✅ Exists" : "❌ Missing") . "\n";
    
    if ($sessionTableExists) {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM sessions");
        $sessionCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "📈 Active sessions: " . $sessionCount . "\n";
        
        // Show table structure
        $stmt = $pdo->query("DESCRIBE sessions");
        echo "\n📋 Sessions table structure:\n";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "   - {$row['Field']}: {$row['Type']}\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
}

echo "\n🔧 Recommendations:\n";

if (env('SESSION_DRIVER') !== 'database') {
    echo "⚠️  Change SESSION_DRIVER to 'database' for better reliability\n";
}

if (env('SESSION_LIFETIME') < 1440) {
    echo "⚠️  Consider increasing SESSION_LIFETIME to 1440 minutes (24 hours)\n";
}

if (!env('SESSION_DOMAIN')) {
    echo "⚠️  Set SESSION_DOMAIN to '.microbelts.com' for proper cookie handling\n";
}

if (!$sessionTableExists) {
    echo "⚠️  Run: php artisan session:table && php artisan migrate\n";
}

echo "\n✅ Verification complete!\n";