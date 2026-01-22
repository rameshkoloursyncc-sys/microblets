<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "📊 Excel Generation Test\n";
echo "========================\n\n";

try {
    // Test 1: Generate Smart Stock Alert Excel
    echo "🔧 Test 1: Generating Smart Stock Alert Excel...\n";
    
    $service = new \App\Services\SmartStockAlertService();
    $itemsNeedingAlerts = $service->getItemsNeedingAlerts();
    
    if ($itemsNeedingAlerts->isEmpty()) {
        echo "No items needing alerts, creating sample data...\n";
        $alertData = [
            'generated_at' => now()->toDateTimeString(),
            'total_items' => 0,
            'total_dies_needed' => 0,
            'belt_types' => []
        ];
    } else {
        $alertData = $service->prepareAlertData($itemsNeedingAlerts);
    }
    
    $excelService = new \App\Services\ExcelExportService();
    $fileInfo = $excelService->generateSmartStockAlertFile($alertData);
    
    echo "✅ Smart Stock Alert Excel generated:\n";
    echo "   File: {$fileInfo['filename']}\n";
    echo "   Path: {$fileInfo['path']}\n";
    echo "   Size: " . number_format($fileInfo['size']) . " bytes\n\n";
    
    // Test 2: Generate Regular Stock Alert Excel
    echo "📋 Test 2: Generating Regular Stock Alert Excel...\n";
    
    // Get sample low stock data
    $lowStockData = [
        'total_low_stock_count' => 5,
        'total_out_of_stock_count' => 2,
        'total_alert_count' => 7,
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
    
    $fileInfo2 = $excelService->generateStockAlertFile($lowStockData);
    
    echo "✅ Regular Stock Alert Excel generated:\n";
    echo "   File: {$fileInfo2['filename']}\n";
    echo "   Path: {$fileInfo2['path']}\n";
    echo "   Size: " . number_format($fileInfo2['size']) . " bytes\n\n";
    
    // Test 3: Verify files exist and are readable
    echo "🔍 Test 3: Verifying generated files...\n";
    
    if (file_exists($fileInfo['path']) && is_readable($fileInfo['path'])) {
        echo "✅ Smart Stock Alert Excel file is accessible\n";
    } else {
        echo "❌ Smart Stock Alert Excel file is not accessible\n";
    }
    
    if (file_exists($fileInfo2['path']) && is_readable($fileInfo2['path'])) {
        echo "✅ Regular Stock Alert Excel file is accessible\n";
    } else {
        echo "❌ Regular Stock Alert Excel file is not accessible\n";
    }
    
    echo "\n🎉 Excel generation test completed successfully!\n";
    echo "\n📋 Next steps:\n";
    echo "  1. Test email sending with Excel attachments\n";
    echo "  2. Verify Excel files open correctly in spreadsheet applications\n";
    echo "  3. Check email delivery and attachment handling\n";
    
    // Clean up test files
    if (file_exists($fileInfo['path'])) {
        unlink($fileInfo['path']);
        echo "\n🧹 Cleaned up test file: {$fileInfo['filename']}\n";
    }
    
    if (file_exists($fileInfo2['path'])) {
        unlink($fileInfo2['path']);
        echo "🧹 Cleaned up test file: {$fileInfo2['filename']}\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Error during Excel generation test: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}