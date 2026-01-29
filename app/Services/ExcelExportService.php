<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ExcelExportService
{
    /**
     * Generate Excel file for smart stock alert data
     */
    public function generateSmartStockAlertExcel($alertData)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Set document properties
        $spreadsheet->getProperties()
            ->setCreator('Smart Belt Inventory System')
            ->setTitle('Smart Stock Alert Report')
            ->setSubject('Dies Required for Production')
            ->setDescription('Low stock items requiring die production');

        // Set sheet title
        $sheet->setTitle('Stock Alert Report');
        
        // Header styling
        $headerStyle = [
            'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2196F3']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ];
        
        $subHeaderStyle = [
            'font' => ['bold' => true, 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F5F5F5']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ];
        
        $dataStyle = [
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER]
        ];

        // Title and summary
        $sheet->setCellValue('A1', 'Smart Stock Alert Report - Dies Required');
        $sheet->mergeCells('A1:H1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 16],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        ]);
        
        $sheet->setCellValue('A2', 'Generated: ' . ($alertData['generated_at'] ?? now()->format('Y-m-d H:i:s')));
        $sheet->mergeCells('A2:H2');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        
        // Summary section
        $row = 4;
        $sheet->setCellValue('A' . $row, 'SUMMARY');
        $sheet->mergeCells('A' . $row . ':H' . $row);
        $sheet->getStyle('A' . $row)->applyFromArray($headerStyle);
        
        $row++;
        $sheet->setCellValue('A' . $row, 'Total Items Needing Attention:');
        $sheet->setCellValue('B' . $row, $alertData['total_items'] ?? 0);
        $sheet->setCellValue('D' . $row, 'Total Dies Needed:');
        $sheet->setCellValue('E' . $row, $alertData['total_dies_needed'] ?? 0);
        
        $row += 2;
        
        // Check if there are items to process
        if (empty($alertData['belt_types'])) {
            // No alerts, but show inventory summary if available
            if (isset($alertData['inventory_summary']) && !empty($alertData['inventory_summary'])) {
                $sheet->setCellValue('A' . $row, $alertData['message'] ?? 'No items currently require die production alerts.');
                $sheet->mergeCells('A' . $row . ':H' . $row);
                $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                
                // Add inventory summary even when no alerts
                $row = $this->addInventoryValueSummary($sheet, $row, $alertData['inventory_summary']);
            } else {
                $sheet->setCellValue('A' . $row, 'No items currently require die production alerts.');
                $sheet->mergeCells('A' . $row . ':H' . $row);
                $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }
            
            // Auto-size columns
            foreach (range('A', 'H') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }
            
            return $spreadsheet;
        }
        
        // Data headers
        $headers = [
            'Belt Type', 'Section', 'Product SKU', 'Current Stock', 
            'Reorder Level', 'Stock per Die', 'Dies Needed', 'Status'
        ];
        
        $sheet->setCellValue('A' . $row, 'DETAILED BREAKDOWN');
        $sheet->mergeCells('A' . $row . ':H' . $row);
        $sheet->getStyle('A' . $row)->applyFromArray($headerStyle);
        
        $row++;
        
        // Set headers
        foreach ($headers as $index => $header) {
            $col = chr(65 + $index); // A, B, C, D, etc.
            $sheet->setCellValue($col . $row, $header);
            $sheet->getStyle($col . $row)->applyFromArray($subHeaderStyle);
        }
        
        $row++;
        
        // Process each belt type
        foreach ($alertData['belt_types'] as $beltType => $beltData) {
            foreach ($beltData['sections'] as $section => $sectionData) {
                foreach ($sectionData['items'] as $item) {
                    $status = $item->current_stock == 0 ? 'OUT OF STOCK' : 'LOW STOCK';
                    
                    $sheet->setCellValue('A' . $row, $beltData['name']);
                    $sheet->setCellValue('B' . $row, $section);
                    $sheet->setCellValue('C' . $row, $item->product_sku ?? 'N/A');
                    $sheet->setCellValue('D' . $row, number_format($item->current_stock, 2));
                    $sheet->setCellValue('E' . $row, number_format($item->reorder_level, 2));
                    $sheet->setCellValue('F' . $row, number_format($item->stock_per_die, 2));
                    $sheet->setCellValue('G' . $row, $item->dies_needed);
                    $sheet->setCellValue('H' . $row, $status);
                    
                    // Apply data styling
                    $sheet->getStyle('A' . $row . ':H' . $row)->applyFromArray($dataStyle);
                    
                    // Color code status
                    if ($status === 'OUT OF STOCK') {
                        $sheet->getStyle('H' . $row)->applyFromArray([
                            'font' => ['color' => ['rgb' => 'FFFFFF']],
                            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D32F2F']]
                        ]);
                    } else {
                        $sheet->getStyle('H' . $row)->applyFromArray([
                            'font' => ['color' => ['rgb' => 'FFFFFF']],
                            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FF9800']]
                        ]);
                    }
                    
                    $row++;
                }
            }
        }
        
        // // Add production planning summary with individual die entries
        // $row += 2;
        // $sheet->setCellValue('A' . $row, 'PRODUCTION PLANNING SUMMARY');
        // $sheet->mergeCells('A' . $row . ':H' . $row);
        // $sheet->getStyle('A' . $row)->applyFromArray($headerStyle);
        
        // $row++;
        // $sheet->setCellValue('A' . $row, 'SIZE');
        // $sheet->setCellValue('B' . $row, 'MAKE');
        // $sheet->setCellValue('C' . $row, 'PARTY');
        // $sheet->getStyle('A' . $row . ':C' . $row)->applyFromArray($subHeaderStyle);
        
        // $row++;
        
        // // Process each belt type and create individual die entries
        // foreach ($alertData['belt_types'] as $beltType => $beltData) {
        //     foreach ($beltData['sections'] as $section => $sectionData) {
        //         foreach ($sectionData['items'] as $item) {
        //             // Create individual rows for each die needed
        //             for ($i = 0; $i < $item->dies_needed; $i++) {
        //                 // Create SIZE from section + size (extract size from product_sku)
        //                 $size = $section;
        //                 if ($item->product_sku) {
        //                     // Extract size from SKU (format: SECTION-SIZE)
        //                     $skuParts = explode('-', $item->product_sku);
        //                     if (count($skuParts) >= 2) {
        //                         $sizeValue = $skuParts[1];
                                
        //                         // Handle decimal sizes (remove unnecessary .00)
        //                         if (is_numeric($sizeValue)) {
        //                             $numericSize = floatval($sizeValue);
        //                             // If it's a whole number, don't show decimals
        //                             if ($numericSize == intval($numericSize)) {
        //                                 $sizeValue = intval($numericSize);
        //                             } else {
        //                                 // Keep necessary decimals, remove trailing zeros
        //                                 $sizeValue = rtrim(rtrim(number_format($numericSize, 2), '0'), '.');
        //                             }
        //                         }
                                
        //                         $size = $skuParts[0] . $sizeValue; // e.g., B80, PJ1245, PK688
        //                     }
        //                 }
                        
        //                 $sheet->setCellValue('A' . $row, $size);
        //                 $sheet->setCellValue('B' . $row, 'MICRO');
        //                 $sheet->setCellValue('C' . $row, 'STOCK');
        //                 $sheet->getStyle('A' . $row . ':C' . $row)->applyFromArray($dataStyle);
        //                 $row++;
        //             }
        //         }
        //     }
        // }
        
        // Add inventory value summary if data is provided
        if (isset($alertData['inventory_summary']) && !empty($alertData['inventory_summary'])) {
            $row = $this->addInventoryValueSummary($sheet, $row, $alertData['inventory_summary']);
        }
        
        // Add footer notes
        $row += 2;
        $sheet->setCellValue('A' . $row, 'NOTES:');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        
        $row++;
        $sheet->setCellValue('A' . $row, '• Dies Needed = CEIL((Reorder Level - Current Stock) / Stock per Die)');
        $row++;
        $sheet->setCellValue('A' . $row, '• This report only includes items below reorder levels that haven\'t been alerted yet');
        $row++;
        $sheet->setCellValue('A' . $row, '• Once alerted, items won\'t appear again until stock is replenished above reorder level');
        $row++;
        $sheet->setCellValue('A' . $row, '• Report generated: ' . now()->format('Y-m-d H:i:s'));
        
        // Auto-size columns
        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        return $spreadsheet;
    }
    
    /**
     * Save spreadsheet to temporary file and return path
     */
    public function saveToTempFile(Spreadsheet $spreadsheet, $filename = 'stock_alert_report.xlsx')
    {
        $writer = new Xlsx($spreadsheet);
        $tempPath = storage_path('app/temp/' . $filename);
        
        // Ensure temp directory exists
        if (!file_exists(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0755, true);
        }
        
        $writer->save($tempPath);
        
        return $tempPath;
    }
    
    /**
     * Generate Excel file for regular stock alert data
     */
    public function generateStockAlertExcel($lowStockData)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Set document properties
        $spreadsheet->getProperties()
            ->setCreator('Smart Belt Inventory System')
            ->setTitle('Daily Stock Alert Report')
            ->setSubject('Low Stock and Out of Stock Items')
            ->setDescription('Daily inventory alert report');

        // Set sheet title
        $sheet->setTitle('Stock Alert Report');
        
        // Header styling
        $headerStyle = [
            'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D32F2F']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ];
        
        $subHeaderStyle = [
            'font' => ['bold' => true, 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F5F5F5']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ];
        
        $dataStyle = [
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER]
        ];

        // Title and summary
        $sheet->setCellValue('A1', 'Daily Stock Alert Report');
        $sheet->mergeCells('A1:G1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 16],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        ]);
        
        $sheet->setCellValue('A2', 'Generated: ' . now()->format('Y-m-d H:i:s'));
        $sheet->mergeCells('A2:G2');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        
        // Summary section
        $row = 4;
        $sheet->setCellValue('A' . $row, 'SUMMARY');
        $sheet->mergeCells('A' . $row . ':G' . $row);
        $sheet->getStyle('A' . $row)->applyFromArray($headerStyle);
        
        $row++;
        $sheet->setCellValue('A' . $row, 'Total Low Stock Items:');
        $sheet->setCellValue('B' . $row, $lowStockData['total_low_stock_count'] ?? 0);
        $sheet->setCellValue('D' . $row, 'Total Out of Stock Items:');
        $sheet->setCellValue('E' . $row, $lowStockData['total_out_of_stock_count'] ?? 0);
        
        $row += 2;
        
        // Check if there are items to process
        $hasLowStock = !empty($lowStockData['low_stock_items']);
        $hasOutOfStock = !empty($lowStockData['out_of_stock_items']);
        
        if (!$hasLowStock && !$hasOutOfStock) {
            $sheet->setCellValue('A' . $row, 'No low stock or out of stock items found.');
            $sheet->mergeCells('A' . $row . ':G' . $row);
            $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            
            // Auto-size columns
            foreach (range('A', 'G') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }
            
            return $spreadsheet;
        }
        
        // Data headers
        $headers = [
            'Belt Type', 'Section', 'Size', 'Current Stock', 
            'Reorder Level', 'Rate', 'Status'
        ];
        
        // Process Low Stock Items
        if ($hasLowStock) {
            $sheet->setCellValue('A' . $row, 'LOW STOCK ITEMS');
            $sheet->mergeCells('A' . $row . ':G' . $row);
            $sheet->getStyle('A' . $row)->applyFromArray([
                'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FF9800']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
            ]);
            
            $row++;
            
            // Set headers
            foreach ($headers as $index => $header) {
                $col = chr(65 + $index); // A, B, C, D, etc.
                $sheet->setCellValue($col . $row, $header);
                $sheet->getStyle($col . $row)->applyFromArray($subHeaderStyle);
            }
            
            $row++;
            
            // Add low stock items
            foreach ($lowStockData['low_stock_items'] as $item) {
                $sheet->setCellValue('A' . $row, $item['belt_type'] ?? 'N/A');
                $sheet->setCellValue('B' . $row, $item['section'] ?? 'N/A');
                $sheet->setCellValue('C' . $row, $item['size'] ?? 'N/A');
                $sheet->setCellValue('D' . $row, number_format($item['balance_stock'] ?? 0, 2));
                $sheet->setCellValue('E' . $row, number_format($item['reorder_level'] ?? 0, 2));
                $sheet->setCellValue('F' . $row, '₹' . number_format($item['rate'] ?? 0, 2));
                $sheet->setCellValue('G' . $row, 'LOW STOCK');
                
                // Apply data styling
                $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray($dataStyle);
                
                // Color code status
                $sheet->getStyle('G' . $row)->applyFromArray([
                    'font' => ['color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FF9800']]
                ]);
                
                $row++;
            }
            
            $row += 2;
        }
        
        // Process Out of Stock Items
        if ($hasOutOfStock) {
            $sheet->setCellValue('A' . $row, 'OUT OF STOCK ITEMS');
            $sheet->mergeCells('A' . $row . ':G' . $row);
            $sheet->getStyle('A' . $row)->applyFromArray([
                'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D32F2F']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
            ]);
            
            $row++;
            
            // Set headers
            foreach ($headers as $index => $header) {
                $col = chr(65 + $index); // A, B, C, D, etc.
                $sheet->setCellValue($col . $row, $header);
                $sheet->getStyle($col . $row)->applyFromArray($subHeaderStyle);
            }
            
            $row++;
            
            // Add out of stock items
            foreach ($lowStockData['out_of_stock_items'] as $item) {
                $sheet->setCellValue('A' . $row, $item['belt_type'] ?? 'N/A');
                $sheet->setCellValue('B' . $row, $item['section'] ?? 'N/A');
                $sheet->setCellValue('C' . $row, $item['size'] ?? 'N/A');
                $sheet->setCellValue('D' . $row, '0');
                $sheet->setCellValue('E' . $row, number_format($item['reorder_level'] ?? 0, 2));
                $sheet->setCellValue('F' . $row, '₹' . number_format($item['rate'] ?? 0, 2));
                $sheet->setCellValue('G' . $row, 'OUT OF STOCK');
                
                // Apply data styling
                $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray($dataStyle);
                
                // Color code status
                $sheet->getStyle('G' . $row)->applyFromArray([
                    'font' => ['color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D32F2F']]
                ]);
                
                $row++;
            }
        }


        // Add inventory value summary if data is provided
