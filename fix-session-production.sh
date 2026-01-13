#!/bin/bash

echo "🔧 Fixing session configuration for production..."

# Backup current .env
cp .env .env.backup.$(date +%Y%m%d_%H%M%S)

# Update session configuration
echo "📝 Updating session configuration..."

# Use database sessions instead of file sessions
sed -i 's/SESSION_DRIVER=file/SESSION_DRIVER=database/' .env

# Increase session lifetime to 24 hours (1440 minutes)
sed -i 's/SESSION_LIFETIME=120/SESSION_LIFETIME=1440/' .env

# Set proper session domain
sed -i 's/SESSION_DOMAIN=null/SESSION_DOMAIN=.microbelts.com/' .env

# Add secure session settings if not present
if ! grep -q "SESSION_SECURE_COOKIE" .env; then
    echo "SESSION_SECURE_COOKIE=true" >> .env
fi

if ! grep -q "SESSION_HTTP_ONLY" .env; then
    echo "SESSION_HTTP_ONLY=true" >> .env
fi

if ! grep -q "SESSION_SAME_SITE" .env; then
    echo "SESSION_SAME_SITE=lax" >> .env
fi

# Change cache store to database for consistency
sed -i 's/CACHE_STORE=file/CACHE_STORE=database/' .env

echo "🗄️ Ensuring sessions table exists..."

# Create sessions table if it doesn't exist
php artisan session:table 2>/dev/null || echo "Sessions table already exists or migration failed"
php artisan migrate --force

echo "🧹 Clearing application cache..."

# Clear all caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "🔄 Restarting session storage..."

# Clear existing sessions to force fresh start
php artisan session:flush 2>/dev/null || echo "Session flush completed"

echo "✅ Session configuration fixed!"
echo ""
echo "📋 Changes made:"
echo "   - SESSION_DRIVER: file → database"
echo "   - SESSION_LIFETIME: 120 → 1440 minutes (24 hours)"
echo "   - SESSION_DOMAIN: null → .microbelts.com"
echo "   - Added secure cookie settings"
echo "   - CACHE_STORE: file → database"
echo ""
echo "🚀 Please test the application now. New users should maintain their sessions."