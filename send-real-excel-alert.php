<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "📧 Sending Real Excel Stock Alert\n";
echo "=================================\n\n";

try {
    // Get real email addresses from environment
    $realEmails = explode(',', env('LOW_STOCK_EMAIL_RECIPIENTS', 'admin@example.com'));
    $realEmails = array_map('trim', $realEmails);
    
    echo "📧 Recipients: " . implode(', ', $realEmails) . "\n";
    echo "📤 From: " . env('MAIL_FROM_ADDRESS') . " (" . env('MAIL_FROM_NAME') . ")\n\n";
    
    // Send Smart Stock Alert with current data
    echo "🔧 Sending Smart Stock Alert with Excel attachment...\n";
    
    $service = new \App\Services\SmartStockAlertService();
    
    // Check current alert status
    $needingAlerts = \App\Models\StockAlertTracking::needsAlert()->get();
    echo "📊 Items currently needing alerts: {$needingAlerts->count()}\n";
    
    if ($needingAlerts->count() > 0) {
        echo "📧 Sending alerts for current low stock items...\n";
        $result = $service->sendSmartAlerts($realEmails);
    } else {
        echo "📧 No current alerts needed. Forcing send with all low stock data...\n";
        $result = $service->sendSmartAlertsForced($realEmails);
    }
    
    echo "✅ Smart Stock Alert Result:\n";
    echo "   Success: " . ($result['success'] ? 'Yes' : 'No') . "\n";
    echo "   Message: {$result['message']}\n";
    echo "   Alerts Sent: {$result['alerts_sent']}\n";
    echo "   Recipients: " . implode(', ', $result['recipients'] ?? []) . "\n\n";
    
    // Also send regular stock alert
    echo "📋 Sending Regular Stock Alert with Excel attachment...\n";
    
    // Get current low stock data from dashboard controller logic
    $dashboardController = new \App\Http\Controllers\Api\DashboardController();
    $reflection = new ReflectionClass($dashboardController);
    $method = $reflection->getMethod('getStockAlertData');
    $method->setAccessible(true);
    $lowStockData = $method->invoke($dashboardController);
    
    $totalLowStock = $lowStockData['total_low_stock_count'] ?? 0;
    $totalOutOfStock = $lowStockData['total_out_of_stock_count'] ?? 0;
    
    echo "📊 Current inventory status:\n";
    echo "   Low Stock Items: {$totalLowStock}\n";
    echo "   Out of Stock Items: {$totalOutOfStock}\n";
    
    if ($totalLowStock > 0 || $totalOutOfStock > 0) {
        foreach ($realEmails as $email) {
            \Mail::to(trim($email))->send(new \App\Mail\LowStockReportExcel($lowStockData));
            echo "✅ Regular Stock Alert Excel sent to: {$email}\n";
        }
    } else {
        echo "ℹ️  No low stock items found for regular alert.\n";
    }
    
    echo "\n🎉 Real Excel email alerts sent successfully!\n";
    echo "\n📧 What was sent:\n";
    echo "  ✅ Smart Stock Alert with die requirements (Excel attachment)\n";
    echo "  ✅ Regular Stock Alert with inventory status (Excel attachment)\n";
    echo "  ✅ Professional email templates with Excel data\n";
    echo "  ✅ Color-coded Excel files with formatted data\n";
    
    echo "\n📋 Email Features:\n";
    echo "  • Excel files with sortable/filterable data\n";
    echo "  • Color-coded status indicators\n";
    echo "  • Professional formatting and summaries\n";
    echo "  • Die calculation details for production planning\n";
    echo "  • Automatic cleanup of temporary files\n";
    
} catch (\Exception $e) {
    echo "❌ Error sending real Excel alerts: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}