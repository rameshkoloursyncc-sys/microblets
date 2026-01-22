<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Stock Alert Report - Dies Required</title>
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
        .section-header {
            background-color: #f5f5f5;
            padding: 10px 15px;
            font-weight: bold;
            border-bottom: 1px solid #ddd;
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
        .dies-needed {
            color: #ff5722;
            font-weight: bold;
            font-size: 16px;
        }
        .stock-level {
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
        .highlight {
            background-color: #fff3e0;
            padding: 2px 6px;
            border-radius: 3px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🏭 Smart Stock Alert Report</h1>
        <h2>Dies Required for Production</h2>
        <p><strong>Report Date:</strong> {{ $reportDate }}</p>
        <p><strong>Generated At:</strong> {{ $alertData['generated_at'] ?? now()->format('Y-m-d H:i:s') }}</p>
    </div>

    <div class="summary">
        <h2>📊 Alert Summary</h2>
        <p><strong>Total Items Needing Attention:</strong> {{ $alertData['total_items'] ?? 0 }}</p>
        <p><strong>Belt Types Affected:</strong> {{ count($alertData['belt_types'] ?? []) }}</p>
    </div>

    <div class="dies-summary">
        <h2>🔧 Dies Required Summary</h2>
        <p><strong>Total Dies Needed:</strong> <span class="dies-needed">{{ $alertData['total_dies_needed'] ?? 0 }} dies</span></p>
        <p><em>This represents the minimum number of dies required to bring all low-stock items back to their reorder levels.</em></p>
    </div>

    @if(empty($alertData['belt_types']))
        <div class="no-items">
            <h3>🎉 Excellent News!</h3>
            <p>No items currently require die production alerts.</p>
            <p>All tracked inventory levels are above their reorder points or have already been alerted.</p>
        </div>
    @else
        @foreach($alertData['belt_types'] as $beltType => $beltData)
            <div class="belt-section">
                <div class="belt-header">
                    {{ $beltData['name'] }} 
                    <span style="float: right;">
                        {{ $beltData['total_items'] }} items | 
                        <span class="highlight">{{ $beltData['total_dies'] }} dies needed</span>
                    </span>
                </div>
                
                @foreach($beltData['sections'] as $section => $sectionData)
                    <div class="section-header">
                        Section {{ $section }} 
                        <span style="float: right;">
                            {{ $sectionData['count'] }} items | 
                            <span class="dies-needed">{{ $sectionData['dies_needed'] }} dies</span>
                        </span>
                    </div>
                    <div class="belt-content">
                        <table>
                            <thead>
                                <tr>
                                    <th>Product SKU</th>
                                    <th>Current Stock</th>
                                    <th>Reorder Level</th>
                                    <th>Stock per Die</th>
                                    <th>Dies Needed</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($sectionData['items'] as $item)
                                    <tr>
                                        <td><strong>{{ $item->product_sku ?? 'N/A' }}</strong></td>
                                        <td class="stock-level">{{ number_format($item->current_stock, 2) }}</td>
                                        <td>{{ number_format($item->reorder_level, 2) }}</td>
                                        <td>{{ number_format($item->stock_per_die, 2) }}</td>
                                        <td class="dies-needed">{{ $item->dies_needed }}</td>
                                        <td>
                                            @if($item->current_stock == 0)
                                                <span style="color: #d32f2f; font-weight: bold;">OUT OF STOCK</span>
                                            @else
                                                <span style="color: #ff9800; font-weight: bold;">LOW STOCK</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endforeach
            </div>
        @endforeach

        <div style="background-color: #e3f2fd; padding: 15px; border-radius: 5px; margin-top: 20px;">
            <h3>📋 Production Planning Summary</h3>
            @foreach($alertData['belt_types'] as $beltType => $beltData)
                <p><strong>{{ $beltData['name'] }}:</strong> {{ $beltData['total_dies'] }} dies needed across {{ count($beltData['sections']) }} sections</p>
            @endforeach
        </div>
    @endif

    <div class="footer">
        <p><strong>How Dies are Calculated:</strong></p>
        <p>Dies Needed = CEIL((Reorder Level - Current Stock) / Stock per Die)</p>
        <p><strong>Note:</strong> This report only includes items that haven't been alerted yet and are below their reorder levels.</p>
        <p><strong>Smart Alerts:</strong> Once an alert is sent for an item, it won't appear again until stock is replenished above the reorder level.</p>
        <p>This is an automated report generated by the Smart Belt Inventory Management System.</p>
        <p>Report generated on {{ now()->format('Y-m-d H:i:s') }}</p>
    </div>
</body>
</html>