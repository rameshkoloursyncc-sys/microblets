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
                    DB::raw('SUM(CASE WHEN reorder_level IS NOT NULL AND reorder_level > 1 AND balance_stock > 0 AND balance_stock <= reorder_level THEN 1 ELSE 0 END) as low_stock'),
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
                    DB::raw('SUM(CASE WHEN reorder_level IS NOT NULL AND reorder_level > 1 AND balance_stock > 0 AND balance_stock <= reorder_level THEN 1 ELSE 0 END) as low_stock'),
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
                    DB::raw("SUM(CASE WHEN reorder_level IS NOT NULL AND reorder_level > 1 AND {$stockColumn} > 0 AND {$stockColumn} <= reorder_level THEN 1 ELSE 0 END) as low_stock"),
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
                    DB::raw("SUM(CASE WHEN reorder_level IS NOT NULL AND reorder_level > 1 AND {$stockColumn} > 0 AND {$stockColumn} <= reorder_level THEN 1 ELSE 0 END) as low_stock"),
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
                    DB::raw("SUM(CASE WHEN reorder_level IS NOT NULL AND reorder_level > 1 AND {$stockColumn} > 0 AND {$stockColumn} <= reorder_level THEN 1 ELSE 0 END) as low_stock"),
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
                    DB::raw('SUM(CASE WHEN reorder_level IS NOT NULL AND reorder_level > 1 AND balance_stock > 0 AND balance_stock <= reorder_level THEN 1 ELSE 0 END) as low_stock'),
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

    /**
     * Get raw materials inventory statistics
     */
    public function getRawMaterialsStats()
    {
        try {
            // Get all raw materials categories
            $categories = ['Carbon', 'Chemical', 'Cord - Cogged Belt', 'Cord - Timing Belt', 'Cord - Vee Belt', 
                          'Fabric - Cogged Belt', 'Fabric - Timing Belt', 'Fabric - Vee Belt', 'Fabric - TPU Belt',
                          'Oil', 'Others', 'Resin', 'Rubber', 'TPU', 'Fibre Glass Cord', 'Steel Wire', 'Packing', 'Open'];
            
            // Calculate totals
            $result = DB::table('raw_carbons')
                ->select([
                    DB::raw('COUNT(*) as total_products'),
                    DB::raw('SUM(CASE WHEN balance_stock > 0 THEN 1 ELSE 0 END) as in_stock'),
                    DB::raw('SUM(CASE WHEN reorder_level IS NOT NULL AND reorder_level > 1 AND balance_stock > 0 AND balance_stock <= reorder_level THEN 1 ELSE 0 END) as low_stock'),
                    DB::raw('SUM(CASE WHEN balance_stock = 0 THEN 1 ELSE 0 END) as out_of_stock'),
                    DB::raw('SUM(COALESCE(value, 0)) as total_value')
                ])
                ->first();

            // Get category-wise breakdown
            $categoryValues = [];
            foreach ($categories as $category) {
                $categoryResult = DB::table('raw_carbons')
                    ->where('category', $category)
                    ->select(DB::raw('SUM(COALESCE(value, 0)) as total_value'))
                    ->first();
                
                $categoryValues[$category] = $categoryResult->total_value ?? 0;
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'totals' => [
                        'total_value' => round($result->total_value ?? 0, 2),
                        'total_products' => $result->total_products ?? 0,
                        'in_stock' => $result->in_stock ?? 0,
                        'low_stock' => $result->low_stock ?? 0,
                        'out_of_stock' => $result->out_of_stock ?? 0,
                    ],
                    'category_values' => array_map(function($value) {
                        return round($value, 2);
                    }, $categoryValues)
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error calculating raw materials stats: ' . $e->getMessage()
            ], 500);
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

    public function getLowStockItems()
    {
        try {
            $lowStockItems = [];
            $outOfStockItems = [];

            // Get low stock and out of stock items from all belt types
            $beltTypes = [
                'vee_belts' => ['stock_column' => 'balance_stock', 'size_column' => 'size', 'name' => 'Vee Belts'],
                'cogged_belts' => ['stock_column' => 'balance_stock', 'size_column' => 'size', 'name' => 'Cogged Belts'],
                'poly_belts' => ['stock_column' => 'ribs', 'size_column' => 'size', 'name' => 'Poly Belts'],
                'tpu_belts' => ['stock_column' => 'meter', 'size_column' => 'width', 'name' => 'TPU Belts'],
                'timing_belts' => ['stock_column' => 'total_mm', 'size_column' => 'size', 'name' => 'Timing Belts'],
                'special_belts' => ['stock_column' => 'balance_stock', 'size_column' => 'size', 'name' => 'Special Belts']
            ];

            foreach ($beltTypes as $table => $config) {
                // Check if table exists and has required columns
                $columns = DB::getSchemaBuilder()->getColumnListing($table);
                if (!in_array($config['stock_column'], $columns)) {
                    $config['stock_column'] = 'balance_stock'; // fallback
                }
                if (!in_array($config['size_column'], $columns)) {
                    $config['size_column'] = 'size'; // fallback
                }

                // Build select array based on available columns
                $selectColumns = [
                    'id',
                    'section',
                    $config['size_column'] . ' as size',
                    $config['stock_column'] . ' as current_stock',
                    'reorder_level'
                ];

                // Add optional columns if they exist
                if (in_array('sku', $columns)) {
                    $selectColumns[] = 'sku';
                }
                if (in_array('value', $columns)) {
                    $selectColumns[] = 'value';
                }

                // Get LOW STOCK items (reorder_level >= 1 AND current_stock > 0 AND current_stock <= reorder_level)
                $lowStockQuery = DB::table($table)
                    ->select($selectColumns)
                    ->whereNotNull('reorder_level')
                    ->where('reorder_level', '>=', 1)
                    ->whereRaw("{$config['stock_column']} > 0")
                    ->whereRaw("{$config['stock_column']} <= reorder_level")
                    ->orderBy('section')
                    ->orderBy($config['size_column'])
                    ->get();

                // Get OUT OF STOCK items (reorder_level >= 1 AND current_stock = 0)
                $outOfStockQuery = DB::table($table)
                    ->select($selectColumns)
                    ->whereNotNull('reorder_level')
                    ->where('reorder_level', '>=', 1)
                    ->whereRaw("{$config['stock_column']} = 0")
                    ->orderBy('section')
                    ->orderBy($config['size_column'])
                    ->get();

                if ($lowStockQuery->count() > 0) {
                    $lowStockItems[$table] = [
                        'name' => $config['name'],
                        'items' => $lowStockQuery->toArray(),
                        'count' => $lowStockQuery->count()
                    ];
                }

                if ($outOfStockQuery->count() > 0) {
                    $outOfStockItems[$table] = [
                        'name' => $config['name'],
                        'items' => $outOfStockQuery->toArray(),
                        'count' => $outOfStockQuery->count()
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'low_stock_items' => $lowStockItems,
                    'out_of_stock_items' => $outOfStockItems,
                    'total_low_stock_count' => array_sum(array_column($lowStockItems, 'count')),
                    'total_out_of_stock_count' => array_sum(array_column($outOfStockItems, 'count')),
                    'total_alert_count' => array_sum(array_column($lowStockItems, 'count')) + array_sum(array_column($outOfStockItems, 'count')),
                    'generated_at' => now()->toDateTimeString()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching stock alert items: ' . $e->getMessage()
            ], 500);
        }
    }

    public function sendStockAlert(Request $request)
    {
        try {
            // Use the same logic as Smart Alerts since that's working
            $smartAlertService = new \App\Services\SmartStockAlertService();
            
            // Get email addresses from request or config
            $emails = $request->input('emails', explode(',', config('mail.low_stock_recipients', 'ramesh.koloursyncc@gmail.com,microbelts@gmail.com')));
            if (is_string($emails)) {
                $emails = explode(',', $emails);
            }
            
            // Clean up email addresses (trim whitespace)
            $emails = array_map('trim', $emails);
            
            // Get the alert data using the correct Smart Alert logic
            $smartAlertService->syncStockAlertTracking();
            $itemsNeedingAlerts = $smartAlertService->getItemsNeedingAlerts();
            $alertData = $smartAlertService->prepareAlertData($itemsNeedingAlerts);
            
            // Debug: Log the data structure
            \Log::info('Send Stock Alert - Using Smart Alert Data:', $alertData);
            
            $totalItems = $alertData['total_items'] ?? 0;
            
            if ($totalItems > 0 || $request->input('force', false)) {
                foreach ($emails as $email) {
                    // Email 1: Smart Alert Excel (existing)
                    \Mail::to(trim($email))->send(new \App\Mail\SmartStockReportExcel($alertData));
                    
                    // Email 2: Production Planning Excel (new)
                    \Mail::to(trim($email))->send(new \App\Mail\ProductionPlanningExcel($alertData));
                }
                
                // Trigger refresh of all table data to show updated alert status
                
                return response()->json([
                    'success' => true,
                    'message' => "Two Excel reports sent successfully to " . count($emails) . " recipient(s) - Stock Alert & Production Planning. Items: {$totalItems}",
                    'data' => [
                        'total_items' => $totalItems,
                        'total_dies_needed' => $alertData['total_dies_needed'] ?? 0,
                        'recipients' => $emails,
                        'sent_at' => now()->toDateTimeString()
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => true,
                    'message' => 'No stock alerts found. Use force=true to send anyway.',
                    'data' => [
                        'total_items' => $totalItems,
                        'debug_data' => $alertData
                    ]
                ]);
            }

        } catch (\Exception $e) {
            \Log::error('Send Stock Alert Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error sending stock alert report: ' . $e->getMessage()
            ], 500);
        }
    }

    private function getStockAlertData()
    {
        $lowStockItems = [];
        $outOfStockItems = [];

        // Get low stock and out of stock items from all belt types
        $beltTypes = [
            'vee_belts' => ['stock_column' => 'balance_stock', 'size_column' => 'size', 'name' => 'Vee Belts'],
            'cogged_belts' => ['stock_column' => 'balance_stock', 'size_column' => 'size', 'name' => 'Cogged Belts'],
            'poly_belts' => ['stock_column' => 'ribs', 'size_column' => 'size', 'name' => 'Poly Belts'],
            'tpu_belts' => ['stock_column' => 'meter', 'size_column' => 'width', 'name' => 'TPU Belts'],
            'timing_belts' => ['stock_column' => 'total_mm', 'size_column' => 'size', 'name' => 'Timing Belts'],
            'special_belts' => ['stock_column' => 'balance_stock', 'size_column' => 'size', 'name' => 'Special Belts']
        ];

        foreach ($beltTypes as $table => $config) {
            try {
                // Check if table exists and has required columns
                $columns = DB::getSchemaBuilder()->getColumnListing($table);
                if (!in_array($config['stock_column'], $columns)) {
                    $config['stock_column'] = 'balance_stock'; // fallback
                }
                if (!in_array($config['size_column'], $columns)) {
                    $config['size_column'] = 'size'; // fallback
                }

                // Build select array based on available columns
                $selectColumns = [
                    'id',
                    'section',
                    $config['size_column'] . ' as size',
                    $config['stock_column'] . ' as current_stock',
                    'reorder_level'
                ];

                // Add optional columns if they exist
                if (in_array('sku', $columns)) {
                    $selectColumns[] = 'sku';
                }
                if (in_array('value', $columns)) {
                    $selectColumns[] = 'value';
                }

                // Get LOW STOCK items (reorder_level >= 1 AND current_stock > 0 AND current_stock <= reorder_level)
                $lowStockQuery = DB::table($table)
                    ->select($selectColumns)
                    ->whereNotNull('reorder_level')
                    ->where('reorder_level', '>=', 1)
                    ->whereRaw("{$config['stock_column']} > 0")
                    ->whereRaw("{$config['stock_column']} <= reorder_level")
                    ->orderBy('section')
                    ->orderBy($config['size_column'])
                    ->get();

                // Get OUT OF STOCK items (reorder_level >= 1 AND current_stock = 0)
                $outOfStockQuery = DB::table($table)
                    ->select($selectColumns)
                    ->whereNotNull('reorder_level')
                    ->where('reorder_level', '>=', 1)
                    ->whereRaw("{$config['stock_column']} = 0")
                    ->orderBy('section')
                    ->orderBy($config['size_column'])
                    ->get();

                if ($lowStockQuery->count() > 0) {
                    $lowStockItems[$table] = [
                        'name' => $config['name'],
                        'items' => $lowStockQuery->toArray(),
                        'count' => $lowStockQuery->count()
                    ];
                }

                if ($outOfStockQuery->count() > 0) {
                    $outOfStockItems[$table] = [
                        'name' => $config['name'],
                        'items' => $outOfStockQuery->toArray(),
                        'count' => $outOfStockQuery->count()
                    ];
                }
            } catch (\Exception $e) {
                // Log error but continue with other tables
                \Log::warning("Error processing {$table} for stock alerts: " . $e->getMessage());
            }
        }

        return [
            'low_stock_items' => $lowStockItems,
            'out_of_stock_items' => $outOfStockItems,
            'total_low_stock_count' => array_sum(array_column($lowStockItems, 'count')),
            'total_out_of_stock_count' => array_sum(array_column($outOfStockItems, 'count')),
            'total_alert_count' => array_sum(array_column($lowStockItems, 'count')) + array_sum(array_column($outOfStockItems, 'count')),
            'generated_at' => now()->toDateTimeString()
        ];







    }

    /**
 * Send smart stock alerts
 */
public function sendSmartStockAlert(Request $request)
{
    try {
        $smartAlertService = new \App\Services\SmartStockAlertService();
        
        $emails = $request->input('emails', explode(',', config('mail.low_stock_recipients', 'ramesh.koloursyncc@gmail.com,microbelts@gmail.com')));
        $force = $request->input('force', false);
        
        // Clean up email addresses (trim whitespace)
        if (is_array($emails)) {
            $emails = array_map('trim', $emails);
        }
        
        if ($force) {
            $result = $smartAlertService->sendSmartAlertsForced($emails);
        } else {
            $result = $smartAlertService->sendSmartAlerts($emails);
        }
        
        return response()->json($result);
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error sending smart stock alerts: ' . $e->getMessage()
        ], 500);
    }
}

