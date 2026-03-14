<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TimingBelt;
use App\Models\InventoryTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TimingBeltController extends Controller
{
    /**
     * Display a listing of timing belts
     */
    public function index(Request $request)
    {
        $query = TimingBelt::query();
        
        $query->with('stockAlert');

        // Filter by section if provided
        if ($request->has('section')) {
            $query->bySection($request->section);
        }

        // Search functionality
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('section', 'like', "%{$search}%")
                  ->orWhere('size', 'like', "%{$search}%")
                  ->orWhere('type', 'like', "%{$search}%")
                  ->orWhere('remark', 'like', "%{$search}%");
            });
        }

        $timingBelts = $query->orderByRaw('CAST(size AS UNSIGNED) ASC')
                            ->get();

        return response()->json($timingBelts);
    }

    /**
     * Get timing belts by specific section
     */
    public function getBySection($section)
    {
        $timingBelts = TimingBelt::bySection($section)
                                 ->with('stockAlert')
                                 ->orderByRaw('CAST(size AS UNSIGNED) ASC')
                                 ->get();

        return response()->json($timingBelts);
    }

    /**
     * Store a newly created timing belt
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'section' => 'required|string|max:20',
            'size' => 'required|string|max:20',
            'type' => 'nullable|string|max:50',
            'mm' => 'nullable|numeric|min:0',
            'total_mm' => 'nullable|numeric|min:0',
            'rate' => 'nullable|numeric|min:0',
            'reorder_level' => 'nullable|integer|min:0',
            'remark' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $data = $request->all();
            
            // Set default values
            if (!isset($data['total_mm'])) {
                $data['total_mm'] = $data['mm'] ?? 0;
            }
            if (!isset($data['type'])) {
                $data['type'] = '1 (FULL SLEEVE)';
            }
            
            $data['created_by'] = session('user')['id'] ?? null;
            
            $timingBelt = TimingBelt::create($data);
            return response()->json($timingBelt, 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create timing belt',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified timing belt
     */
    public function show(TimingBelt $timingBelt)
    {
        return response()->json($timingBelt);
    }

    /**
     * Update the specified timing belt
     */
    public function update(Request $request, TimingBelt $timingBelt)
    {
        $validator = Validator::make($request->all(), [
            'section' => 'sometimes|required|string|max:20',
            'size' => 'sometimes|required|string|max:20',
            'type' => 'nullable|string|max:50',
            'mm' => 'nullable|numeric|min:0',
            'total_mm' => 'nullable|numeric|min:0',
            'rate' => 'nullable|numeric|min:0',
            'reorder_level' => 'nullable|integer|min:0',
            'remark' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $data = $request->all();
            $data['updated_by'] = session('user')['id'] ?? null;
            
            $timingBelt->update($data);
            return response()->json($timingBelt);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update timing belt',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified timing belt
     */
    public function destroy(TimingBelt $timingBelt)
    {
        try {
            $timingBelt->delete();
            return response()->json(['message' => 'Timing belt deleted successfully']);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete timing belt',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk import timing belts
     */
    public function bulkImport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'data' => 'required|array',
            'data.*.section' => 'required|string',
            'data.*.size' => 'required',
            'mode' => 'required|in:append,replace'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            if ($request->mode === 'replace') {
                TimingBelt::query()->delete();
            }

            $imported = 0;
            foreach ($request->data as $item) {
                $createData = [
                    'section' => $item['section'],
                    'size' => (string)$item['size'],
                    'type' => $item['type'] ?? '0', // Default to '0' if not provided
                    'total_mm' => $item['total_mm'] ?? 0,
                    'rate' => $item['rate'] ?? 0,
                    // Note: Removed 'value' to allow model auto-calculation
                    'reorder_level' => $item['reorder_level'] ?? null,
                    'remark' => $item['remark'] ?? null,
                    'created_by' => session('user')['id'] ?? null,
                ];

                // Create using model to trigger auto-calculation
                TimingBelt::create($createData);
                $imported++;
            }

            DB::commit();

            return response()->json([
                'message' => "Successfully imported {$imported} timing belts",
                'imported_count' => $imported
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Bulk import failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Perform IN/OUT operations for timing belts (supports both total_mm and full_sleeve operations)
     */
    public function inOutOperation(Request $request)
    {
        \Log::info('Timing Belt IN/OUT operation started', [
            'request_data' => $request->all(),
            'user' => session('user')
        ]);

        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'exists:timing_belts,id',
            'action' => 'required|in:IN,OUT',
            'unit_type' => 'required|in:total_mm,type', // Changed from full_sleeve to type
            'quantity' => 'required|numeric|min:0.01',
            'remark' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            \Log::error('Timing Belt IN/OUT validation failed', [
                'errors' => $validator->errors()
            ]);
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $results = [];
            foreach ($request->ids as $id) {
                $timingBelt = TimingBelt::findOrFail($id);
                
                $unitType = $request->unit_type;
                $change = $request->quantity;
                
                if ($unitType === 'total_mm') {
                    // Total MM operations
                    $oldStock = $timingBelt->total_mm;
                    
                    if ($request->action === 'IN') {
                        $timingBelt->total_mm += $change;
                        $timingBelt->in_mm += $change;
                    } else { // OUT
                        if ($timingBelt->total_mm < $change) {
                            throw new \Exception("Insufficient Total MM stock for {$timingBelt->section}-{$timingBelt->size}. Available: {$timingBelt->total_mm}mm, Requested: {$change}mm");
                        }
                        $timingBelt->total_mm -= $change;
                        $timingBelt->out_mm += $change;
                    }
                    
                    $results[] = [
                        'id' => $timingBelt->id,
                        'section' => $timingBelt->section,
                        'size' => $timingBelt->size,
                        'unit_type' => 'total_mm',
                        'old_stock' => $oldStock,
                        'new_stock' => $timingBelt->total_mm,
                        'change' => $change,
                    ];
                    
                } else { // type operations (Full Sleeve)
                    // Type operations - treating type as quantity of full sleeves
                    $oldTypeStock = (float) $timingBelt->type;
                    
                    if ($request->action === 'IN') {
                        $timingBelt->type = $oldTypeStock + $change;
                    } else { // OUT
                        if ($oldTypeStock < $change) {
                            throw new \Exception("Insufficient Type stock for {$timingBelt->section}-{$timingBelt->size}. Available: {$oldTypeStock}, Requested: {$change}");
                        }
                        $timingBelt->type = $oldTypeStock - $change;
                    }
                    
                    $results[] = [
                        'id' => $timingBelt->id,
                        'section' => $timingBelt->section,
                        'size' => $timingBelt->size,
                        'unit_type' => 'type',
                        'old_stock' => $oldTypeStock,
                        'new_stock' => (float) $timingBelt->type,
                        'change' => $change,
                    ];
                }
                
                $timingBelt->save();

                // Update stock alert tracking immediately when total_mm changes
                $tracking = \App\Models\StockAlertTracking::where('belt_type', 'timing')
                    ->where('product_id', $timingBelt->id)
                    ->first();
                
                if ($tracking) {
                    if ($timingBelt->reorder_level && $timingBelt->total_mm >= $timingBelt->reorder_level) {
                        $tracking->update([
                            'current_stock' => $timingBelt->total_mm,
                            'previous_stock' => $timingBelt->total_mm,
                            'dies_needed' => 0,
                            'alert_sent' => false,
                            'last_alerted_stock' => null,
                            'is_active' => true
                        ]);
                    } else if ($timingBelt->reorder_level && $timingBelt->total_mm < $timingBelt->reorder_level) {
                        $stockPerDie = \App\Models\DieConfiguration::getStockPerDie('timing', $timingBelt->section);
                        
                        $previousStock = $tracking->current_stock;
                        $newStock = $timingBelt->total_mm;
                        
                        if ($newStock > $previousStock) {
                            // Recalculate dies based on last alerted stock (incremental) or reorder level (first time)
                            if ($tracking->last_alerted_stock !== null) {
                                // Use incremental calculation from last alerted stock
                                $deficit = $tracking->last_alerted_stock - $newStock;
                                $diesNeeded = $deficit > 0 ? ceil($deficit / $stockPerDie) : 0;
                            } else {
                                // First time, use reorder level
                                $deficit = $timingBelt->reorder_level - $newStock;
                                $diesNeeded = ceil($deficit / $stockPerDie);
                            }
                            
                            $tracking->update([
                                'current_stock' => $newStock,
                                'previous_stock' => $newStock,
                                'dies_needed' => $diesNeeded,
                                'stock_per_die' => $stockPerDie
                            ]);
                        } else if ($newStock < $previousStock) {
                            if ($tracking->alert_sent && $tracking->last_alerted_stock !== null && $newStock < $tracking->last_alerted_stock) {
                                $deficit = $tracking->last_alerted_stock - $newStock;
                                $diesNeeded = ceil($deficit / $stockPerDie);
                                
                                $tracking->update([
                                    'current_stock' => $newStock,
                                    'previous_stock' => $previousStock,
                                    'dies_needed' => $diesNeeded,
                                    'stock_per_die' => $stockPerDie,
                                    'alert_sent' => false
                                ]);
                            } else if (!$tracking->alert_sent) {
                                $deficit = $timingBelt->reorder_level - $newStock;
                                $diesNeeded = ceil($deficit / $stockPerDie);
                                
                                $tracking->update([
                                    'current_stock' => $newStock,
                                    'previous_stock' => $previousStock,
                                    'dies_needed' => $diesNeeded,
                                    'stock_per_die' => $stockPerDie,
                                    'alert_sent' => false
                                ]);
                            } else {
                                $tracking->update([
                                    'current_stock' => $newStock,
                                    'previous_stock' => $previousStock,
                                    'stock_per_die' => $stockPerDie
                                ]);
                            }
                        }
                    }
                }

                // Create transaction record
                InventoryTransaction::create([
                    'category' => 'timing_belts',
                    'product_id' => $timingBelt->id,
                    'type' => $request->action,
                    'quantity' => $request->quantity,
                    'stock_before' => $unitType === 'total_mm' ? ($oldStock ?? 0) : ($oldTypeStock ?? 0),
                    'stock_after' => $unitType === 'total_mm' ? $timingBelt->total_mm : (float) $timingBelt->type,
                    'rate' => $unitType === 'total_mm' ? ($timingBelt->rate ?? 0) : ($timingBelt->rate_per_sleeve ?? 0),
                    'description' => "{$request->action} {$change}" . ($unitType === 'total_mm' ? 'mm' : ' full sleeves') . " ({$unitType})",
                    'user_id' => session('user')['id'] ?? null,
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => "Successfully processed {$request->action} operation for " . count($request->ids) . " timing belts",
                'results' => $results
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Timing Belt IN/OUT operation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'message' => 'Operation failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get transaction history for a timing belt
     */
    public function getTransactions(TimingBelt $timingBelt)
    {
        $transactions = InventoryTransaction::where('category', 'timing_belts')
                                          ->where('product_id', $timingBelt->id)
                                          ->with('user')
                                          ->orderBy('created_at', 'desc')
                                          ->get();

        return response()->json($transactions);
    }

    /**
     * Update global minimum inventory (reorder level) for all products
     */
    public function updateGlobalMinInventory(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'min_inventory' => 'required|numeric|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $updated = TimingBelt::query()->update([
                'reorder_level' => $request->min_inventory
            ]);

            DB::commit();

            return response()->json([
                'message' => "Updated minimum inventory level to {$request->min_inventory} for {$updated} timing belt products",
                'updated_count' => $updated
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to update global minimum inventory',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update rate for all products in a specific section
     */
    public function updateSectionRate(Request $request)
    {
        $request->validate([
            'section' => 'required|string',
            'rate' => 'required|numeric|min:0'
        ]);

        try {
            DB::beginTransaction();

            $updated = TimingBelt::where('section', $request->section)
                                 ->update(['rate' => $request->rate]);

            DB::commit();

            return response()->json([
                'message' => "Updated rate for {$updated} products in {$request->section} section",
                'updated_count' => $updated
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to update section rate',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Seed specific section from JSON file
     */
    public function seedSection(Request $request)
    {
        $request->validate([
            'section' => 'required|string',
            'filename' => 'required|string'
        ]);

        try {
            $jsonPath = resource_path("js/mock/{$request->filename}");
            
            if (!file_exists($jsonPath)) {
                return response()->json([
                    'message' => "JSON file not found: {$request->filename}"
                ], 404);
            }

            $jsonData = json_decode(file_get_contents($jsonPath), true);
            
            if (!$jsonData) {
                return response()->json([
                    'message' => 'Invalid JSON file format'
                ], 400);
            }

            DB::beginTransaction();

            $imported = 0;
            $skipped = 0;
            foreach ($jsonData as $item) {
                $section = $item['section'] ?? $request->section;
                $size = (string)$item['size'];
                $type = $item['type'] ?? '0';
                
                // Check if product already exists
                $existing = TimingBelt::where('section', $section)
                                     ->where('size', $size)
                                     ->where('type', $type)
                                     ->first();
                
                if ($existing) {
                    $skipped++;
                    continue;
                }
                
                // Create timing belt without explicit value to allow auto-calculation
                $timingBelt = TimingBelt::create([
                    'section' => $section,
                    'size' => $size,
                    'type' => $type,
                    'total_mm' => $item['total_mm'] ?? 0,
                    'rate' => $item['rate'] ?? 0,
                    // Note: Removed 'value' to allow model auto-calculation
                    'reorder_level' => $item['reorder_level'] ?? null,
                    'remark' => $item['remark'] ?? null,
                    'created_by' => session('user')['id'] ?? null,
                ]);
                
                // The value will be automatically calculated by the model's boot method
                $imported++;
            }

            DB::commit();

            $message = "Successfully seeded {$imported} products for {$request->section} section";
            if ($skipped > 0) {
                $message .= " ({$skipped} duplicates skipped)";
            }
            
            return response()->json([
                'message' => $message,
                'imported_count' => $imported,
                'skipped_count' => $skipped
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Seeding failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clear all products from a specific section
     */
    public function clearSection($section)
    {
        try {
            $deleted = TimingBelt::where('section', $section)->delete();

            return response()->json([
                'message' => "Cleared {$deleted} products from {$section} section",
                'deleted_count' => $deleted
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to clear section',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clear all timing belt data
     */
    public function clearAll()
    {
        try {
            $deleted = TimingBelt::query()->delete();

            return response()->json([
                'message' => "Cleared all timing belt data ({$deleted} products)",
                'deleted_count' => $deleted
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to clear all data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Recalculate rates for all timing belts based on current formulas
     */
    public function recalculateAllRates()
    {
        try {
            DB::beginTransaction();

            $timingBelts = TimingBelt::all();
            $updated = 0;

            foreach ($timingBelts as $timingBelt) {
                $timingBelt->calculateValue();
                $timingBelt->save();
                $updated++;
            }

            DB::commit();

            return response()->json([
                'message' => "Recalculated rates for {$updated} timing belt products",
                'updated_count' => $updated
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to recalculate rates',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Recalculate rates for specific section
     */
    public function recalculateSectionRates(Request $request)
    {
        $request->validate([
            'section' => 'required|string'
        ]);

        try {
            DB::beginTransaction();

            $timingBelts = TimingBelt::where('section', $request->section)->get();
            $updated = 0;

            foreach ($timingBelts as $timingBelt) {
                $timingBelt->calculateValue();
                $timingBelt->save();
                $updated++;
            }

            DB::commit();

            return response()->json([
                'message' => "Recalculated rates for {$updated} products in {$request->section} section",
                'updated_count' => $updated
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to recalculate section rates',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