if (isset($lowStockData['inventory_summary']) && !empty($lowStockData['inventory_summary'])) {
    $row = $this->addInventoryValueSummary($sheet, $row, $lowStockData['inventory_summary']);
}
        
        // Add footer notes
        $row += 2;
        $sheet->setCellValue('A' . $row, 'NOTES:');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        
        $row++;
        $sheet->setCellValue('A' . $row, '• Low Stock: Items below their reorder level but still have stock');
        $row++;
        $sheet->setCellValue('A' . $row, '• Out of Stock: Items with zero stock');
        $row++;
        $sheet->setCellValue('A' . $row, '• This report is generated daily to help maintain optimal inventory levels');
        $row++;
        $sheet->setCellValue('A' . $row, '• Report generated: ' . now()->format('Y-m-d H:i:s'));
        
        // Auto-size columns
        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        return $spreadsheet;
    }
    
    /**
     * Generate and save Excel file for regular stock alerts
     */
    public function generateStockAlertFile($lowStockData, $filename = null)
    {
        $filename = $filename ?? 'daily_stock_alert_' . date('Y-m-d_H-i-s') . '.xlsx';
        
        $spreadsheet = $this->generateStockAlertExcel($lowStockData);
        $filePath = $this->saveToTempFile($spreadsheet, $filename);
        
        return [
            'path' => $filePath,
            'filename' => $filename,
            'size' => filesize($filePath)
        ];
    }
    
    /**
     * Generate and save Excel file for smart stock alerts
     */
    public function generateSmartStockAlertFile($alertData, $filename = null)
    {
        $filename = $filename ?? 'smart_stock_alert_' . date('Y-m-d_H-i-s') . '.xlsx';
        
        $spreadsheet = $this->generateSmartStockAlertExcel($alertData);
        $filePath = $this->saveToTempFile($spreadsheet, $filename);
        
        return [
            'path' => $filePath,
            'filename' => $filename,
            'size' => filesize($filePath)
        ];
    }

  /**
 * Add inventory value summary to existing Excel report
 */
