<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🧪 Complete Stock Alert System Test\n";
echo "===================================\n\n";

// Test 1: Check low stock items
echo "📊 Test 1: Checking low stock CoggedBelt items...\n";
$lowStockItems = \App\Models\CoggedBelt::with('stockAlert')
    ->whereNotNull('reorder_level')
    ->where('reorder_level', '>=', 1)
    ->whereColumn('balance_stock', '<=', 'reorder_level')
    ->get();

echo "Found {$lowStockItems->count()} low stock items:\n";
foreach ($lowStockItems->take(5) as $item) {
    $alertStatus = $item->stockAlert && $item->stockAlert->alert_sent ? 'YELLOW (sent)' : 'RED (not sent)';
    echo "  {$item->section}-{$item->size}: stock={$item->balance_stock}, reorder={$item->reorder_level} → {$alertStatus}\n";
}

// Test 2: Sync tracking and check
echo "\n🔄 Test 2: Syncing stock alert tracking...\n";
$service = new \App\Services\SmartStockAlertService();
$service->syncStockAlertTracking();

$trackingCount = \App\Models\StockAlertTracking::where('belt_type', 'cogged')
    ->where('is_active', true)
    ->count();
echo "Active tracking records: {$trackingCount}\n";

// Test 3: Check items needing alerts
echo "\n📧 Test 3: Checking items needing alerts...\n";
$needingAlerts = \App\Models\StockAlertTracking::needsAlert()
    ->where('belt_type', 'cogged')
    ->get();
echo "Items needing alerts: {$needingAlerts->count()}\n";

// Test 4: Check items with alerts sent
echo "\n🟡 Test 4: Checking items with alerts sent...\n";
$alertsSent = \App\Models\StockAlertTracking::where('belt_type', 'cogged')
    ->where('alert_sent', true)
    ->where('is_active', true)
    ->get();
echo "Items with alerts sent: {$alertsSent->count()}\n";
foreach ($alertsSent->take(3) as $item) {
    echo "  {$item->product_sku}: sent at {$item->alert_sent_at}\n";
}

// Test 5: Test the color logic
echo "\n🎨 Test 5: Testing color logic...\n";
foreach ($lowStockItems->take(3) as $item) {
    $color = 'GREEN';
    if ($item->balance_stock <= 0) {
        // Out of stock - check if alert sent
        if ($item->stockAlert && $item->stockAlert->alert_sent) {
            $color = 'YELLOW';
        } else {
            $color = 'RED';
        }
    } elseif ($item->reorder_level !== null && $item->reorder_level >= 1 && $item->balance_stock <= $item->reorder_level) {
        // Low stock - check if alert sent
        if ($item->stockAlert && $item->stockAlert->alert_sent) {
            $color = 'YELLOW';
        } else {
            $color = 'RED';
        }
    }
    echo "  {$item->section}-{$item->size}: {$color}\n";
}

echo "\n✅ Test completed!\n";
echo "\n📋 Expected frontend behavior:\n";
echo "  🔴 RED: Low stock, no alert sent yet\n";
echo "  🟡 YELLOW: Low stock, alert has been sent\n";
echo "  🟢 GREEN: Stock above reorder level\n";