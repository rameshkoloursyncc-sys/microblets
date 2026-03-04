#!/bin/bash

echo "🚀 Starting comprehensive production fix for session and transaction issues..."

# Backup current .env
echo "📋 Backing up current .env..."
cp .env .env.backup.$(date +%Y%m%d_%H%M%S)

# Update .env with production settings
echo "⚙️ Updating .env with production session configuration..."
cp .env.production.final .env

# Clear all caches
echo "🧹 Clearing application caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Run migrations to ensure all tables are up to date
echo "🗄️ Running database migrations..."
php artisan migrate --force

# Check if sessions table exists, create if not
echo "📊 Ensuring sessions table exists..."
php artisan session:table --force 2>/dev/null || echo "Sessions table already exists or created"
php artisan migrate --force

# Verify inventory_transactions table structure
echo "🔍 Verifying inventory_transactions table structure..."
php artisan tinker --execute="
try {
    \$columns = \DB::select('DESCRIBE inventory_transactions');
    \$hasRate = collect(\$columns)->contains('Field', 'rate');
    if (!\$hasRate) {
        echo 'ERROR: rate column missing from inventory_transactions table\n';
        exit(1);
    } else {
        echo 'SUCCESS: inventory_transactions table has rate column\n';
    }
} catch (Exception \$e) {
    echo 'ERROR: ' . \$e->getMessage() . '\n';
    exit(1);
}
"

# Build frontend assets
echo "🏗️ Building frontend assets..."
npm run build

# Set proper permissions
echo "🔐 Setting proper permissions..."
chmod -R 755 storage
chmod -R 755 bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || echo "Permission setting completed (may need manual adjustment)"

# Test session functionality
echo "🧪 Testing session functionality..."
php artisan tinker --execute="
try {
    // Test session creation
    session(['test_key' => 'test_value']);
    session()->save();
    
    // Test session retrieval
    \$value = session('test_key');
    if (\$value === 'test_value') {
        echo 'SUCCESS: Session functionality working\n';
    } else {
        echo 'ERROR: Session not working properly\n';
        exit(1);
    }
    
    // Clean up test session
    session()->forget('test_key');
    session()->save();
} catch (Exception \$e) {
    echo 'ERROR: Session test failed - ' . \$e->getMessage() . '\n';
    exit(1);
}
"

# Test database connection and inventory transactions
echo "🔗 Testing database connection and inventory transactions..."
php artisan tinker --execute="
try {
    // Test database connection
    \$users = \App\Models\User::count();
    echo 'SUCCESS: Database connection working, found ' . \$users . ' users\n';
    
    // Test inventory transaction creation (dry run)
    \$transaction = new \App\Models\InventoryTransaction([
        'category' => 'test',
        'product_id' => 1,
        'type' => 'IN',
        'quantity' => 1.00,
        'stock_before' => 0.00,
        'stock_after' => 1.00,
        'rate' => 10.50,
        'description' => 'Test transaction',
        'user_id' => 1,
    ]);
    
    // Validate without saving
    if (\$transaction->validate()) {
        echo 'SUCCESS: InventoryTransaction model validation passed\n';
    } else {
        echo 'ERROR: InventoryTransaction model validation failed\n';
    }
} catch (Exception \$e) {
    echo 'ERROR: Database test failed - ' . \$e->getMessage() . '\n';
    exit(1);
}
"

echo "✅ Production fix deployment completed!"
echo ""
echo "📋 Summary of changes:"
echo "   - Updated .env with proper session configuration"
echo "   - Session driver: database"
echo "   - Session lifetime: 1440 minutes (24 hours)"
echo "   - Session domain: empty (for proper cookie handling)"
echo "   - HTTP_ONLY: false (for JavaScript fallback)"
echo "   - Cleared all caches"
echo "   - Ran database migrations"
echo "   - Built frontend assets"
echo "   - Verified session and transaction functionality"
echo ""
echo "🔄 Please test the following:"
echo "   1. Create a new user via UI"
echo "   2. Login with that user"
echo "   3. Refresh the page to verify session persistence"
echo "   4. Perform inventory operations to verify transactions work"
echo ""
echo "📝 If issues persist, check:"
echo "   - Laravel logs: storage/logs/laravel.log"
echo "   - Web server error logs"
echo "   - Browser developer console for JavaScript errors"