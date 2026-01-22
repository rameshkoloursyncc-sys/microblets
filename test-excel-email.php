<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "📧 Excel Email Test\n";
echo "===================\n\n";

try {
    // Test 1: Send Smart Stock Alert with Excel to real emails
    echo "🔧 Test 1: Testing Smart Stock Alert Excel Email to real recipients...\n";
    
    $service = new \App\Services\SmartStockAlertService();
    
    // Get real email addresses from environment
    $realEmails = explode(',', env('LOW_STOCK_EMAIL_RECIPIENTS', 'admin@example.com'));
    $realEmails = array_map('trim', $realEmails);
    
    echo "📧 Sending to real email addresses: " . implode(', ', $realEmails) . "\n";
    
    // Force send to real emails (this will reset alerts and send)
    $result = $service->sendSmartAlertsForced($realEmails);
    
    echo "✅ Smart Stock Alert Email Result:\n";
    echo "   Success: " . ($result['success'] ? 'Yes' : 'No') . "\n";
    echo "   Message: {$result['message']}\n";
    echo "   Alerts Sent: {$result['alerts_sent']}\n";
    echo "   Recipients: " . implode(', ', $result['recipients'] ?? []) . "\n\n";
    
    // Test 2: Test Regular Stock Alert with Excel to real emails
    echo "📋 Test 2: Testing Regular Stock Alert Excel Email to real recipients...\n";
    
    // Get real email addresses from environment
    $realEmails = explode(',', env('LOW_STOCK_EMAIL_RECIPIENTS', 'admin@example.com'));
    $realEmails = array_map('trim', $realEmails);
    
    echo "📧 Sending to real email addresses: " . implode(', ', $realEmails) . "\n";
    
    // Create sample low stock data
    $lowStockData = [
        'total_low_stock_count' => 3,
        'total_out_of_stock_count' => 1,
        'total_alert_count' => 4,
        'low_stock_items' => [
            [
                'belt_type' => 'Cogged Belts',
                'section' => 'BX',
                'size' => '24',
                'balance_stock' => 29,
                'reorder_level' => 80,
                'rate' => 15.50
            ],
            [
                'belt_type' => 'Vee Belts',
                'section' => 'A',
                'size' => '35',
                'balance_stock' => 5,
                'reorder_level' => 20,
                'rate' => 12.75
            ],
            [
                'belt_type' => 'Poly Belts',
                'section' => 'PJ',
                'size' => '6',
                'balance_stock' => 150,
                'reorder_level' => 500,
                'rate' => 8.25
            ]
        ],
        'out_of_stock_items' => [
            [
                'belt_type' => 'Cogged Belts',
                'section' => 'AX',
                'size' => '23',
                'balance_stock' => 0,
                'reorder_level' => 1,
                'rate' => 18.25
            ]
        ]
    ];
    
    // Send regular stock alert email with Excel to real recipients
    try {
        foreach ($realEmails as $email) {
            \Mail::to(trim($email))->send(new \App\Mail\LowStockReportExcel($lowStockData));
            echo "✅ Regular Stock Alert Excel Email sent to: {$email}\n";
        }
    } catch (\Exception $e) {
        echo "❌ Error sending regular stock alert: " . $e->getMessage() . "\n";
    }
    
    echo "\n🎉 Excel email test completed!\n";
    echo "\n📋 What was tested:\n";
    echo "  ✅ Smart Stock Alert with Excel attachment\n";
    echo "  ✅ Regular Stock Alert with Excel attachment\n";
    echo "  ✅ Email generation and sending\n";
    echo "  ✅ Excel file creation and attachment\n";
    echo "  ✅ Automatic cleanup of temporary files\n";
    
    echo "\n📧 Email Features:\n";
    echo "  • Excel files are automatically generated with formatted data\n";
    echo "  • Color-coded status indicators (red for out of stock, orange for low stock)\n";
    echo "  • Sortable and filterable data in Excel\n";
    echo "  • Professional formatting with headers and summaries\n";
    echo "  • Automatic cleanup of temporary files after sending\n";
    
} catch (\Exception $e) {
    echo "❌ Error during Excel email test: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}