public function addInventoryValueSummary($sheet, $startRow, $inventoryData)
{
    if (empty($inventoryData)) {
        return $startRow; // Return same row if no data
    }

    // Styling (reuse existing styles)
    $headerStyle = [
        'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2196F3']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
    ];
    
    $subHeaderStyle = [
        'font' => ['bold' => true, 'size' => 11],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F5F5F5']],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
    ];
    
    $dataStyle = [
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        'alignment' => ['vertical' => Alignment::VERTICAL_CENTER]
    ];

    $row = $startRow + 2;
    
    // Inventory Value Summary Header
    $sheet->setCellValue('A' . $row, 'INVENTORY VALUE SUMMARY');
    $sheet->mergeCells('A' . $row . ':H' . $row);
    $sheet->getStyle('A' . $row)->applyFromArray($headerStyle);
    
    $row++;
    
    // Overall Totals Section
    $sheet->setCellValue('A' . $row, 'OVERALL TOTALS');
    $sheet->mergeCells('A' . $row . ':H' . $row);
    $sheet->getStyle('A' . $row)->applyFromArray($subHeaderStyle);
    
    $row++;
    
    $totals = $inventoryData['totals'] ?? [];
    
    $sheet->setCellValue('A' . $row, 'Total Inventory Value:');
    $sheet->setCellValue('B' . $row, '₹' . number_format($totals['total_value'] ?? 0, 2));
    $sheet->setCellValue('D' . $row, 'Total Products:');
    $sheet->setCellValue('E' . $row, number_format($totals['total_products'] ?? 0));
    $sheet->getStyle('A' . $row . ':E' . $row)->applyFromArray($dataStyle);
    
    $row++;
    
    $sheet->setCellValue('A' . $row, 'In Stock Items:');
    $sheet->setCellValue('B' . $row, number_format($totals['in_stock'] ?? 0));
    $sheet->setCellValue('D' . $row, 'Low Stock Items:');
    $sheet->setCellValue('E' . $row, number_format($totals['low_stock'] ?? 0));
    $sheet->getStyle('A' . $row . ':E' . $row)->applyFromArray($dataStyle);
    
    $row++;
    
    $sheet->setCellValue('A' . $row, 'Out of Stock Items:');
    $sheet->setCellValue('B' . $row, number_format($totals['out_of_stock'] ?? 0));
    $sheet->getStyle('A' . $row . ':B' . $row)->applyFromArray($dataStyle);
    
    $row += 2;
    
    // Belt Type Breakdown Section (Same as Dashboard)
    $sheet->setCellValue('A' . $row, 'BELT TYPE BREAKDOWN');
    $sheet->mergeCells('A' . $row . ':H' . $row);
    $sheet->getStyle('A' . $row)->applyFromArray($subHeaderStyle);
    
    $row++;
    
    // Headers for belt type table
    $sheet->setCellValue('A' . $row, 'Belt Type');
    $sheet->setCellValue('B' . $row, 'Total Value');
    $sheet->setCellValue('C' . $row, 'Products');
    $sheet->setCellValue('D' . $row, 'In Stock');
    $sheet->setCellValue('E' . $row, 'Low Stock');
    $sheet->setCellValue('F' . $row, 'Out of Stock');
    $sheet->getStyle('A' . $row . ':F' . $row)->applyFromArray($subHeaderStyle);
    
    $row++;
    
    // Belt type data (same as dashboard)
    $beltTypes = $inventoryData['belt_types'] ?? [];
    $detailedStats = $inventoryData['detailed_stats'] ?? [];
    
    $beltTypeNames = [
        'vee' => 'Vee Belts',
        'cogged' => 'Cogged Belts', 
        'poly' => 'Poly Belts',
        'tpu' => 'TPU Belts',
        'timing' => 'Timing Belts',
        'special' => 'Special Belts'
    ];
    
    foreach ($beltTypeNames as $key => $name) {
        $value = $beltTypes[$key] ?? 0;
        $stats = $detailedStats[$key . '_belts'] ?? [];
        
        $sheet->setCellValue('A' . $row, $name);
        $sheet->setCellValue('B' . $row, '₹' . number_format($value, 2));
        $sheet->setCellValue('C' . $row, number_format($stats['total_products'] ?? 0));
        $sheet->setCellValue('D' . $row, number_format($stats['in_stock'] ?? 0));
        $sheet->setCellValue('E' . $row, number_format($stats['low_stock'] ?? 0));
        $sheet->setCellValue('F' . $row, number_format($stats['out_of_stock'] ?? 0));
        $sheet->getStyle('A' . $row . ':F' . $row)->applyFromArray($dataStyle);
        
        // Add color coding for values
        if ($value > 0) {
            $sheet->getStyle('B' . $row)->applyFromArray([
                'font' => ['color' => ['rgb' => '2E7D32']] // Green for positive values
            ]);
        }
        
        $row++;
    }
    
    return $row; // Return the last row used
}




