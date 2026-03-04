#!/bin/bash

# Dashboard Fix Deployment Script for Shared Hosting
echo "🚀 Deploying dashboard calculation fixes..."

# Clear Laravel caches (shared hosting compatible)
echo "🧹 Clearing Laravel caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Optimize for production (shared hosting compatible)
echo "⚡ Optimizing for production..."
php artisan config:cache
php artisan route:cache

echo "✅ Dashboard fix deployment complete!"
echo ""
echo "🔧 Changes made:"
echo "- Added column existence checks for all belt types"
echo "- Fixed poly belts to use 'ribs' column instead of 'balance_stock'"
echo "- Fixed TPU belts to use 'meter' column instead of 'balance_stock'"
echo "- Added fallback handling for missing 'value' columns"
echo "- Added proper error handling for all belt calculations"
echo ""
echo "📋 Test the dashboard at: https://inventory.microbelts.com/inventory"
echo ""
echo "📝 Manual steps for shared hosting:"
echo "1. Upload the updated app/Http/Controllers/Api/DashboardController.php file"
echo "2. Run this script on the server: bash deploy_dashboard_fix.sh"
echo "3. Test the dashboard functionality"