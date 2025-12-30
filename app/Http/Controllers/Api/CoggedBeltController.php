<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CoggedBelt;
use App\Models\InventoryTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CoggedBeltController extends Controller
{
    /**
     * Get all cogged belts or filter by section
     */
    public function index(Request $request)
    {
        $query = CoggedBelt::query();

        // Filter by section if provided
        if ($request->has('section')) {
            $query->bySection($request->section);
        }

        // Filter by low stock
        if ($request->boolean('low_stock')) {
            $query->lowStock();
        }

        // Filter by out of stock
        if ($request->boolean('out_of_stock')) {
            $query->outOfStock();
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('section', 'like', "%{$search}%")
                  ->orWhere('size', 'like', "%{$search}%");
            });
        }

        // Sort
        // Sort by size (width) in ascending order
        $query->orderByRaw('CAST(size AS UNSIGNED) ASC');

        // Paginate or get all
        if ($request->boolean('paginate', true)) {
            return $query->paginate($request->get('per_page', 50));
        }

        return $query->get();
    }

    /**
     * Get vee belts by specific section
     */
    public function bySection(string $section)
    {
        return CoggedBelt::bySection($section)
            ->orderByRaw('CAST(size AS UNSIGNED) ASC')
            ->get();
    }

    /**
     * Store a new vee belt
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'section' => 'required|string|max:10',
            'size' => 'required|string|max:20',
            'balance_stock' => 'required|integer|min:0',
            'reorder_level' => 'nullable|integer|min:0',
            'rate' => 'nullable|numeric|min:0',
            'remark' => 'nullable|string',
        ]);

        // Check if already exists
        $existing = CoggedBelt::where('section', $validated['section'])
            ->where('size', $validated['size'])
            ->first();

        if ($existing) {
            return response()->json([
                'message' => 'Product already exists',
                'product' => $existing
            ], 409);
        }

        DB::beginTransaction();
        try {
            // Calculate rate if not provided
            if (!isset($validated['rate'])) {
                $veeBelt = new CoggedBelt($validated);
                $validated['rate'] = $veeBelt->calculateRate();
            }

            // Calculate value
            $validated['value'] = $validated['balance_stock'] * $validated['rate'];

            $veeBelt = CoggedBelt::create($validated);

            // Create transaction record
            if ($validated['balance_stock'] > 0) {
                InventoryTransaction::create([
                    'category' => 'cogged_belts',
                    'product_id' => $veeBelt->id,
                    'type' => 'IN',
                    'quantity' => $validated['balance_stock'],
                    'stock_before' => 0,
                    'stock_after' => $validated['balance_stock'],
                    'rate' => $validated['rate'],
                    'description' => 'Initial stock',
                    'user_id' => session('user')['id'] ?? null,
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Product created successfully',
                'product' => $veeBelt->fresh()
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to create product',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a vee belt
     */
    public function update(Request $request, int $id)
    {
        $veeBelt = CoggedBelt::findOrFail($id);

        $validated = $request->validate([
            'section' => 'sometimes|string|max:10',
            'size' => 'sometimes|string|max:20',
            'balance_stock' => 'sometimes|integer|min:0',
            'reorder_level' => 'sometimes|integer|min:0',
            'rate' => 'sometimes|numeric|min:0',
            'remark' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $oldStock = $veeBelt->balance_stock;
            $oldRate = $veeBelt->rate;

            $veeBelt->update($validated);

            // Create transaction if stock changed
            if (isset($validated['balance_stock']) && $oldStock != $validated['balance_stock']) {
                $type = $validated['balance_stock'] > $oldStock ? 'IN' : 'OUT';
                $quantity = abs($validated['balance_stock'] - $oldStock);

                InventoryTransaction::create([
                    'category' => 'cogged_belts',
                    'product_id' => $veeBelt->id,
                    'type' => $type,
                    'quantity' => $quantity,
                    'stock_before' => $oldStock,
                    'stock_after' => $validated['balance_stock'],
                    'rate' => $veeBelt->rate,
                    'description' => "Stock updated from {$oldStock} to {$validated['balance_stock']}",
                    'user_id' => session('user')['id'] ?? null,
                ]);
            }

            // Create transaction if rate changed
            if (isset($validated['rate']) && $oldRate != $validated['rate']) {
                InventoryTransaction::create([
                    'category' => 'cogged_belts',
                    'product_id' => $veeBelt->id,
                    'type' => 'EDIT',
                    'stock_before' => $veeBelt->balance_stock,
                    'stock_after' => $veeBelt->balance_stock,
                    'rate' => $validated['rate'],
                    'description' => "Rate updated from ₹{$oldRate} to ₹{$validated['rate']}",
                    'user_id' => session('user')['id'] ?? null,
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Product updated successfully',
                'product' => $veeBelt->fresh()
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to update product',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a vee belt
     */
    public function destroy(int $id)
    {
        $veeBelt = CoggedBelt::findOrFail($id);
        $veeBelt->delete();

        return response()->json([
            'message' => 'Product deleted successfully'
        ]);
    }

    /**
     * Bulk import vee belts
     */
    public function bulkImport(Request $request)
    {
        // Pre-process products to convert size to string
        $products = $request->input('products', []);
        foreach ($products as &$product) {
            if (isset($product['size'])) {
                $product['size'] = (string) $product['size'];
            }
            // Map balanceStock to balance_stock if present
            if (isset($product['balanceStock'])) {
                $product['balance_stock'] = $product['balanceStock'];
                unset($product['balanceStock']);
            }
        }
        
        $request->merge(['products' => $products]);
        
        $validated = $request->validate([
            'products' => 'required|array',
            'products.*.section' => 'required|string|max:10',
            'products.*.size' => 'required|string|max:20',
            'products.*.balance_stock' => 'required|integer|min:0',
            'products.*.reorder_level' => 'nullable|integer|min:0',
            'products.*.rate' => 'nullable|numeric|min:0',
            'products.*.remark' => 'nullable|string',
            'mode' => 'required|in:append,replace',
        ]);

        DB::beginTransaction();
        try {
            $addedCount = 0;
            $updatedCount = 0;
            $skippedCount = 0;

            foreach ($validated['products'] as $productData) {
                // Check if exists
                $existing = CoggedBelt::where('section', $productData['section'])
                    ->where('size', $productData['size'])
                    ->first();

                if ($existing) {
                    // Update existing
                    $oldStock = $existing->balance_stock;
                    $existing->update($productData);

                    // Create transaction
                    if ($oldStock != $productData['balance_stock']) {
                        InventoryTransaction::create([
                            'category' => 'cogged_belts',
                            'product_id' => $existing->id,
                            'type' => $productData['balance_stock'] > $oldStock ? 'IN' : 'OUT',
                            'quantity' => abs($productData['balance_stock'] - $oldStock),
                            'stock_before' => $oldStock,
                            'stock_after' => $productData['balance_stock'],
                            'rate' => $existing->rate,
                            'description' => 'Bulk import update',
                            'user_id' => session('user')['id'] ?? null,
                        ]);
                    }

                    $updatedCount++;
                } else {
                    // Create new
                    if (!isset($productData['rate'])) {
                        $temp = new CoggedBelt($productData);
                        $productData['rate'] = $temp->calculateRate();
                    }

                    $productData['value'] = $productData['balance_stock'] * $productData['rate'];
                    $veeBelt = CoggedBelt::create($productData);

                    if ($productData['balance_stock'] > 0) {
                        InventoryTransaction::create([
                            'category' => 'cogged_belts',
                            'product_id' => $veeBelt->id,
                            'type' => 'IN',
                            'quantity' => $productData['balance_stock'],
                            'stock_before' => 0,
                            'stock_after' => $productData['balance_stock'],
                            'rate' => $productData['rate'],
                            'description' => 'Bulk import',
                            'user_id' => session('user')['id'] ?? null,
                        ]);
                    }

                    $addedCount++;
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Bulk import completed',
                'added' => $addedCount,
                'updated' => $updatedCount,
                'skipped' => $skippedCount,
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
     * Get transaction history for a product
     */
    public function transactions(int $id)
    {
        $veeBelt = CoggedBelt::findOrFail($id);

        $transactions = InventoryTransaction::forProduct('cogged_belts', $id)
            ->with('user:id,name')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'product' => $veeBelt,
            'transactions' => $transactions
        ]);
    }

    /**
     * IN/OUT operations
     */
    public function inOut(Request $request)
    {
        $validated = $request->validate([
            'product_ids' => 'required|array',
            'product_ids.*' => 'required|integer|exists:cogged_belts,id',
            'type' => 'required|in:IN,OUT',
            'quantity' => 'required|integer|min:1',
        ]);

        DB::beginTransaction();
        try {
            $results = [];

            foreach ($validated['product_ids'] as $productId) {
                $veeBelt = CoggedBelt::findOrFail($productId);
                $oldStock = $veeBelt->balance_stock;

                if ($validated['type'] === 'IN') {
                    $veeBelt->balance_stock += $validated['quantity'];
                    $veeBelt->in_stock += $validated['quantity'];
                } else {
                    if ($veeBelt->balance_stock < $validated['quantity']) {
                        $results[] = [
                            'product_id' => $productId,
                            'success' => false,
                            'message' => 'Insufficient stock'
                        ];
                        continue;
                    }
                    $veeBelt->balance_stock -= $validated['quantity'];
                    $veeBelt->out_stock += $validated['quantity'];
                }

                $veeBelt->save();

                // Create transaction
                InventoryTransaction::create([
                    'category' => 'cogged_belts',
                    'product_id' => $veeBelt->id,
                    'type' => $validated['type'],
                    'quantity' => $validated['quantity'],
                    'stock_before' => $oldStock,
                    'stock_after' => $veeBelt->balance_stock,
                    'rate' => $veeBelt->rate,
                    'description' => "{$validated['type']} operation: {$validated['quantity']} units",
                    'user_id' => session('user')['id'] ?? null,
                ]);

                $results[] = [
                    'product_id' => $productId,
                    'success' => true,
                    'new_stock' => $veeBelt->balance_stock,
                    'in_stock' => $veeBelt->in_stock,
                    'out_stock' => $veeBelt->out_stock,
                ];
            }

            DB::commit();

            return response()->json([
                'message' => 'Operation completed',
                'results' => $results
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Operation failed',
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

            $updated = \App\Models\CoggedBelt::where('section', $request->section)
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
                $rawSection = $item['section'] ?? $item['name'] ?? $request->section;
                
                // Clean section name - extract just the section code (e.g., "5VX (Megaflex)" -> "5VX")
                $section = trim(explode('(', $rawSection)[0]);
                $size = $item['size'];
                
                // Check if product already exists
                $existing = \App\Models\CoggedBelt::where('section', $section)
                                                 ->where('size', $size)
                                                 ->first();
                
                if ($existing) {
                    $skipped++;
                    continue;
                }
                
                \App\Models\CoggedBelt::create([
                    'section' => $section,
                    'size' => $size,
                    'balance_stock' => $item['balance_stock'] ?? $item['stock'] ?? 0,
                    'rate' => $item['rate'],
                    'remark' => $item['remark'] ?? null,
                ]);
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
            $deleted = \App\Models\CoggedBelt::where('section', $section)->delete();

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
     * Clear all cogged belt data
     */
    public function clearAll()
    {
        try {
            $deleted = \App\Models\CoggedBelt::query()->delete();

            return response()->json([
                'message' => "Cleared all cogged belt data ({$deleted} products)",
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
        $request->validate([
            'section' => 'required|string'
        ]);

        try {
            DB::beginTransaction();

            // Get the rate formula for this section
            $formula = \App\Models\RateFormula::where('category', 'cogged_belts')
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
            $divisor = 1;
            
            // Handle both string and array formats for backward compatibility
            if (is_array($formulaData)) {
                // Old array format
                if ($formulaData['type'] === 'divide_multiply') {
                    $divisor = (float) ($formulaData['divisor'] ?? 10);
                    $multiplier = (float) ($formulaData['multiplier'] ?? 1);
                } else {
                    $multiplier = (float) ($formulaData['multiplier'] ?? 1);
                }
            } else {
                // New string format
                $formulaStr = $formulaData;
                if (preg_match('/size\/([0-9.]+)\*([0-9.]+)/', $formulaStr, $matches)) {
                    // Format: "size/10*2.15" -> divisor=10, multiplier=2.15
                    $divisor = (float) $matches[1];
                    $multiplier = (float) $matches[2];
                } elseif (preg_match('/size\*([0-9.]+)/', $formulaStr, $matches)) {
                    // Format: "size*1.95" -> multiplier=1.95
                    $multiplier = (float) $matches[1];
                } else {
                    return response()->json([
                        'message' => "Invalid formula format for {$request->section} section"
                    ], 400);
                }
            }

            // Update all products in this section
            $products = \App\Models\CoggedBelt::where('section', $request->section)->get();
            $updated = 0;

            foreach ($products as $product) {
                $newRate = ((float) $product->size / $divisor) * $multiplier;
                $product->update([
                    'rate' => $newRate,
                    'value' => $product->balance_stock * $newRate
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
     * Recalculate all rates based on current formulas
     */
    public function recalculateAllRates()
    {
        try {
            DB::beginTransaction();

            // Get all active formulas for cogged belts
            $formulas = \App\Models\RateFormula::where('category', 'cogged_belts')
                                              ->where('is_active', true)
                                              ->get()
                                              ->keyBy('section');

            $totalUpdated = 0;
            $sections = ['AX', 'BX', 'CX', 'XPA', 'XPB', 'XPC', 'XPZ', '3VX', '5VX'];

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
                    if ($formulaData['type'] === 'divide_multiply') {
                        $divisor = (float) ($formulaData['divisor'] ?? 10);
                        $multiplier = (float) ($formulaData['multiplier'] ?? 1);
                    } else {
                        $multiplier = (float) ($formulaData['multiplier'] ?? 1);
                    }
                } else {
                    // New string format
                    $formulaStr = $formulaData;
                    if (preg_match('/size\/([0-9.]+)\*([0-9.]+)/', $formulaStr, $matches)) {
                        // Format: "size/10*2.15" -> divisor=10, multiplier=2.15
                        $divisor = (float) $matches[1];
                        $multiplier = (float) $matches[2];
                    } elseif (preg_match('/size\*([0-9.]+)/', $formulaStr, $matches)) {
                        // Format: "size*1.95" -> multiplier=1.95
                        $multiplier = (float) $matches[1];
                    } else {
                        continue; // Skip invalid formulas
                    }
                }

                // Update all products in this section
                $products = \App\Models\CoggedBelt::where('section', $section)->get();

                foreach ($products as $product) {
                    $newRate = ((float) $product->size / $divisor) * $multiplier;
                    $product->update([
                        'rate' => $newRate,
                        'value' => $product->balance_stock * $newRate
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

    /**
     * Update global minimum inventory (reorder level) for all products
     */
    public function updateGlobalMinInventory(Request $request)
    {
        $validator = \Validator::make($request->all(), [
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

            $updated = \App\Models\CoggedBelt::query()->update([
                'reorder_level' => $request->min_inventory
            ]);

            DB::commit();

            return response()->json([
                'message' => "Updated minimum inventory level to {$request->min_inventory} for {$updated} Cogged belt products",
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
}
