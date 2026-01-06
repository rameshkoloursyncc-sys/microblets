<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class TimingBeltExcelController extends Controller
{
    public function uploadExcel(Request $request)
    {
        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls',
            'belt_type' => 'required|in:commercial,neoprene'
        ]);

        try {
            $file = $request->file('excel_file');
            $beltType = $request->input('belt_type');
            
            $spreadsheet = IOFactory::load($file->getPathname());
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();
            
            if ($beltType === 'commercial') {
                return $this->processCommercialTimingBelts($rows);
            } else {
                return $this->processNeopreneTimingBelts($rows);
            }
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error processing Excel file: ' . $e->getMessage()
            ], 500);
        }
    }

    private function processCommercialTimingBelts($rows)
    {
        $processedData = [];
        $headers = $rows[0] ?? [];
        
        // Find section from headers (XL, L, 5M, etc.)
        $section = null;
        foreach ($headers as $header) {
            if (in_array(strtoupper(trim($header)), ['XL', 'L', '5M', 'T5', 'T10', 'AT5', 'AT10'])) {
                $section = strtoupper(trim($header));
                break;
            }
        }
        
        if (!$section) {
            return response()->json([
                'success' => false,
                'message' => 'Could not identify section from Excel headers. Expected: XL, L, 5M, T5, T10, AT5, AT10',
                'headers' => $headers
            ], 400);
        }

        // Process data rows (skip header)
        $skippedRows = 0;
        $processedRows = 0;
        
        for ($i = 1; $i < count($rows); $i++) {
            $row = $rows[$i];
            
            // Skip empty rows
            if (empty(array_filter($row, function($cell) { return !is_null($cell) && $cell !== ''; }))) {
                $skippedRows++;
                continue;
            }
            
            // Your Excel format: XL | TYPE- 1 (FULL SLEVE) | MM | TOTAL(MM) | RATE PER SLV | TOTAL MM RATE | FINAL VALUE
            $size = isset($row[0]) ? trim($row[0]) : null; // XL column (100, 110, 120, etc.)
            $typeValue = isset($row[1]) ? trim($row[1]) : null; // TYPE column (18, 21, 10, 24, etc.) - numbers, not text
            $mmValues = isset($row[2]) ? trim($row[2]) : ''; // MM column (comma-separated)
            $totalMm = isset($row[3]) ? (float)$row[3] : 0; // TOTAL(MM) column
            $ratePerSlv = isset($row[4]) ? (float)$row[4] : 0; // RATE PER SLV column
            $totalMmRate = isset($row[5]) ? (float)$row[5] : 0; // TOTAL MM RATE column  
            $finalValue = isset($row[6]) ? (float)$row[6] : 0; // FINAL VALUE column
            
            // Skip rows only if size is missing - import if size exists regardless of other values
            if (!$size || $size === '') {
                $skippedRows++;
                continue;
            }
            
            // Ensure type has a default value if null or empty
            $typeValue = $typeValue ?: '0'; // Default to '0' if type is null or empty
            
            $processedData[] = [
                'section' => $section,
                'size' => $size,
                'type' => $typeValue, // Store the actual numerical value from Excel (18, 21, 10, etc.)
                'total_mm' => $totalMm,
                'in_mm' => 0, // Default
                'out_mm' => 0, // Default
                'reorder_level' => null, // Default
                'rate' => $ratePerSlv, // Use RATE PER SLV as rate
                'value' => $finalValue,
                'remark' => $mmValues, // Store MM values as remark
                'created_by' => null,
                'updated_by' => null,
                'created_at' => now()->format('Y-m-d H:i:s'),
                'updated_at' => now()->format('Y-m-d H:i:s')
            ];
            
            $processedRows++;
        }

        return response()->json([
            'success' => true,
            'message' => 'Commercial timing belts processed successfully',
            'data' => $processedData,
            'count' => count($processedData),
            'section' => $section,
            'debug' => [
                'total_rows' => count($rows),
                'processed_rows' => $processedRows,
                'skipped_rows' => $skippedRows,
                'headers' => $headers
            ]
        ]);
    }

    private function processNeopreneTimingBelts($rows)
    {
        $processedData = [];
        $headers = $rows[0] ?? [];
        
        // For neoprene, section might be in filename or specified separately
        $section = 'NEOPRENE'; // Default section for neoprene belts
        
        // Process data rows (skip header)
        for ($i = 1; $i < count($rows); $i++) {
            $row = $rows[$i];
            
            // Skip empty rows
            if (empty(array_filter($row))) continue;
            
            $fullSleeve = $row[0] ?? null; // FULL SLEEVE column
            $mm = $row[1] ?? 0; // MM column
            $ratePerSleeve = $row[2] ?? 0; // RATE PER SLEEVE column
            $totalRate = $row[3] ?? 0; // TOTAL RATE column
            
            if (!$fullSleeve) continue;
            
            $processedData[] = [
                'section' => $section,
                'size' => $fullSleeve,
                'type' => 'FULL SLEEVE', // Use "FULL SLEEVE" for neoprene instead of "1 (FULL SLEEVE)"
                'total_mm' => (float)$mm, // Use MM as total_mm for neoprene
                'in_mm' => 0, // Default
                'out_mm' => 0, // Default
                'reorder_level' => null, // Default
                'rate' => (float)$ratePerSleeve,
                'value' => (float)$totalRate,
                'remark' => "Neoprene timing belt",
                'created_by' => null,
                'updated_by' => null,
                'created_at' => now()->format('Y-m-d H:i:s'),
                'updated_at' => now()->format('Y-m-d H:i:s')
            ];
        }

        return response()->json([
            'success' => true,
            'message' => 'Neoprene timing belts processed successfully',
            'data' => $processedData,
            'count' => count($processedData),
            'section' => $section
        ]);
    }

    public function downloadJson(Request $request)
    {
        $request->validate([
            'section' => 'required|string'
        ]);

        try {
            $section = $request->input('section');
            
            $timingBelts = DB::table('timing_belts')
                ->where('section', $section)
                ->select([
                    'section',
                    'size', 
                    'type',
                    'total_mm',
                    'rate',
                    'value',
                    'remark'
                ])
                ->orderBy('size')
                ->get()
                ->toArray();

            $filename = "timing_belts_{$section}_" . date('Y-m-d_H-i-s') . '.json';
            
            return response()->json([
                'success' => true,
                'data' => $timingBelts,
                'filename' => $filename,
                'count' => count($timingBelts)
            ])
            ->header('Content-Disposition', "attachment; filename={$filename}")
            ->header('Content-Type', 'application/json');
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error generating JSON: ' . $e->getMessage()
            ], 500);
        }
    }

    public function importToDatabase(Request $request)
    {
        $request->validate([
            'data' => 'required|array',
            'section' => 'required|string'
        ]);

        try {
            $data = $request->input('data');
            $section = $request->input('section');
            
            // Clear existing data for this section
            DB::table('timing_belts')->where('section', $section)->delete();
            
            // Process data in chunks to handle large datasets
            $chunks = array_chunk($data, 50); // Process 50 records at a time
            $totalInserted = 0;
            
            foreach ($chunks as $chunk) {
                // Ensure all datetime fields are properly formatted and handle duplicates
                $formattedChunk = [];
                
                foreach ($chunk as $item) {
                    $formattedItem = $item;
                    $formattedItem['created_at'] = now()->format('Y-m-d H:i:s');
                    $formattedItem['updated_at'] = now()->format('Y-m-d H:i:s');
                    
                    // Check if record already exists to avoid duplicates
                    $exists = DB::table('timing_belts')
                        ->where('section', $formattedItem['section'])
                        ->where('size', $formattedItem['size'])
                        ->where('type', $formattedItem['type'])
                        ->exists();
                    
                    if (!$exists) {
                        $formattedChunk[] = $formattedItem;
                    }
                }
                
                if (!empty($formattedChunk)) {
                    // Use insertOrIgnore to handle any remaining duplicates gracefully
                    DB::table('timing_belts')->insertOrIgnore($formattedChunk);
                    $totalInserted += count($formattedChunk);
                }
            }
            
            return response()->json([
                'success' => true,
                'message' => "Successfully imported {$totalInserted} timing belts for section {$section}",
                'count' => $totalInserted,
                'chunks_processed' => count($chunks)
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error importing to database: ' . $e->getMessage(),
                'error_details' => [
                    'line' => $e->getLine(),
                    'file' => $e->getFile()
                ]
            ], 500);
        }
    }
}
