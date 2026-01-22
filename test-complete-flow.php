<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔄 Complete Flow Test\n";
echo "====================\n\n";

// Step 1: Check current state
echo "📊 Step 1: Current state before sending alerts...\n";
$service = new \App\Services\SmartStockAlertService();

$needingAlerts = \App\Models\StockAlertTracking::needsAlert()->get();
echo "Items needing alerts: {$needingAlerts->count()}\n";

$dieRequirements = $service->getDieRequirementsUnalerted();
echo "Die requirements (unalerted): " . json_encode($dieRequirements->toArray(), JSON_PRETTY_PRINT) . "\n\n";

// Step 2: Send alerts (simulate what dashboard does)
echo "📧 Step 2: Sending smart stock alerts...\n";
try {
    $result = $service->sendSmartAlerts(['test@example.com']);
    echo "Alert result: " . json_encode($result, JSON_PRETTY_PRINT) . "\n\n";
} catch (\Exception $e) {
    echo "Error sending alerts: " . $e->getMessage() . "\n\n";
}

// Step 3: Check state after sending alerts
echo "📊 Step 3: State after sending alerts...\n";
$needingAlertsAfter = \App\Models\StockAlertTracking::needsAlert()->get();
echo "Items needing alerts after: {$needingAlertsAfter->count()}\n";

$dieRequirementsAfter = $service->getDieRequirementsUnalerted();
echo "Die requirements (unalerted) after: " . json_encode($dieRequirementsAfter->toArray(), JSON_PRETTY_PRINT) . "\n\n";

// Step 4: Check specific cogged belt items
echo "🔧 Step 4: Checking specific cogged belt items...\n";
$coggedItems = \App\Models\CoggedBelt::with('stockAlert')
    ->whereNotNull('reorder_level')
    ->where('reorder_level', '>=', 1)
    ->whereColumn('balance_stock', '<=', 'reorder_level')
    ->get();

foreach ($coggedItems->take(5) as $item) {
    $alertStatus = $item->stockAlert && $item->stockAlert->alert_sent ? 'YELLOW (sent)' : 'RED (not sent)';
    echo "  {$item->section}-{$item->size}: stock={$item->balance_stock}, reorder={$item->reorder_level} → {$alertStatus}\n";
}

echo "\n✅ Complete flow test finished!\n";
echo "\n📋 Expected behavior:\n";
echo "  - Before alerts: Some items need alerts, die requirements show those items\n";
echo "  - After alerts: No items need alerts, die requirements should be empty\n";
echo "  - Frontend should refresh and show YELLOW colors for alerted items\n";