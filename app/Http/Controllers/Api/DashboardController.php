<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function getInventoryStats()
    {
        try {
            // Calculate totals using SQL aggregation for accuracy
            $stats = [
                'vee_belts' => $this->getVeeBeltStats(),
                'cogged_belts' => $this->getCoggedBeltStats(),
                'poly_belts' => $this->getPolyBeltStats(),
                'tpu_belts' => $this->getTpuBeltStats(),
                'timing_belts' => $this->getTimingBeltStats(),
                'special_belts' => $this->getSpecialBeltStats(),
            ];

            // Calculate combined totals
            $totalValue = array_sum(array_column($stats, 'total_value'));
            $totalProducts = array_sum(array_column($stats, 'total_products'));
            $totalInStock = array_sum(array_column($stats, 'in_stock'));
            $totalLowStock = array_sum(array_column($stats, 'low_stock'));
            $totalOutOfStock = array_sum(array_column($stats, 'out_of_stock'));

            return response()->json([
                'success' => true,
                'data' => [
                    'totals' => [
                        'total_value' => round($totalValue, 2),
                        'total_products' => $totalProducts,
                        'in_stock' => $totalInStock,
                        'low_stock' => $totalLowStock,
                        'out_of_stock' => $totalOutOfStock,
                    ],
                    'belt_types' => [
                        'vee' => round($stats['vee_belts']['total_value'], 2),
                        'cogged' => round($stats['cogged_belts']['total_value'], 2),
                        'poly' => round($stats['poly_belts']['total_value'], 2),
                        'tpu' => round($stats['tpu_belts']['total_value'], 2),
                        'timing' => round($stats['timing_belts']['total_value'], 2),
                        'special' => round($stats['special_belts']['total_value'], 2),
                    ],
                    'detailed_stats' => $stats
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error calculating inventory stats: ' . $e->getMessage()
            ], 500);
        }
    }

    private function getVeeBeltStats()
    {
        try {
            // Check if value column exists
            $columns = DB::getSchemaBuilder()->getColumnListing('vee_belts');
            $hasValueColumn = in_array('value', $columns);
            
            $result = DB::table('vee_belts')
                ->select([
                    DB::raw('COUNT(*) as total_products'),
                    DB::raw('SUM(CASE WHEN balance_stock > 0 THEN 1 ELSE 0 END) as in_stock'),
                    DB::raw('SUM(CASE WHEN balance_stock > 0 AND balance_stock <= COALESCE(reorder_level, 5) THEN 1 ELSE 0 END) as low_stock'),
                    DB::raw('SUM(CASE WHEN balance_stock = 0 THEN 1 ELSE 0 END) as out_of_stock'),
                    DB::raw($hasValueColumn ? 'SUM(COALESCE(value, 0)) as total_value' : '0 as total_value')
                ])
                ->first();

            return [
                'total_products' => $result->total_products ?? 0,
                'in_stock' => $result->in_stock ?? 0,
                'low_stock' => $result->low_stock ?? 0,
                'out_of_stock' => $result->out_of_stock ?? 0,
                'total_value' => $result->total_value ?? 0,
            ];
        } catch (\Exception $e) {
            return [
                'total_products' => 0,
                'in_stock' => 0,
                'low_stock' => 0,
                'out_of_stock' => 0,
                'total_value' => 0,
            ];
        }
    }

    private function getCoggedBeltStats()
    {
        try {
            // Check if value column exists
            $columns = DB::getSchemaBuilder()->getColumnListing('cogged_belts');
            $hasValueColumn = in_array('value', $columns);
            
            $result = DB::table('cogged_belts')
                ->select([
                    DB::raw('COUNT(*) as total_products'),
                    DB::raw('SUM(CASE WHEN balance_stock > 0 THEN 1 ELSE 0 END) as in_stock'),
                    DB::raw('SUM(CASE WHEN balance_stock > 0 AND balance_stock <= COALESCE(reorder_level, 5) THEN 1 ELSE 0 END) as low_stock'),
                    DB::raw('SUM(CASE WHEN balance_stock = 0 THEN 1 ELSE 0 END) as out_of_stock'),
                    DB::raw($hasValueColumn ? 'SUM(COALESCE(value, 0)) as total_value' : '0 as total_value')
                ])
                ->first();

            return [
                'total_products' => $result->total_products ?? 0,
                'in_stock' => $result->in_stock ?? 0,
                'low_stock' => $result->low_stock ?? 0,
                'out_of_stock' => $result->out_of_stock ?? 0,
                'total_value' => $result->total_value ?? 0,
            ];
        } catch (\Exception $e) {
            return [
                'total_products' => 0,
                'in_stock' => 0,
                'low_stock' => 0,
                'out_of_stock' => 0,
                'total_value' => 0,
            ];
        }
    }

    private function getPolyBeltStats()
    {
        try {
            // Check if columns exist
            $columns = DB::getSchemaBuilder()->getColumnListing('poly_belts');
            $hasValueColumn = in_array('value', $columns);
            $hasRibsColumn = in_array('ribs', $columns);
            $stockColumn = $hasRibsColumn ? 'ribs' : 'balance_stock';
            
            $result = DB::table('poly_belts')
                ->select([
                    DB::raw('COUNT(*) as total_products'),
                    DB::raw("SUM(CASE WHEN {$stockColumn} > 0 THEN 1 ELSE 0 END) as in_stock"),
                    DB::raw("SUM(CASE WHEN {$stockColumn} > 0 AND {$stockColumn} <= COALESCE(reorder_level, 5) THEN 1 ELSE 0 END) as low_stock"),
                    DB::raw("SUM(CASE WHEN {$stockColumn} = 0 THEN 1 ELSE 0 END) as out_of_stock"),
                    DB::raw($hasValueColumn ? 'SUM(COALESCE(value, 0)) as total_value' : '0 as total_value')
                ])
                ->first();

            return [
                'total_products' => $result->total_products ?? 0,
                'in_stock' => $result->in_stock ?? 0,
                'low_stock' => $result->low_stock ?? 0,
                'out_of_stock' => $result->out_of_stock ?? 0,
                'total_value' => $result->total_value ?? 0,
            ];
        } catch (\Exception $e) {
            // If any error, return basic counts
            return [
                'total_products' => DB::table('poly_belts')->count(),
                'in_stock' => 0,
                'low_stock' => 0,
                'out_of_stock' => 0,
                'total_value' => 0,
            ];
        }
    }

    private function getTpuBeltStats()
    {
        try {
            // Check if columns exist
            $columns = DB::getSchemaBuilder()->getColumnListing('tpu_belts');
            $hasValueColumn = in_array('value', $columns);
            $hasMeterColumn = in_array('meter', $columns);
            $stockColumn = $hasMeterColumn ? 'meter' : 'balance_stock';
            
            $result = DB::table('tpu_belts')
                ->select([
                    DB::raw('COUNT(*) as total_products'),
                    DB::raw("SUM(CASE WHEN {$stockColumn} > 0 THEN 1 ELSE 0 END) as in_stock"),
                    DB::raw("SUM(CASE WHEN {$stockColumn} > 0 AND {$stockColumn} <= COALESCE(reorder_level, 5) THEN 1 ELSE 0 END) as low_stock"),
                    DB::raw("SUM(CASE WHEN {$stockColumn} = 0 THEN 1 ELSE 0 END) as out_of_stock"),
                    DB::raw($hasValueColumn ? 'SUM(COALESCE(value, 0)) as total_value' : '0 as total_value')
                ])
                ->first();

            return [
                'total_products' => $result->total_products ?? 0,
                'in_stock' => $result->in_stock ?? 0,
                'low_stock' => $result->low_stock ?? 0,
                'out_of_stock' => $result->out_of_stock ?? 0,
                'total_value' => $result->total_value ?? 0,
            ];
        } catch (\Exception $e) {
            // If any error, return basic counts
            return [
                'total_products' => DB::table('tpu_belts')->count(),
                'in_stock' => 0,
                'low_stock' => 0,
                'out_of_stock' => 0,
                'total_value' => 0,
            ];
        }
    }

    private function getTimingBeltStats()
    {
        try {
            // Check if columns exist
            $columns = DB::getSchemaBuilder()->getColumnListing('timing_belts');
            $hasValueColumn = in_array('value', $columns);
            $hasTotalMmColumn = in_array('total_mm', $columns);
            $stockColumn = $hasTotalMmColumn ? 'total_mm' : 'balance_stock';
            
            $result = DB::table('timing_belts')
                ->select([
                    DB::raw('COUNT(*) as total_products'),
                    DB::raw("SUM(CASE WHEN {$stockColumn} > 0 THEN 1 ELSE 0 END) as in_stock"),
                    DB::raw("SUM(CASE WHEN {$stockColumn} > 0 AND {$stockColumn} <= COALESCE(reorder_level, 5) THEN 1 ELSE 0 END) as low_stock"),
                    DB::raw("SUM(CASE WHEN {$stockColumn} = 0 THEN 1 ELSE 0 END) as out_of_stock"),
                    DB::raw($hasValueColumn ? 'SUM(COALESCE(value, 0)) as total_value' : '0 as total_value')
                ])
                ->first();

            return [
                'total_products' => $result->total_products ?? 0,
                'in_stock' => $result->in_stock ?? 0,
                'low_stock' => $result->low_stock ?? 0,
                'out_of_stock' => $result->out_of_stock ?? 0,
                'total_value' => $result->total_value ?? 0,
            ];
        } catch (\Exception $e) {
            return [
                'total_products' => 0,
                'in_stock' => 0,
                'low_stock' => 0,
                'out_of_stock' => 0,
                'total_value' => 0,
            ];
        }
    }

    private function getSpecialBeltStats()
    {
        try {
            // Check if value column exists
            $columns = DB::getSchemaBuilder()->getColumnListing('special_belts');
            $hasValueColumn = in_array('value', $columns);
            
            $result = DB::table('special_belts')
                ->select([
                    DB::raw('COUNT(*) as total_products'),
                    DB::raw('SUM(CASE WHEN balance_stock > 0 THEN 1 ELSE 0 END) as in_stock'),
                    DB::raw('SUM(CASE WHEN balance_stock > 0 AND balance_stock <= COALESCE(reorder_level, 5) THEN 1 ELSE 0 END) as low_stock'),
                    DB::raw('SUM(CASE WHEN balance_stock = 0 THEN 1 ELSE 0 END) as out_of_stock'),
                    DB::raw($hasValueColumn ? 'SUM(COALESCE(value, 0)) as total_value' : '0 as total_value')
                ])
                ->first();

            return [
                'total_products' => $result->total_products ?? 0,
                'in_stock' => $result->in_stock ?? 0,
                'low_stock' => $result->low_stock ?? 0,
                'out_of_stock' => $result->out_of_stock ?? 0,
                'total_value' => $result->total_value ?? 0,
            ];
        } catch (\Exception $e) {
            return [
                'total_products' => 0,
                'in_stock' => 0,
                'low_stock' => 0,
                'out_of_stock' => 0,
                'total_value' => 0,
            ];
        }
    }

    public function getVeeBeltTotalDebug()
    {
        try {
            // Debug query to see actual vee belt values
            $veeBelts = DB::table('vee_belts')
                ->select(['id', 'section', 'size', 'value', 'balance_stock'])
                ->whereNotNull('value')
                ->where('value', '>', 0)
                ->orderBy('value', 'desc')
                ->limit(20)
                ->get();

            $totalValue = DB::table('vee_belts')
                ->sum('value');

            $count = DB::table('vee_belts')->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'belt_type' => 'Vee Belts',
                    'total_value' => $totalValue,
                    'total_count' => $count,
                    'sample_records' => $veeBelts,
                    'sql_query' => 'SELECT SUM(value) FROM vee_belts'
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getCoggedBeltTotalDebug()
    {
        try {
            $coggedBelts = DB::table('cogged_belts')
                ->select(['id', 'section', 'size', 'value', 'balance_stock'])
                ->whereNotNull('value')
                ->where('value', '>', 0)
                ->orderBy('value', 'desc')
                ->limit(20)
                ->get();

            $totalValue = DB::table('cogged_belts')->sum('value');
            $count = DB::table('cogged_belts')->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'belt_type' => 'Cogged Belts',
                    'total_value' => $totalValue,
                    'total_count' => $count,
                    'sample_records' => $coggedBelts,
                    'sql_query' => 'SELECT SUM(value) FROM cogged_belts'
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getPolyBeltTotalDebug()
    {
        try {
            $polyBelts = DB::table('poly_belts')
                ->select(['id', 'section', 'size', 'value', 'ribs'])
                ->whereNotNull('value')
                ->where('value', '>', 0)
                ->orderBy('value', 'desc')
                ->limit(20)
                ->get();

            $totalValue = DB::table('poly_belts')->sum('value');
            $count = DB::table('poly_belts')->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'belt_type' => 'Poly Belts',
                    'total_value' => $totalValue,
                    'total_count' => $count,
                    'sample_records' => $polyBelts,
                    'sql_query' => 'SELECT SUM(value) FROM poly_belts',
                    'stock_column' => 'ribs'
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
                'table' => 'poly_belts'
            ], 500);
        }
    }

    public function getTpuBeltTotalDebug()
    {
        try {
            $tpuBelts = DB::table('tpu_belts')
                ->select(['id', 'section', 'width', 'value', 'meter'])
                ->whereNotNull('value')
                ->where('value', '>', 0)
                ->orderBy('value', 'desc')
                ->limit(20)
                ->get();

            $totalValue = DB::table('tpu_belts')->sum('value');
            $count = DB::table('tpu_belts')->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'belt_type' => 'TPU Belts',
                    'total_value' => $totalValue,
                    'total_count' => $count,
                    'sample_records' => $tpuBelts,
                    'sql_query' => 'SELECT SUM(value) FROM tpu_belts',
                    'stock_column' => 'meter'
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
                'table' => 'tpu_belts'
            ], 500);
        }
    }

    public function getTimingBeltTotalDebug()
    {
        try {
            $timingBelts = DB::table('timing_belts')
                ->select(['id', 'section', 'size', 'value', 'total_mm'])
                ->whereNotNull('value')
                ->where('value', '>', 0)
                ->orderBy('value', 'desc')
                ->limit(20)
                ->get();

            $totalValue = DB::table('timing_belts')->sum('value');
            $count = DB::table('timing_belts')->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'belt_type' => 'Timing Belts',
                    'total_value' => $totalValue,
                    'total_count' => $count,
                    'sample_records' => $timingBelts,
                    'sql_query' => 'SELECT SUM(value) FROM timing_belts'
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getSpecialBeltTotalDebug()
    {
        try {
            $specialBelts = DB::table('special_belts')
                ->select(['id', 'section', 'size', 'value', 'balance_stock'])
                ->whereNotNull('value')
                ->where('value', '>', 0)
                ->orderBy('value', 'desc')
                ->limit(20)
                ->get();

            $totalValue = DB::table('special_belts')->sum('value');
            $count = DB::table('special_belts')->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'belt_type' => 'Special Belts',
                    'total_value' => $totalValue,
                    'total_count' => $count,
                    'sample_records' => $specialBelts,
                    'sql_query' => 'SELECT SUM(value) FROM special_belts'
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getAllBeltTotalsDebug()
    {
        try {
            $results = [];
            
            // Get totals for all belt types
            $beltTypes = [
                'vee_belts' => 'Vee Belts',
                'cogged_belts' => 'Cogged Belts', 
                'poly_belts' => 'Poly Belts',
                'tpu_belts' => 'TPU Belts',
                'timing_belts' => 'Timing Belts',
                'special_belts' => 'Special Belts'
            ];

            $grandTotal = 0;
            $totalProducts = 0;

            foreach ($beltTypes as $table => $name) {
                $totalValue = DB::table($table)->sum('value');
                $count = DB::table($table)->count();
                
                $results[$table] = [
                    'name' => $name,
                    'total_value' => round($totalValue, 2),
                    'count' => $count,
                    'sql' => "SELECT SUM(value) FROM {$table}"
                ];
                
                $grandTotal += $totalValue;
                $totalProducts += $count;
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'belt_types' => $results,
                    'grand_total_value' => round($grandTotal, 2),
                    'total_products' => $totalProducts,
                    'timestamp' => now()->toDateTimeString()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getVeeBeltSectionTotals()
    {
        try {
            // Get section-wise totals for vee belts
            $sectionTotals = DB::table('vee_belts')
                ->select([
                    'section',
                    DB::raw('COUNT(*) as count'),
                    DB::raw('SUM(COALESCE(value, 0)) as total_value'),
                    DB::raw('AVG(COALESCE(value, 0)) as avg_value'),
                    DB::raw('MAX(COALESCE(value, 0)) as max_value'),
                    DB::raw('MIN(COALESCE(value, 0)) as min_value')
                ])
                ->groupBy('section')
                ->orderBy('total_value', 'desc')
                ->get();

            $grandTotal = $sectionTotals->sum('total_value');

            return response()->json([
                'success' => true,
                'data' => [
                    'section_totals' => $sectionTotals,
                    'grand_total' => round($grandTotal, 2),
                    'total_sections' => $sectionTotals->count(),
                    'sql_query' => 'SELECT section, COUNT(*) as count, SUM(COALESCE(value, 0)) as total_value FROM vee_belts GROUP BY section ORDER BY total_value DESC'
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function checkTableStructures()
    {
        try {
            $tables = ['vee_belts', 'cogged_belts', 'poly_belts', 'tpu_belts', 'timing_belts', 'special_belts'];
            $structures = [];

            foreach ($tables as $table) {
                try {
                    $columns = DB::getSchemaBuilder()->getColumnListing($table);
                    $sampleRecord = DB::table($table)->first();
                    
                    $structures[$table] = [
                        'exists' => true,
                        'columns' => $columns,
                        'has_value_column' => in_array('value', $columns),
                        'has_total_value_column' => in_array('total_value', $columns),
                        'sample_record' => $sampleRecord,
                        'record_count' => DB::table($table)->count()
                    ];
                } catch (\Exception $e) {
                    $structures[$table] = [
                        'exists' => false,
                        'error' => $e->getMessage()
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'data' => $structures
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
}