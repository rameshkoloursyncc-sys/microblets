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
            max-width: 800px;
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
            background-color: #e3f2fd;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .belt-section {
            margin-bottom: 30px;
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
        }
        .belt-header {
            background-color: #2196f3;
            color: white;
            padding: 15px;
            font-weight: bold;
            font-size: 18px;
        }
        .belt-content {
            padding: 15px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            padding: 8px 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        .low-stock {
            color: #d32f2f;
            font-weight: bold;
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
    </style>
</head>
<body>
    <div class="header">
        <h1>Daily Stock Alert Report</h1>
        <p><strong>Report Date:</strong> {{ $reportDate }}</p>
        <p><strong>Generated At:</strong> {{ $lowStockData['generated_at'] ?? now()->format('Y-m-d H:i:s') }}</p>
    </div>

    <div class="summary">
        <h2>Summary</h2>
        <p><strong>Total Low Stock Items:</strong> {{ $lowStockData['total_low_stock_count'] ?? 0 }}</p>
        <p><strong>Total Out of Stock Items:</strong> {{ $lowStockData['total_out_of_stock_count'] ?? 0 }}</p>
        <p><strong>Total Alert Items:</strong> {{ $lowStockData['total_alert_count'] ?? 0 }}</p>
        <p><strong>Belt Types Affected:</strong> {{ count(array_merge($lowStockData['low_stock_items'] ?? [], $lowStockData['out_of_stock_items'] ?? [])) }}</p>
    </div>

    @if(empty($lowStockData['low_stock_items']) && empty($lowStockData['out_of_stock_items']))
        <div class="no-items">
            <h3>🎉 Great News!</h3>
            <p>No items are currently running low on stock or out of stock.</p>
            <p>All tracked inventory levels are above their reorder points.</p>
        </div>
    @else
        <!-- LOW STOCK SECTION -->
        @if(!empty($lowStockData['low_stock_items']))
            <h2 style="color: #ff9800; margin-top: 30px; margin-bottom: 20px;">⚠️ Low Stock Items ({{ $lowStockData['total_low_stock_count'] ?? 0 }})</h2>
            @foreach($lowStockData['low_stock_items'] as $beltType => $data)
                <div class="belt-section">
                    <div class="belt-header" style="background-color: #ff9800;">
                        {{ $data['name'] }} ({{ $data['count'] }} items)
                    </div>
                    <div class="belt-content">
                        <table>
                            <thead>
                                <tr>
                                    <th>Section</th>
                                    <th>Size</th>
                                    <th>SKU</th>
                                    <th>Current Stock</th>
                                    <th>Reorder Level</th>
                                    <th>Value (₹)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($data['items'] as $item)
                                    <tr>
                                        <td><strong>{{ $item->section }}</strong></td>
                                        <td>{{ $item->size }}</td>
                                        <td>{{ $item->sku ?? 'N/A' }}</td>
                                        <td class="low-stock">{{ number_format($item->current_stock, 2) }}</td>
                                        <td>{{ $item->reorder_level }}</td>
                                        <td>₹{{ number_format($item->value ?? 0, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach
        @endif

        <!-- OUT OF STOCK SECTION -->
        @if(!empty($lowStockData['out_of_stock_items']))
            <h2 style="color: #d32f2f; margin-top: 30px; margin-bottom: 20px;">🚨 Out of Stock Items ({{ $lowStockData['total_out_of_stock_count'] ?? 0 }})</h2>
            @foreach($lowStockData['out_of_stock_items'] as $beltType => $data)
                <div class="belt-section">
                    <div class="belt-header" style="background-color: #d32f2f;">
                        {{ $data['name'] }} ({{ $data['count'] }} items)
                    </div>
                    <div class="belt-content">
                        <table>
                            <thead>
                                <tr>
                                    <th>Section</th>
                                    <th>Size</th>
                                    <th>SKU</th>
                                    <th>Current Stock</th>
                                    <th>Reorder Level</th>
                                    <th>Value (₹)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($data['items'] as $item)
                                    <tr>
                                        <td><strong>{{ $item->section }}</strong></td>
                                        <td>{{ $item->size }}</td>
                                        <td>{{ $item->sku ?? 'N/A' }}</td>
                                        <td style="color: #d32f2f; font-weight: bold;">{{ number_format($item->current_stock, 2) }}</td>
                                        <td>{{ $item->reorder_level }}</td>
                                        <td>₹{{ number_format($item->value ?? 0, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach
        @endif
    @endif

    <div class="footer">
        <p><strong>Note:</strong> This report includes items with reorder_level >= 1. Items with reorder_level = null or 0 are not tracked for stock alerts.</p>
        <p><strong>Low Stock:</strong> Items with stock > 0 but <= reorder_level</p>
        <p><strong>Out of Stock:</strong> Items with stock = 0</p>
        <p>This is an automated report generated by the Belt Inventory Management System.</p>
        <p>Report generated on {{ now()->format('Y-m-d H:i:s') }}</p>
    </div>
</body>
</html>