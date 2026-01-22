<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Stock Alert Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        .summary {
            background-color: #e8f5e8;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #4caf50;
        }
        .alert-summary {
            background-color: #fff3e0;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #ff9800;
        }
        .attachment-info {
            background-color: #e3f2fd;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #2196f3;
        }
        .no-items {
            text-align: center;
            color: #666;
            font-style: italic;
            padding: 20px;
            background-color: #e8f5e8;
            border-radius: 5px;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            text-align: center;
            color: #666;
            font-size: 14px;
        }
        .highlight {
            background-color: #fff3e0;
            padding: 2px 6px;
            border-radius: 3px;
            font-weight: bold;
        }
        .low-stock {
            color: #ff9800;
            font-weight: bold;
        }
        .out-of-stock {
            color: #d32f2f;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📊 Daily Stock Alert Report</h1>
        <h2>Inventory Status Update</h2>
        <p><strong>Report Date:</strong> {{ $reportDate }}</p>
        <p><strong>Generated At:</strong> {{ now()->format('Y-m-d H:i:s') }}</p>
    </div>

    <div class="summary">
        <h2>📋 Alert Summary</h2>
        <p><strong>Total Low Stock Items:</strong> <span class="low-stock">{{ $lowStockData['total_low_stock_count'] ?? 0 }}</span></p>
        <p><strong>Total Out of Stock Items:</strong> <span class="out-of-stock">{{ $lowStockData['total_out_of_stock_count'] ?? 0 }}</span></p>
        <p><strong>Total Items Requiring Attention:</strong> {{ ($lowStockData['total_low_stock_count'] ?? 0) + ($lowStockData['total_out_of_stock_count'] ?? 0) }}</p>
    </div>

    <div class="attachment-info">
        <h2>📎 Excel Report Attached</h2>
        <p><strong>Attachment:</strong> {{ $excelFileName }}</p>
        <p>The complete inventory alert report with all low stock and out of stock items is available in the attached Excel file.</p>
        <p><strong>Excel Features:</strong></p>
        <ul>
            <li>Complete item details with current stock levels and reorder points</li>
            <li>Separate sections for low stock and out of stock items</li>
            <li>Color-coded status indicators for easy identification</li>
            <li>Sortable and filterable data for inventory analysis</li>
            <li>Rate information for cost calculations</li>
        </ul>
    </div>

    @php
        $hasLowStock = !empty($lowStockData['low_stock_items']);
        $hasOutOfStock = !empty($lowStockData['out_of_stock_items']);
        $totalAlerts = ($lowStockData['total_low_stock_count'] ?? 0) + ($lowStockData['total_out_of_stock_count'] ?? 0);
    @endphp

    @if($totalAlerts == 0)
        <div class="no-items">
            <h3>🎉 Excellent News!</h3>
            <p>No low stock or out of stock items found.</p>
            <p>All inventory levels are above their reorder points.</p>
        </div>
    @else
        <div class="alert-summary">
            <h2>⚠️ Items Requiring Attention</h2>
            @if($hasLowStock)
                <p><strong>Low Stock Items:</strong> {{ $lowStockData['total_low_stock_count'] }} items below reorder level but still in stock</p>
            @endif
            @if($hasOutOfStock)
                <p><strong>Out of Stock Items:</strong> {{ $lowStockData['total_out_of_stock_count'] }} items with zero inventory</p>
            @endif
            <p><em>Please review the attached Excel file for complete details and take appropriate action to replenish inventory.</em></p>
        </div>

        <div style="background-color: #e3f2fd; padding: 15px; border-radius: 5px; margin-top: 20px;">
            <h3>📋 Quick Action Items</h3>
            @if($hasOutOfStock)
                <p><strong>Priority 1:</strong> Immediate attention required for {{ $lowStockData['total_out_of_stock_count'] }} out of stock items</p>
            @endif
            @if($hasLowStock)
                <p><strong>Priority 2:</strong> Reorder {{ $lowStockData['total_low_stock_count'] }} low stock items to prevent stockouts</p>
            @endif
            <p><strong>Recommendation:</strong> Review reorder levels and supplier lead times to optimize inventory management</p>
        </div>
    @endif

    <div class="footer">
        <p><strong>Inventory Management Guidelines:</strong></p>
        <p><strong>Low Stock:</strong> Items below reorder level but still available</p>
        <p><strong>Out of Stock:</strong> Items with zero inventory requiring immediate attention</p>
        <p><strong>Note:</strong> This report is generated daily to help maintain optimal inventory levels.</p>
        <p>This is an automated report generated by the Smart Belt Inventory Management System.</p>
        <p>Report generated on {{ now()->format('Y-m-d H:i:s') }}</p>
    </div>
</body>
</html>