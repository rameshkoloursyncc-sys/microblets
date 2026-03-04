#!/bin/bash

# PRODUCTION DEPLOYMENT SCRIPT
# Run this on your production server after uploading files

echo "🚀 Starting Production Deployment..."

# Set production directory
PROD_DIR="/home/u558881185/public_html/inventory"
cd $PROD_DIR

echo "1. Setting permissions..."
chmod -R 775 storage/
chmod -R 775 bootstrap/cache/
chown -R www-data:www-data storage/ 2>/dev/null || echo "Note: Could not change ownership (may need sudo)"
chown -R www-data:www-data bootstrap/cache/ 2>/dev/null || echo "Note: Could not change ownership (may need sudo)"

echo "2. Creating missing directories..."
mkdir -p storage/framework/{cache,sessions,views}
mkdir -p storage/app/temp
mkdir -p storage/logs
chmod -R 775 storage/framework/

echo "3. Installing/updating dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

echo "4. Clearing all caches..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

echo "5. Rebuilding production caches..."
php artisan config:cache
php artisan route:cache

echo "6. Running migrations..."
php artisan migrate --force

echo "7. Testing configuration..."
php artisan tinker --execute="
echo 'Storage writable: ' . (is_writable(storage_path()) ? 'YES' : 'NO') . PHP_EOL;
echo 'Email config: ' . config('mail.low_stock_recipients') . PHP_EOL;
echo 'Die configs: ' . \App\Models\DieConfiguration::count() . PHP_EOL;
"

echo "8. Testing cron job..."
php artisan report:low-stock

echo "✅ Production deployment completed!"
echo ""
echo "Next steps:"
echo "1. Test the frontend at https://inventory.microbelts.com"
echo "2. Set up cron job: 0 17 * * * cd $PROD_DIR && php artisan report:low-stock"
echo "3. Monitor logs: tail -f storage/logs/laravel.log"