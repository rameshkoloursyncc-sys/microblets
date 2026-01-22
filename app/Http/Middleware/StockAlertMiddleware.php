<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use App\Models\StockAlertTracking;
use App\Models\VeeBelt;
use App\Models\CoggedBelt;
use App\Models\PolyBelt;
use App\Models\TpuBelt;
use App\Models\TimingBelt;
use App\Models\SpecialBelt;

class StockAlertMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Process the request first
        $response = $next($request);

        // Only process on inventory-related routes
        if ($this->shouldProcessStockAlert($request)) {
            $this->updateStockAlertTracking();
        }

        return $response;
    }

    /**
     * Check if we should process stock alerts for this request
     */
    private function shouldProcessStockAlert(Request $request): bool
    {
        // Only process on POST, PUT, PATCH, DELETE operations that modify inventory
        if (!in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            return false;
        }

        $inventoryRoutes = [
            'api/vee-belts',
            'api/cogged-belts',
            'api/poly-belts',
            'api/tpu-belts',
            'api/timing-belts',
            'api/special-belts'
        ];

        foreach ($inventoryRoutes as $route) {
            if (str_contains($request->path(), $route)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Update stock alert tracking for all belt types
     */
    private function updateStockAlertTracking()
    {
        try {
            // Check if the table exists before processing
            if (!Schema::hasTable('stock_alert_tracking')) {
                return;
            }

            $beltTypes = [
                'vee' => VeeBelt::class,
                'cogged' => CoggedBelt::class,
                'poly' => PolyBelt::class,
                'tpu' => TpuBelt::class,
                'timing' => TimingBelt::class,
                'special' => SpecialBelt::class
            ];

            foreach ($beltTypes as $beltType => $model) {
                $this->updateBeltTypeTracking($beltType, $model);
            }
        } catch (\Exception $e) {
            // Silently fail if there are any database issues
            Log::error('StockAlertMiddleware error: ' . $e->getMessage());
        }
    }

    /**
     * Update tracking for specific belt type
     */
    private function updateBeltTypeTracking(string $beltType, string $modelClass)
    {
        $products = $modelClass::where('reorder_level', '>', 0)->get();

        foreach ($products as $product) {
            $currentStock = $this->getCurrentStock($product, $beltType);
            $reorderLevel = $product->reorder_level ?? 0;

            if ($reorderLevel <= 0) continue;

            // Get or create tracking record
            $tracking = StockAlertTracking::updateOrCreate(
                [
                    'belt_type' => $beltType,
                    'section' => $product->section,
                    'product_id' => $product->id
                ],
                [
                    'product_sku' => $product->sku ?? $this->generateSku($product, $beltType),
                    'current_stock' => $currentStock,
                    'reorder_level' => $reorderLevel,
                    'stock_per_die' => $this->getStockPerDie($product, $beltType),
                    'is_active' => true
                ]
            );

            // Calculate dies needed
            $tracking->calculateDiesNeeded();

            // Check if stock is replenished and reset alert if needed
            $tracking->checkAndResetIfReplenished($currentStock);
        }
    }

    /**
     * Get current stock based on belt type
     */
    private function getCurrentStock($product, string $beltType): float
    {
        switch ($beltType) {
            case 'timing':
                return $product->category === 'Commercial' 
                    ? ($product->total_mm ?? 0) 
                    : ($product->full_sleeve ?? 0);
            case 'special':
                return $product->balance_stock ?? 0;
            case 'poly':
                return $product->meter ?? 0;
            default:
                return $product->balance_stock ?? $product->meter ?? 0;
        }
    }

    /**
     * Get stock per die (configurable per section)
     */
    private function getStockPerDie($product, string $beltType): float
    {
        // Default die production amounts - can be made configurable
        $dieProduction = [
            'vee' => [
                'A' => 34, 'B' => 26, 'C' => 20, 'D' => 35, 'E' => 30,
                'SPA' => 20, 'SPB' => 24, 'SPC' => 20, 'SPZ' => 28,
                '3V' => 25, '5V' => 20, '8V' => 15
            ],
            'cogged' => [
                'AX' => 45, 'BX' => 40, 'CX' => 35,
                'XPA' => 50, 'XPB' => 45, 'XPC' => 40, 'XPZ' => 55,
                '3VX' => 25, '5VX' => 20
            ],
            'poly' => [
                'PJ' => 100, 'PK' => 80, 'PL' => 70, 'PM' => 60, 'PH' => 50,
                'DPL' => 65, 'DPK' => 75
            ],
            'tpu' => [
                '5M' => 30, '8M' => 25, '8M RPP' => 25, 'S8M' => 20, '14M' => 15,
                'XL' => 35, 'L' => 40, 'H' => 35, 'AT5' => 50, 'AT10' => 30, 'T10' => 25, 'AT20' => 20
            ],
            'timing' => [
                'XL' => 30, 'L' => 35, 'H' => 30, 'XH' => 25, 'T5' => 45, 'T10' => 35,
                '3M' => 40, '5M' => 35, '8M' => 30, '14M' => 20,
                'DL' => 25, 'DH' => 20, 'D5M' => 30, 'D8M' => 25
            ],
            'special' => [
                'Conical C' => 20, 'Harvester' => 15, 'RAX' => 35, 'RBX' => 30,
                'R3VX' => 25, 'R5VX' => 20, '8M PK' => 30, '8M PL' => 25
            ]
        ];

        return $dieProduction[$beltType][$product->section] ?? 30; // Default 30 units per die
    }

    /**
     * Generate SKU if not exists
     */
    private function generateSku($product, string $beltType): string
    {
        return strtoupper($beltType) . '-' . $product->section . '-' . $product->size;
    }
}
