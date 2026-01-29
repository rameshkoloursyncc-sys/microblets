<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ProductionPlanningExcelService
{
    /**
     * Generate Production Planning Excel file
     */
    public function generateProductionPlanningExcel($alertData)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Set document properties
        $spreadsheet->getProperties()
            ->setCreator('Microbelts Inventory System')
            ->setTitle('Production Planning Summary')
            ->setSubject('Dies Required for Production')
            ->setDescription('Production planning with die requirements');

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

        // // Title and summary
        // $sheet->setCellValue('A1', 'PRODUCTION PLANNING SUMMARY');
        // $sheet->mergeCells('A1:F1');
        // $sheet->getStyle('A1')->applyFromArray([
        //     'font' => ['bold' => true, 'size' => 16],
        //     'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        // ]);
        
        // $sheet->setCellValue('A2', 'Generated: ' . \Carbon\Carbon::parse($alertData['generated_at'] ?? now())->setTimezone('Asia/Kolkata')->format('d M Y, h:i A') . ' IST');
        // $sheet->mergeCells('A2:F2');
        // $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        
        // // Summary section
        // $row = 4;
        // $sheet->setCellValue('A' . $row, 'SUMMARY');
        // $sheet->mergeCells('A' . $row . ':F' . $row);
        // $sheet->getStyle('A' . $row)->applyFromArray($headerStyle);
        
        // $row++;
        // $sheet->setCellValue('A' . $row, 'Total Dies Needed:');
        // $sheet->setCellValue('B' . $row, $alertData['total_dies_needed'] ?? 0);
        // $sheet->setCellValue('C' . $row, 'Items Requiring Dies: ' . ($alertData['total_items'] ?? 0));
        // $sheet->getStyle('A' . $row . ':C' . $row)->applyFromArray($dataStyle);
        
        // $row += 2;
        
        // // Check if there are items to process
        // if (empty($alertData['belt_types'])) {
        //     $sheet->setCellValue('A' . $row, 'No production planning required - all items adequately stocked.');
        //     $sheet->mergeCells('A' . $row . ':F' . $row);
        //     $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            
        //     // Auto-size columns
        //     foreach (range('A', 'C') as $col) {
        //         $sheet->getColumnDimension($col)->setAutoSize(true);
        //     }
            
        //     return $spreadsheet;
        // }
        
        // // Production planning headers
        // $sheet->setCellValue('A' . $row, 'PRODUCTION PLANNING DETAILS');
        // $sheet->mergeCells('A' . $row . ':F' . $row);
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
        
        // Add summary by belt type
        $row += 5;
        $sheet->setCellValue('A' . $row, 'BELT TYPE SUMMARY');
        $sheet->mergeCells('A' . $row . ':F' . $row);
        $sheet->getStyle('A' . $row)->applyFromArray($subHeaderStyle);
        
        $row++;
        $sheet->setCellValue('A' . $row, 'Belt Type');
        $sheet->setCellValue('B' . $row, 'Dies Needed');
        $sheet->setCellValue('C' . $row, 'Items Count');
        $sheet->getStyle('A' . $row . ':C' . $row)->applyFromArray($subHeaderStyle);
        
        $row++;
        
        foreach ($alertData['belt_types'] as $beltType => $beltData) {
            $sheet->setCellValue('A' . $row, $beltData['name']);
            $sheet->setCellValue('B' . $row, $beltData['total_dies']);
            $sheet->setCellValue('C' . $row, $beltData['total_items']);
            $sheet->getStyle('A' . $row . ':C' . $row)->applyFromArray($dataStyle);
            $row++;
        }
        
        // Add footer notes
        $row += 2;
        $sheet->setCellValue('A' . $row, 'NOTES:');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        
        $row++;
        $sheet->setCellValue('A' . $row, '• Each row represents one die to be manufactured');
        $row++;
        $sheet->setCellValue('A' . $row, '• MAKE is always "MICRO" for all products');
        $row++;
        $sheet->setCellValue('A' . $row, '• PARTY is always "STOCK" for inventory items');
        $row++;
        $sheet->setCellValue('A' . $row, '• Report generated: ' . \Carbon\Carbon::now()->setTimezone('Asia/Kolkata')->format('d M Y, h:i A') . ' IST');
        
        // Auto-size columns
        foreach (range('A', 'C') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        return $spreadsheet;
    }
    
    /**
     * Save spreadsheet to temporary file and return path
     */
    public function saveToTempFile(Spreadsheet $spreadsheet, $filename = 'production_planning.xlsx')
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
     * Generate and save Production Planning Excel file
     */
    public function generateProductionPlanningFile($alertData, $filename = null)
    {
        $filename = $filename ?? 'production_planning_' . date('Y-m-d_H-i-s') . '.xlsx';
        
        $spreadsheet = $this->generateProductionPlanningExcel($alertData);
        $filePath = $this->saveToTempFile($spreadsheet, $filename);
        
        return [
            'path' => $filePath,
            'filename' => $filename,
            'size' => filesize($filePath)
        ];
    }
}