/**
 * Generate production planning Excel (SIZE, MAKE, PARTY only)
 */
public function generateProductionPlanningOnlyExcel($planningData)
{
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Set document properties
    $spreadsheet->getProperties()
        ->setCreator('Microbelts Inventory System')
        ->setTitle('Production Planning Summary')
        ->setSubject('Dies Required for Production')
        ->setDescription('Daily production planning requirements');

    // Set sheet title
    $sheet->setTitle('Production Planning');
    
    // Header styling
    $headerStyle = [
        'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2196F3']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
    ];
    
    $subHeaderStyle = [
        'font' => ['bold' => true, 'size' => 11],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F5F5F5']],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
    ];
    
    $dataStyle = [
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        'alignment' => ['vertical' => Alignment::VERTICAL_CENTER]
    ];

    // Title
    $sheet->setCellValue('A1', 'Production Planning Summary');
    $sheet->mergeCells('A1:C1');
    $sheet->getStyle('A1')->applyFromArray([
        'font' => ['bold' => true, 'size' => 16],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
    ]);
    
    $sheet->setCellValue('A2', 'Generated: ' . \Carbon\Carbon::parse($planningData['generated_at'] ?? now())->setTimezone('Asia/Kolkata')->format('d M Y, h:i A') . ' IST');
    $sheet->mergeCells('A2:C2');
    $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    
    $row = 4;
    
    // Headers
    $sheet->setCellValue('A' . $row, 'SIZE');
    $sheet->setCellValue('B' . $row, 'MAKE');
    $sheet->setCellValue('C' . $row, 'PARTY');
    $sheet->getStyle('A' . $row . ':C' . $row)->applyFromArray($subHeaderStyle);
    
    $row++;
    
    // Production planning data (same logic as existing)
    if (isset($planningData['belt_types'])) {
        foreach ($planningData['belt_types'] as $beltType => $beltData) {
            foreach ($beltData['sections'] as $section => $sectionData) {
                foreach ($sectionData['items'] as $item) {
                    for ($i = 0; $i < $item->dies_needed; $i++) {
                        $size = $section;
                        if ($item->product_sku) {
                            $skuParts = explode('-', $item->product_sku);
                            if (count($skuParts) >= 2) {
                                $sizeValue = $skuParts[1];
                                
                                // Handle decimal sizes
                                if (is_numeric($sizeValue)) {
                                    $numericSize = floatval($sizeValue);
                                    if ($numericSize == intval($numericSize)) {
                                        $sizeValue = intval($numericSize);
                                    } else {
                                        $sizeValue = rtrim(rtrim(number_format($numericSize, 2), '0'), '.');
                                    }
                                }
                                
                                $size = $skuParts[0] . $sizeValue;
                            }
                        }
                        
                        $sheet->setCellValue('A' . $row, $size);
                        $sheet->setCellValue('B' . $row, 'MICRO');
                        $sheet->setCellValue('C' . $row, 'STOCK');
                        $sheet->getStyle('A' . $row . ':C' . $row)->applyFromArray($dataStyle);
                        $row++;
                    }
                }
            }
        }
    }
    
    // Auto-size columns
    foreach (range('A', 'C') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
    
    return $spreadsheet;
}
}