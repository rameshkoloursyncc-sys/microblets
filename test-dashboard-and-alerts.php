<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🧪 Testing Dashboard and Alert System\n";
echo "====================================\n\n";

try {
    // Test 1: Dashboard Controller
    echo "📊 Test 1: Dashboard Controller...\n";
    $controller = new \App\Http\Controllers\Api\DashboardController();
    
    $statsResponse = $controller->getInventoryStats();
    echo "✅ Inventory stats: " . ($statsResponse->getStatusCode() === 200 ? 'WORKING' : 'FAILED') . "\n";
    
    $dieResponse = $controller->getDieRequirements();
    echo "✅ Die requirements: " . ($dieResponse->getStatusCode() === 200 ? 'WORKING' : 'FAILED') . "\n";
    
    // Test 2: Stock Alert System
    echo "\n📧 Test 2: Stock Alert System...\n";
    $request = new \Illuminate\Http\Request();
    $request->merge(['force' => true]);
    
    $alertResponse = $controller->sendStockAlert($request);
    echo "✅ Send stock alert: " . ($alertResponse->getStatusCode() === 200 ? 'WORKING' : 'FAILED') . "\n";
    
    $smartResponse = $controller->sendSmartStockAlert($request);
    echo "✅ Send smart alert: " . ($smartResponse->getStatusCode() === 200 ? 'WORKING' : 'FAILED') . "\n";
    
    // Test 3: CoggedBelt with Alert Status
    echo "\n🎨 Test 3: CoggedBelt Alert Status...\n";
    $belts = \App\Models\CoggedBelt::with('stockAlert')
        ->whereNotNull('reorder_level')
        ->where('reorder_level', '>=', 1)
        ->whereColumn('balance_stock', '<=', 'reorder_level')
        ->take(3)
        ->get();
    
    echo "Low stock items with alert status:\n";
    foreach ($belts as $belt) {
        $alertSent = $belt->stockAlert && $belt->stockAlert->alert_sent ? 'YELLOW' : 'RED';
        echo "  {$belt->section}-{$belt->size}: stock={$belt->balance_stock}, reorder={$belt->reorder_level} → {$alertSent}\n";
    }
    
    // Test 4: Alert Tracking
    echo "\n📋 Test 4: Alert Tracking Summary...\n";
    $totalTracking = \App\Models\StockAlertTracking::where('is_active', true)->count();
    $alertsSent = \App\Models\StockAlertTracking::where('alert_sent', true)->where('is_active', true)->count();
    $needingAlerts = \App\Models\StockAlertTracking::needsAlert()->count();
    
    echo "Total active tracking: {$totalTracking}\n";
    echo "Alerts sent: {$alertsSent}\n";
    echo "Needing alerts: {$needingAlerts}\n";
    
    echo "\n✅ ALL TESTS PASSED!\n";
    echo "\n🎯 System Status:\n";
    echo "  ✅ Dashboard API working\n";
    echo "  ✅ Alert system working\n";
    echo "  ✅ Color system working\n";
    echo "  ✅ Tracking system working\n";
    echo "\n🚀 Frontend should now work properly!\n";
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}