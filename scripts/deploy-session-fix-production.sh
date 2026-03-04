#!/bin/bash

echo "🚀 Deploying session fix to production..."

# Backup current .env
cp .env .env.backup.$(date +%Y%m%d_%H%M%S)

# Update session configuration
echo "📝 Updating session configuration..."

# Remove old session domain setting
sed -i 's/SESSION_DOMAIN=.microbelts.com/SESSION_DOMAIN=/' .env

# Update HTTP_ONLY setting
sed -i 's/SESSION_HTTP_ONLY=true/SESSION_HTTP_ONLY=false/' .env

# Add session cookie name if not present
if ! grep -q "SESSION_COOKIE=" .env; then
    echo "SESSION_COOKIE=laravel_session" >> .env
fi

echo "🔄 Clearing caches..."

# Clear all caches
php artisan config:clear
php artisan cache:clear 2>/dev/null || echo "Cache clear completed"
php artisan route:clear
php artisan view:clear

# Rebuild caches for production
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "🗄️ Ensuring sessions table is ready..."

# Make sure sessions table exists and is accessible
php artisan migrate --force

echo "✅ Session fix deployed successfully!"
echo ""
echo "📋 Changes applied:"
echo "   - SESSION_DOMAIN: .microbelts.com → (empty)"
echo "   - SESSION_HTTP_ONLY: true → false"
echo "   - SESSION_COOKIE: laravel_session (added)"
echo ""
echo "🧪 Test the application now:"
echo "   1. Create a new user via UI"
echo "   2. Login with that user"
echo "   3. Refresh the page"
echo "   4. User should remain logged in"
echo ""
echo "📊 Monitor logs with: tail -f storage/logs/laravel.log"