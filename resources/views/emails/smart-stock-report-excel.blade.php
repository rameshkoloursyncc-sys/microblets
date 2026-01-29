<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Stock Alert Report</title>
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
        .dies-summary {
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
    </style>
</head>
<body>
    <div class="header">
        @if(($alertData['total_items'] ?? 0) > 0)
            <h1>🚨 Smart Stock Alert Report</h1>
            <p><strong>{{ $alertData['total_items'] }} Items</strong> need attention with <strong>{{ $alertData['total_dies_needed'] }} Dies</strong> required</p>
        @else
            <h1>📊 Daily Inventory Summary</h1>
            <p><strong>{{ $alertData['message'] ?? 'Daily inventory status report' }}</strong></p>
        @endif
        <p><strong>Report Date:</strong> {{ \Carbon\Carbon::parse($alertData['generated_at'] ?? now())->setTimezone('Asia/Kolkata')->format('d M Y, h:i A') }} IST</p>
    </div>

    @if(($alertData['total_items'] ?? 0) > 0)
        <div class="dies-summary">
            <h2>🔧 Production Requirements</h2>
            <p><strong>Total Items Needing Dies:</strong> <span class="highlight">{{ $alertData['total_items'] }}</span></p>
            <p><strong>Total Dies Required:</strong> <span class="highlight">{{ $alertData['total_dies_needed'] }}</span></p>
            <p><strong>Belt Types Affected:</strong> {{ count($alertData['belt_types'] ?? []) }}</p>
        </div>
    @else
        <div class="summary">
            <h2>✅ Stock Status</h2>
            <p>All items with reorder levels are currently adequately stocked or have already been alerted.</p>
            <p>This report includes the complete inventory value summary for your daily review.</p>
        </div>
    @endif

    <div class="attachment-info">
        <h2>📎 Excel Report Attached</h2>
        <p><strong>Attachment:</strong> {{ $excelFileName }}</p>
        @if(($alertData['total_items'] ?? 0) > 0)
            <p>The detailed breakdown with all low-stock items, die calculations, and production planning summary is available in the attached Excel file.</p>
        @else
            <p>The complete inventory value summary with belt-wise breakdown is available in the attached Excel file.</p>
        @endif
        <p><strong>Excel Features:</strong></p>
        <ul>
            @if(($alertData['total_items'] ?? 0) > 0)
                <li>Complete item details with stock levels and die requirements</li>
                <li>Production planning summary (SIZE, MAKE, PARTY format)</li>
            @endif
            <li>Complete inventory value summary (₹{{ number_format($alertData['inventory_summary']['totals']['total_value'] ?? 0, 2) }})</li>
            <li>Belt-wise breakdown with stock status counts</li>
            <li>Formatted tables with color-coded status indicators</li>
            <li>Sortable and filterable data for easy analysis</li>
        </ul>
    </div>


    <div class="footer">
        @if(($alertData['total_items'] ?? 0) > 0)
            <p><strong>How Dies are Calculated:</strong></p>
            <p>Dies Needed = CEIL((Reorder Level - Current Stock) / Stock per Die)</p>
            <p><strong>Note:</strong> This report only includes items that haven't been alerted yet and are below their reorder levels.</p>
            <p><strong>Smart Alerts:</strong> Once an alert is sent for an item, it won't appear again until stock is replenished above the reorder level.</p>
        @else
            <p><strong>Daily Inventory Summary:</strong> This report provides a complete overview of your inventory value and stock status.</p>
            <p><strong>Next Alert:</strong> You'll receive stock alerts when items fall below reorder levels and haven't been previously alerted.</p>
        @endif
        <p>This is an automated report generated by the Microbelts Inventory Management System.</p>
        <p>Report generated on {{ \Carbon\Carbon::parse($alertData['generated_at'] ?? now())->setTimezone('Asia/Kolkata')->format('d M Y, h:i A') }} IST</p>
    </div>
</body>
</html>