/**
 * Get die requirements summary
 */
public function getDieRequirements()
{
    try {
        $smartAlertService = new \App\Services\SmartStockAlertService();
        $summary = $smartAlertService->getDieRequirementsUnalerted();
        
        return response()->json([
            'success' => true,
            'data' => $summary
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error getting die requirements: ' . $e->getMessage()
        ], 500);
    }
}

/**
 * Mark stock alerts as sent in StockAlertTracking table
 */
private function markStockAlertsAsSent($lowStockData)
{
    try {
        // First sync the stock alert tracking data
        $smartAlertService = new \App\Services\SmartStockAlertService();
        $smartAlertService->syncStockAlertTracking();
        
        // Get all low stock and out of stock items from the data
        $allItems = array_merge(
            $lowStockData['low_stock_items'] ?? [],
            $lowStockData['out_of_stock_items'] ?? []
        );
        
        // Map table names to belt types
        $tableToType = [
            'vee_belts' => 'vee',
            'cogged_belts' => 'cogged',
            'poly_belts' => 'poly',
            'tpu_belts' => 'tpu',
            'timing_belts' => 'timing',
            'special_belts' => 'special'
        ];
        
        foreach ($allItems as $table => $data) {
            $beltType = $tableToType[$table] ?? null;
            if (!$beltType || !isset($data['items'])) {
                continue;
            }
            
            foreach ($data['items'] as $item) {
                // Find the corresponding tracking record and mark as sent
                $tracking = \App\Models\StockAlertTracking::where('belt_type', $beltType)
                    ->where('product_id', $item->id)
                    ->where('is_active', true)
                    ->first();
                
                if ($tracking) {
                    $tracking->markAlertSent();
                }
            }
        }
        
    } catch (\Exception $e) {
        \Log::warning("Error marking stock alerts as sent: " . $e->getMessage());
    }
}

