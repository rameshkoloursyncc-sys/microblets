#!/bin/bash

echo "🧪 Testing Stock Alert System"
echo "============================="

# Test 1: Send manual stock alert
echo ""
echo "📧 Test 1: Sending manual stock alert..."
php artisan report:low-stock --email=ramesh.koloursyncc@gmail.com

echo ""
echo "✅ Manual alert test completed"

# Test 2: Check if alerts are marked as sent
echo ""
echo "📊 Test 2: Checking stock alert tracking..."
php artisan tinker --execute="
\$tracking = \App\Models\StockAlertTracking::where('alert_sent', true)->count();
echo 'Alerts marked as sent: ' . \$tracking . PHP_EOL;

\$activeTracking = \App\Models\StockAlertTracking::where('is_active', true)->count();
echo 'Active tracking records: ' . \$activeTracking . PHP_EOL;
"

# Test 3: Test smart alert service
echo ""
echo "🏭 Test 3: Testing smart alert service..."
php artisan tinker --execute="
\$service = new \App\Services\SmartStockAlertService();
\$service->syncStockAlertTracking();
echo 'Stock alert tracking synced' . PHP_EOL;

\$summary = \$service->getDieRequirementsSummary();
echo 'Die requirements calculated for ' . \$summary->count() . ' belt types' . PHP_EOL;
"

echo ""
echo "✅ All tests completed!"
echo ""
echo "🔍 To verify the system:"
echo "1. Check frontend - low stock items should show YELLOW after alert sent"
echo "2. Check database - stock_alert_tracking table should have alert_sent = 1"
echo "3. When stock is replenished above reorder level, alert_sent should reset to 0"
echo ""
echo "📋 Next steps:"
echo "1. Set up cron job: ./setup-5pm-stock-alert.sh"
echo "2. Test frontend color changes in browser"
echo "3. Test IN/OUT operations to verify alert reset"