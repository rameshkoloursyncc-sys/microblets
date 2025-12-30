<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TpuBelt;
use App\Models\InventoryTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TpuBeltController extends Controller
{
    /**
     * Display a listing of TPU belts
     */
    public function index(Request $request)
    {
        $query = TpuBelt::query();

        // Filter by section if provided
        if ($request->has('section')) {
            $query->bySection($request->section);
        }

        // Search functionality
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('section', 'like', "%{$search}%")
                  ->orWhere('width', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('remark', 'like', "%{$search}%");
            });
        }

        // Date range filter
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $tpuBelts = $query->orderByRaw('CAST(width AS UNSIGNED) ASC')
                         ->get();

        return response()->json($tpuBelts);
    }

    /**
     * Get TPU belts by specific section
     */
    public function getBySection($section)
    {
        $tpuBelts = TpuBelt::bySection($section)
                          ->orderByRaw('CAST(width AS UNSIGNED) ASC')
                          ->get();

        return response()->json($tpuBelts);
    }

    /**
     * Store a newly created TPU belt
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'section' => 'required|string|max:50',
            'width' => 'required|string|max:50',
            'meter' => 'required|numeric|min:0',
            'rate' => 'required|numeric|min:0',
            'remark' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check for duplicates
        $existing = TpuBelt::where('section', $request->section)
                          ->where('width', $request->width)
                          ->first();

        if ($existing) {
            return response()->json([
                'message' => 'TPU belt with this section and width already exists'
            ], 409);
        }

        try {
            $tpuBelt = TpuBelt::create($request->all());
            return response()->json($tpuBelt, 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create TPU belt',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified TPU belt
     */
    public function show(TpuBelt $tpuBelt)
    {
        return response()->json($tpuBelt);
    }

    /**
     * Update the specified TPU belt
     */
    public function update(Request $request, TpuBelt $tpuBelt)
    {
        $validator = Validator::make($request->all(), [
            'section' => 'sometimes|required|string|max:50',
            'width' => 'sometimes|required|string|max:50',
            'meter' => 'sometimes|required|numeric|min:0',
            'rate' => 'sometimes|required|numeric|min:0',
            'remark' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $tpuBelt->update($request->all());
            return response()->json($tpuBelt);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update TPU belt',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified TPU belt
     */
    public function destroy(TpuBelt $tpuBelt)
    {
        try {
            $tpuBelt->delete();
            return response()->json(['message' => 'TPU belt deleted successfully']);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete TPU belt',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk import TPU belts
     */
    public function bulkImport(Request $request)
    {
        // Custom validation to handle both 'meter' and 'meters' fields
        $validator = Validator::make($request->all(), [
            'data' => 'required|array',
            'data.*.section' => 'required|string',
            'data.*.width' => 'required',
            'data.*.rate' => 'required|numeric|min:0',
            'mode' => 'required|in:append,replace'
        ]);

        // Additional validation for meter/meters field
        foreach ($request->data as $index => $item) {
            if (!isset($item['meter']) && !isset($item['meters'])) {
                $validator->after(function ($validator) use ($index) {
                    $validator->errors()->add("data.{$index}.meter", 'Either meter or meters field is required');
                });
            }
        }

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            if ($request->mode === 'replace') {
                TpuBelt::query()->delete();
            }

            $imported = 0;
            foreach ($request->data as $item) {
                // No duplicate check - allow multiple identical products (each has unique ID/SKU)

                // Handle both 'meter' and 'meters' field names
                $createData = [
                    'section' => $item['section'],
                    'width' => (string)$item['width'],
                    'meter' => $item['meters'] ?? $item['meter'],
                    'rate' => $item['rate'],
                    'remark' => $item['remark'] ?? null,
                ];

                TpuBelt::create($createData);
                $imported++;
            }

            DB::commit();

            return response()->json([
                'message' => "Successfully imported {$imported} TPU belts",
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
     * Perform IN/OUT operations for TPU belts
     */
    public function inOutOperation(Request $request)
    {
        \Log::info('TPU IN/OUT operation started', [
            'request_data' => $request->all(),
            'user' => session('user')
        ]);

        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'exists:tpu_belts,id',
            'action' => 'required|in:IN,OUT',
            'quantity' => 'required|numeric|min:0.01',
            'unit_type' => 'required|in:width,meter',
            'remark' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            \Log::error('TPU IN/OUT validation failed', [
                'errors' => $validator->errors()
            ]);
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            \Log::info('TPU IN/OUT transaction started');
            DB::beginTransaction();

            $results = [];
            foreach ($request->ids as $id) {
                \Log::info('Processing TPU belt', ['id' => $id]);
                $tpuBelt = TpuBelt::findOrFail($id);
                $oldMeter = $tpuBelt->meter;
                $oldWidth = $tpuBelt->width;

                // Calculate change based on unit type
                if ($request->unit_type === 'meter') {
                    // Change meter field
                    $meterChange = $request->quantity;
                    
                    if ($request->action === 'IN') {
                        $tpuBelt->meter += $meterChange;
                        $tpuBelt->in_meter += $meterChange;
                    } else { // OUT
                        if ($tpuBelt->meter < $meterChange) {
                            throw new \Exception("Insufficient meter for {$tpuBelt->section}-{$tpuBelt->width}. Available: {$tpuBelt->meter}, Requested: {$meterChange}");
                        }
                        $tpuBelt->meter -= $meterChange;
                        $tpuBelt->out_meter += $meterChange;
                    }
                    
                    $changeDescription = "{$request->action} {$meterChange} meter";
                    
                } else { // width
                    // Change width field
                    $widthChange = $request->quantity;
                    
                    if ($request->action === 'IN') {
                        $tpuBelt->width += $widthChange;
                    } else { // OUT
                        if ($tpuBelt->width < $widthChange) {
                            throw new \Exception("Insufficient width for {$tpuBelt->section}-{$tpuBelt->width}. Available: {$tpuBelt->width}, Requested: {$widthChange}");
                        }
                        $tpuBelt->width -= $widthChange;
                    }
                    
                    $meterChange = 0; // No meter change for width operations
                    $changeDescription = "{$request->action} {$widthChange} width (from {$oldWidth} to {$tpuBelt->width})";
                }
                $tpuBelt->save();

                // Create transaction record
                InventoryTransaction::create([
                    'category' => 'tpu_belts',
                    'product_id' => $tpuBelt->id,
                    'type' => $request->action,
                    'quantity' => $request->quantity,
                    'stock_before' => $request->unit_type === 'meter' ? $oldMeter : $oldWidth,
                    'stock_after' => $request->unit_type === 'meter' ? $tpuBelt->meter : $tpuBelt->width,
                    'rate' => $tpuBelt->rate,
                    'description' => $changeDescription,
                    'user_id' => session('user')['id'] ?? null,
                ]);

                $results[] = [
                    'id' => $tpuBelt->id,
                    'section' => $tpuBelt->section,
                    'width' => $tpuBelt->width,
                    'meter' => $tpuBelt->meter,
                    'change' => $request->quantity,
                    'unit_type' => $request->unit_type,
                    'field_changed' => $request->unit_type === 'meter' ? 'meter' : 'width'
                ];
            }

            DB::commit();
            \Log::info('TPU IN/OUT operation completed successfully', [
                'results_count' => count($results)
            ]);

            return response()->json([
                'message' => "Successfully processed {$request->action} operation for " . count($request->ids) . " TPU belts",
                'results' => $results
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('TPU IN/OUT operation failed', [
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
     * Get transaction history for a TPU belt
     */
    public function getTransactions(TpuBelt $tpuBelt)
    {
        $transactions = InventoryTransaction::where('category', 'tpu_belts')
                                          ->where('product_id', $tpuBelt->id)
                                          ->with('user')
                                          ->orderBy('created_at', 'desc')
                                          ->get();

        return response()->json($transactions);
    }

    /**
     * Update rate for all products in a specific section
     */
    public function updateSectionRate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'section' => 'required|string',
            'rate' => 'required|numeric|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $updated = TpuBelt::where('section', $request->section)
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
        $validator = Validator::make($request->all(), [
            'section' => 'required|string',
            'filename' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

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
            foreach ($jsonData as $item) {
                // Handle both 'meter' and 'meters' field names
                $createData = [
                    'section' => $item['section'],
                    'width' => (string)$item['width'],
                    'meter' => $item['meters'] ?? $item['meter'],
                    'rate' => $item['rate'],
                    'remark' => $item['remark'] ?? null,
                ];

                TpuBelt::create($createData);
                $imported++;
            }

            DB::commit();

            return response()->json([
                'message' => "Successfully seeded {$imported} products for {$request->section} section",
                'imported_count' => $imported
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
            $deleted = TpuBelt::where('section', $section)->delete();

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
     * Clear all TPU belt data
     */
    public function clearAll()
    {
        try {
            $deleted = TpuBelt::query()->delete();

            return response()->json([
                'message' => "Cleared all TPU belt data ({$deleted} products)",
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
     * Recalculate rates for specific section based on current formula
     */
    public function recalculateSectionRates(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'section' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Get the rate formula for this section
            $formula = \App\Models\RateFormula::where('category', 'tpu_belts')
                                             ->where('section', $request->section)
                                             ->where('is_active', true)
                                             ->first();

            if (!$formula) {
                return response()->json([
                    'message' => "No active formula found for {$request->section} section"
                ], 404);
            }

            // Parse the formula to get the multiplier and divisor
            $formulaData = $formula->formula;
            $multiplier = 0;
            $divisor = 1; // Default for TPU belts
            
            // Handle both string and array formats for backward compatibility
            if (is_array($formulaData)) {
                // Old array format - TPU belts don't have old array format, but handle just in case
                $multiplier = (float) ($formulaData['multiplier'] ?? 1);
                $divisor = (float) ($formulaData['divisor'] ?? 1);
            } else {
                // New string format: "size/1*2.50" or "size*2.50"
                $formulaStr = $formulaData;
                if (preg_match('/size\/([0-9.]+)\*([0-9.]+)/', $formulaStr, $matches)) {
                    // Format: "size/1*2.50" -> divisor=1, multiplier=2.50
                    $divisor = (float) $matches[1];
                    $multiplier = (float) $matches[2];
                } elseif (preg_match('/size\*([0-9.]+)/', $formulaStr, $matches)) {
                    // Format: "size*2.50" -> multiplier=2.50
                    $multiplier = (float) $matches[1];
                } else {
                    return response()->json([
                        'message' => "Invalid formula format for {$request->section} section"
                    ], 400);
                }
            }

            // Update all products in this section
            // For TPU belts, we use width as the size multiplier
            $products = TpuBelt::where('section', $request->section)->get();
            $updated = 0;

            foreach ($products as $product) {
                $newRate = (float) $product->width * $multiplier;
                $product->update([
                    'rate' => $newRate
                ]);
                $updated++;
            }

            DB::commit();

            return response()->json([
                'message' => "Recalculated rates for {$updated} products in {$request->section} section",
                'updated_count' => $updated,
                'multiplier_used' => $multiplier
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to recalculate section rates',
                'error' => $e->getMessage()
            ], 500);
        }
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

            $updated = TpuBelt::query()->update([
                'reorder_level' => $request->min_inventory
            ]);

            DB::commit();

            return response()->json([
                'message' => "Updated minimum inventory level to {$request->min_inventory} for {$updated} TPU belt products",
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
     * Recalculate all rates based on current formulas
     */
    public function recalculateAllRates()
    {
        try {
            DB::beginTransaction();

            // Get all active formulas for TPU belts
            $formulas = \App\Models\RateFormula::where('category', 'tpu_belts')
                                              ->where('is_active', true)
                                              ->get()
                                              ->keyBy('section');

            $totalUpdated = 0;
            $sections = ['5M', '8M', '8M RPP', 'S8M', '14M', 'XL', 'L', 'H', 'AT5', 'AT10', 'T10', 'AT20'];

            foreach ($sections as $section) {
                if (!isset($formulas[$section])) {
                    continue; // Skip if no formula found
                }

                $formula = $formulas[$section];
                $formulaStr = $formula->formula;

                // Parse the formula to get the multiplier and divisor
                $formulaData = $formula->formula;
                $multiplier = 0;
                $divisor = 1;
                
                // Handle both string and array formats for backward compatibility
                if (is_array($formulaData)) {
                    // Old array format
                    $multiplier = (float) ($formulaData['multiplier'] ?? 1);
                    $divisor = (float) ($formulaData['divisor'] ?? 1);
                } else {
                    // New string format
                    $formulaStr = $formulaData;
                    if (preg_match('/size\/([0-9.]+)\*([0-9.]+)/', $formulaStr, $matches)) {
                        $divisor = (float) $matches[1];
                        $multiplier = (float) $matches[2];
                    } elseif (preg_match('/size\*([0-9.]+)/', $formulaStr, $matches)) {
                        $multiplier = (float) $matches[1];
                    } else {
                        continue; // Skip invalid formulas
                    }
                }

                // Update all products in this section
                $products = TpuBelt::where('section', $section)->get();

                foreach ($products as $product) {
                    $newRate = (float) $product->width * $multiplier;
                    $product->update([
                        'rate' => $newRate
                    ]);
                    $totalUpdated++;
                }
            }

            DB::commit();

            return response()->json([
                'message' => "Recalculated rates for {$totalUpdated} products across all sections",
                'updated_count' => $totalUpdated
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to recalculate all rates',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}