/**
 * Download Excel report without sending email
 */
public function downloadExcelReport(Request $request)
{
    try {
        // Use the same logic as Smart Alerts since that's working
        $smartAlertService = new \App\Services\SmartStockAlertService();
        $smartAlertService->syncStockAlertTracking();
        $itemsNeedingAlerts = $smartAlertService->getItemsNeedingAlerts();
        $alertData = $smartAlertService->prepareAlertData($itemsNeedingAlerts);
        
        // Debug: Log the data structure
        \Log::info('Download Excel - Using Smart Alert Data:', $alertData);
        
        // Check if we have any data
        $totalItems = $alertData['total_items'] ?? 0;
        
        if ($totalItems === 0) {
            // If no data, return a message
            return response()->json([
                'success' => false,
                'message' => 'No low stock or out of stock items found. Total items: ' . $totalItems,
                'debug_data' => $alertData
            ], 200);
        }
        
        // Use the ExcelExportService to generate the Excel file (Smart Alert version)
        $excelService = new \App\Services\ExcelExportService();
        $spreadsheet = $excelService->generateSmartStockAlertExcel($alertData);
        
        // Convert spreadsheet to binary content
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        
        // Create temporary file
        $tempFile = tempnam(sys_get_temp_dir(), 'stock_report_');
        $writer->save($tempFile);
        
        // Get file content
        $excelContent = file_get_contents($tempFile);
        
        // Clean up temp file
        unlink($tempFile);
        
        // Set filename with current date
        $filename = 'stock-report-' . date('Y-m-d') . '.xlsx';
        
        return response($excelContent)
            ->header('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->header('Cache-Control', 'max-age=0');
            
    } catch (\Exception $e) {
        \Log::error('Download Excel Error: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Error generating Excel report: ' . $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
}

/**
 * Debug stock data to see what's being returned
 */
public function debugStockData(Request $request)
{
    try {
        $lowStockData = $this->getStockAlertData();
        
        // Also get some sample data from each table to see the structure
        $debugInfo = [];
        
        $beltTypes = [
            'vee_belts' => ['stock_column' => 'balance_stock', 'size_column' => 'size'],
            'cogged_belts' => ['stock_column' => 'balance_stock', 'size_column' => 'size'],
            'poly_belts' => ['stock_column' => 'ribs', 'size_column' => 'size'],
            'tpu_belts' => ['stock_column' => 'meter', 'size_column' => 'width'],
            'timing_belts' => ['stock_column' => 'total_mm', 'size_column' => 'size'],
        ];
        
        foreach ($beltTypes as $table => $config) {
            try {
                // Get table structure
                $columns = DB::getSchemaBuilder()->getColumnListing($table);
                
                // Get sample records
                $sampleRecords = DB::table($table)
                    ->select(['id', 'section', $config['size_column'], $config['stock_column'], 'reorder_level'])
                    ->limit(5)
                    ->get();
                
                // Count records with reorder_level set
                $withReorderLevel = DB::table($table)
                    ->whereNotNull('reorder_level')
                    ->where('reorder_level', '>=', 1)
                    ->count();
                
                // Count low stock items
                $lowStockCount = DB::table($table)
                    ->whereNotNull('reorder_level')
                    ->where('reorder_level', '>=', 1)
                    ->whereRaw("{$config['stock_column']} > 0")
                    ->whereRaw("{$config['stock_column']} <= reorder_level")
                    ->count();
                
                // Count out of stock items
                $outOfStockCount = DB::table($table)
                    ->whereNotNull('reorder_level')
                    ->where('reorder_level', '>=', 1)
                    ->whereRaw("{$config['stock_column']} = 0")
                    ->count();
                
                $debugInfo[$table] = [
                    'columns' => $columns,
                    'sample_records' => $sampleRecords,
                    'total_records' => DB::table($table)->count(),
                    'with_reorder_level' => $withReorderLevel,
                    'low_stock_count' => $lowStockCount,
                    'out_of_stock_count' => $outOfStockCount,
                    'stock_column' => $config['stock_column'],
                    'size_column' => $config['size_column']
                ];
                
            } catch (\Exception $e) {
                $debugInfo[$table] = ['error' => $e->getMessage()];
            }
        }
        
        return response()->json([
            'success' => true,
            'low_stock_data' => $lowStockData,
            'debug_info' => $debugInfo
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Debug error: ' . $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
}

    /**
     * Get dashboard snapshot for a specific date or date range
     * Supports: single date, date range, week, month, year
     */
    public function getSnapshot(Request $request)
    {
        try {
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            $date = $request->input('date');
            
            // If single date provided, use it
            if ($date && !$startDate && !$endDate) {
                $snapshot = \App\Models\DashboardSnapshot::where('snapshot_date', $date)->first();
                
                if (!$snapshot) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No snapshot found for this date'
                    ], 404);
                }
                
                return response()->json([
                    'success' => true,
                    'type' => 'single',
                    'data' => $this->formatSnapshotData($snapshot)
                ]);
            }
            
            // If date range provided, aggregate the data
            if ($startDate && $endDate) {
                $snapshots = \App\Models\DashboardSnapshot::whereBetween('snapshot_date', [$startDate, $endDate])
                    ->orderBy('snapshot_date', 'asc')
                    ->get();
                
                if ($snapshots->isEmpty()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No snapshots found for this date range'
                    ], 404);
                }
                
                // Aggregate the data
                $aggregated = $this->aggregateSnapshots($snapshots);
                
                return response()->json([
                    'success' => true,
                    'type' => 'range',
                    'date_range' => [
                        'start' => $startDate,
                        'end' => $endDate,
                        'days' => $snapshots->count()
                    ],
                    'data' => $aggregated
                ]);
            }
            
            // Default: return today's snapshot
            $today = now()->toDateString();
            $snapshot = \App\Models\DashboardSnapshot::where('snapshot_date', $today)->first();
            
            if (!$snapshot) {
                // If today's snapshot doesn't exist, get the latest available
                $snapshot = \App\Models\DashboardSnapshot::orderBy('snapshot_date', 'desc')->first();
            }
            
            if (!$snapshot) {
                return response()->json([
                    'success' => false,
                    'message' => 'No snapshots available'
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'type' => 'single',
                'data' => $this->formatSnapshotData($snapshot)
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching snapshot: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Format single snapshot data
     */
    private function formatSnapshotData($snapshot)
    {
        return [
            'snapshot_date' => $snapshot->snapshot_date->format('Y-m-d'),
            'finished_goods' => [
                'total_products' => $snapshot->finished_total_products,
                'in_stock' => $snapshot->finished_in_stock,
                'low_stock' => $snapshot->finished_low_stock,
                'out_of_stock' => $snapshot->finished_out_of_stock,
                'total_value' => $snapshot->finished_total_value,
                'categories' => [
                    'vee_belts' => $snapshot->vee_belts_value,
                    'cogged_belts' => $snapshot->cogged_belts_value,
                    'poly_belts' => $snapshot->poly_belts_value,
                    'tpu_belts' => $snapshot->tpu_belts_value,
                    'timing_belts' => $snapshot->timing_belts_value,
                    'special_belts' => $snapshot->special_belts_value,
                ]
            ],
            'raw_materials' => [
                'total_materials' => $snapshot->raw_total_materials,
                'available' => $snapshot->raw_available,
                'low_stock' => $snapshot->raw_low_stock,
                'out_of_stock' => $snapshot->raw_out_of_stock,
                'total_value' => $snapshot->raw_total_value,
                'categories' => [
                    'Carbon' => $snapshot->raw_carbon_value,
                    'Chemical' => $snapshot->raw_chemical_value,
                    'Cord (All)' => $snapshot->raw_cord_all_value,
                    'Fabric (All)' => $snapshot->raw_fabric_all_value,
                    'Oil' => $snapshot->raw_oil_value,
                    'Others' => $snapshot->raw_others_value,
                    'Resin' => $snapshot->raw_resin_value,
                    'Rubber' => $snapshot->raw_rubber_value,
                    'TPU' => $snapshot->raw_tpu_value,
                    'Fibre Glass Cord' => $snapshot->raw_fibre_glass_cord_value,
                    'Steel Wire' => $snapshot->raw_steel_wire_value,
                    'Packing' => $snapshot->raw_packing_value,
                    'Open' => $snapshot->raw_open_value,
                ]
            ],
            'die_requirements' => $snapshot->die_requirements,
            'total_alerts' => $snapshot->total_alerts,
        ];
    }
    
    /**
     * Aggregate multiple snapshots (average values)
     */
    private function aggregateSnapshots($snapshots)
    {
        $count = $snapshots->count();
        
        return [
            'snapshot_dates' => $snapshots->pluck('snapshot_date')->map(fn($d) => $d->format('Y-m-d'))->toArray(),
            'finished_goods' => [
                'avg_total_products' => round($snapshots->avg('finished_total_products')),
                'avg_in_stock' => round($snapshots->avg('finished_in_stock')),
                'avg_low_stock' => round($snapshots->avg('finished_low_stock')),
                'avg_out_of_stock' => round($snapshots->avg('finished_out_of_stock')),
                'avg_total_value' => round($snapshots->avg('finished_total_value'), 2),
                'total_value_sum' => round($snapshots->sum('finished_total_value'), 2),
                'categories' => [
                    'vee_belts' => round($snapshots->avg('vee_belts_value'), 2),
                    'cogged_belts' => round($snapshots->avg('cogged_belts_value'), 2),
                    'poly_belts' => round($snapshots->avg('poly_belts_value'), 2),
                    'tpu_belts' => round($snapshots->avg('tpu_belts_value'), 2),
                    'timing_belts' => round($snapshots->avg('timing_belts_value'), 2),
                    'special_belts' => round($snapshots->avg('special_belts_value'), 2),
                ]
            ],
            'raw_materials' => [
                'avg_total_materials' => round($snapshots->avg('raw_total_materials')),
                'avg_available' => round($snapshots->avg('raw_available')),
                'avg_low_stock' => round($snapshots->avg('raw_low_stock')),
                'avg_out_of_stock' => round($snapshots->avg('raw_out_of_stock')),
                'avg_total_value' => round($snapshots->avg('raw_total_value'), 2),
                'total_value_sum' => round($snapshots->sum('raw_total_value'), 2),
                'categories' => [
                    'Carbon' => round($snapshots->avg('raw_carbon_value'), 2),
                    'Chemical' => round($snapshots->avg('raw_chemical_value'), 2),
                    'Cord (All)' => round($snapshots->avg('raw_cord_all_value'), 2),
                    'Fabric (All)' => round($snapshots->avg('raw_fabric_all_value'), 2),
                    'Oil' => round($snapshots->avg('raw_oil_value'), 2),
                    'Others' => round($snapshots->avg('raw_others_value'), 2),
                    'Resin' => round($snapshots->avg('raw_resin_value'), 2),
                    'Rubber' => round($snapshots->avg('raw_rubber_value'), 2),
                    'TPU' => round($snapshots->avg('raw_tpu_value'), 2),
                    'Fibre Glass Cord' => round($snapshots->avg('raw_fibre_glass_cord_value'), 2),
                    'Steel Wire' => round($snapshots->avg('raw_steel_wire_value'), 2),
                    'Packing' => round($snapshots->avg('raw_packing_value'), 2),
                    'Open' => round($snapshots->avg('raw_open_value'), 2),
                ]
            ],
            'avg_total_alerts' => round($snapshots->avg('total_alerts')),
            'trend' => $this->calculateTrend($snapshots),
        ];
    }
    
    /**
     * Calculate trend (first vs last snapshot)
     */
    private function calculateTrend($snapshots)
    {
        if ($snapshots->count() < 2) {
            return null;
        }
        
        $first = $snapshots->first();
        $last = $snapshots->last();
        
        $finishedChange = $last->finished_total_value - $first->finished_total_value;
        $finishedPercent = $first->finished_total_value > 0 
            ? round(($finishedChange / $first->finished_total_value) * 100, 2) 
            : 0;
            
        $rawChange = $last->raw_total_value - $first->raw_total_value;
        $rawPercent = $first->raw_total_value > 0 
            ? round(($rawChange / $first->raw_total_value) * 100, 2) 
            : 0;
        
        return [
            'finished_goods' => [
                'change' => round($finishedChange, 2),
                'percent' => $finishedPercent,
                'direction' => $finishedChange > 0 ? 'up' : ($finishedChange < 0 ? 'down' : 'stable')
            ],
            'raw_materials' => [
                'change' => round($rawChange, 2),
                'percent' => $rawPercent,
                'direction' => $rawChange > 0 ? 'up' : ($rawChange < 0 ? 'down' : 'stable')
            ]
        ];
    }
    
    /**
     * Get available snapshot dates
     */
    public function getAvailableDates()
    {
        try {
            $dates = \App\Models\DashboardSnapshot::orderBy('snapshot_date', 'desc')
                ->pluck('snapshot_date')
                ->map(fn($date) => $date->format('Y-m-d'))
                ->toArray();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'dates' => $dates,
                    'count' => count($dates),
                    'latest' => $dates[0] ?? null,
                    'oldest' => end($dates) ?: null,
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching dates: ' . $e->getMessage()
            ], 500);
        }
    }
}
