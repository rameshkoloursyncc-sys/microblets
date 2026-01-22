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
            $sheet->setCellValue('A' . $row, 'No items currently require die production alerts.');
            $sheet->mergeCells('A' . $row . ':H' . $row);
            $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            
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
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . $row, $header);
            $sheet->getStyle($col . $row)->applyFromArray($subHeaderStyle);
            $col++;
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
        
        // Add summary by belt type
        $row += 2;
        $sheet->setCellValue('A' . $row, 'PRODUCTION PLANNING SUMMARY');
        $sheet->mergeCells('A' . $row . ':H' . $row);
        $sheet->getStyle('A' . $row)->applyFromArray($headerStyle);
        
        $row++;
        $sheet->setCellValue('A' . $row, 'Belt Type');
        $sheet->setCellValue('B' . $row, 'Total Dies Needed');
        $sheet->setCellValue('C' . $row, 'Sections Affected');
        $sheet->setCellValue('D' . $row, 'Items Count');
        $sheet->getStyle('A' . $row . ':D' . $row)->applyFromArray($subHeaderStyle);
        
        $row++;
        
        foreach ($alertData['belt_types'] as $beltType => $beltData) {
            $sheet->setCellValue('A' . $row, $beltData['name']);
            $sheet->setCellValue('B' . $row, $beltData['total_dies']);
            $sheet->setCellValue('C' . $row, count($beltData['sections']));
            $sheet->setCellValue('D' . $row, $beltData['total_items']);
            $sheet->getStyle('A' . $row . ':D' . $row)->applyFromArray($dataStyle);
            $row++;
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
            $col = 'A';
            foreach ($headers as $header) {
                $sheet->setCellValue($col . $row, $header);
                $sheet->getStyle($col . $row)->applyFromArray($subHeaderStyle);
                $col++;
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
            $col = 'A';
            foreach ($headers as $header) {
                $sheet->setCellValue($col . $row, $header);
                $sheet->getStyle($col . $row)->applyFromArray($subHeaderStyle);
                $col++;
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
}