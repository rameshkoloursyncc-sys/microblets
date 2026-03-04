<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PolyBelt;
use App\Models\InventoryTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PolyBeltController extends Controller
{
    /**
     * Get all poly belts or filter by section
     */
    public function index(Request $request)
    {
        $query = PolyBelt::query();

        $query->with('stockAlert');


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
                  ->orWhere('size', 'like', "%{$search}%")
                  ->orWhere('ribs', 'like', "%{$search}%");
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
     * Get poly belts by specific section
     */
    public function bySection(string $section)
    {
        return PolyBelt::bySection($section)
            ->with('stockAlert')
            ->orderByRaw('CAST(size AS UNSIGNED) ASC')
            ->orderBy('ribs')
            ->get();
    }

    /**
     * Store a new poly belt
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'section' => 'required|string|max:10',
            'size' => 'required|numeric|min:0',
            'ribs' => 'required|integer|min:0',
            'reorder_level' => 'nullable|integer|min:0',
            'rate_per_rib' => 'nullable|numeric|min:0',
            'remark' => 'nullable|string',
        ]);

        // Check if already exists
        $existing = PolyBelt::where('section', $validated['section'])
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
            $polyBelt = PolyBelt::create($validated);

            // Create transaction record
            if ($validated['ribs'] > 0) {
                InventoryTransaction::create([
                    'category' => 'poly_belts',
                    'product_id' => $polyBelt->id,
                    'type' => 'IN',
                    'quantity' => $validated['ribs'],
                    'stock_before' => 0,
                    'stock_after' => $validated['ribs'],
                    'rate' => $polyBelt->rate_per_rib,
                    'description' => 'Initial ribs',
                    'user_id' => session('user')['id'] ?? null,
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Product created successfully',
                'product' => $polyBelt->fresh()
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
     * Update a poly belt
     */
    public function update(Request $request, int $id)
    {
        $polyBelt = PolyBelt::findOrFail($id);

        $validated = $request->validate([
            'section' => 'sometimes|string|max:10',
            'size' => 'sometimes|numeric|min:0',
            'ribs' => 'sometimes|integer|min:0',
            'reorder_level' => 'sometimes|integer|min:0',
            'rate_per_rib' => 'sometimes|numeric|min:0',
            'remark' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $oldRibs = $polyBelt->ribs;
            $oldRatePerRib = $polyBelt->rate_per_rib;

            $polyBelt->update($validated);

            // Create transaction if ribs changed
            if (isset($validated['ribs']) && $oldRibs != $validated['ribs']) {
                $type = $validated['ribs'] > $oldRibs ? 'IN' : 'OUT';
                $quantity = abs($validated['ribs'] - $oldRibs);

                InventoryTransaction::create([
                    'category' => 'poly_belts',
                    'product_id' => $polyBelt->id,
                    'type' => $type,
                    'quantity' => $quantity,
                    'stock_before' => $oldRibs,
                    'stock_after' => $validated['ribs'],
                    'rate' => $polyBelt->rate_per_rib,
                    'description' => "Ribs updated from {$oldRibs} to {$validated['ribs']}",
                    'user_id' => session('user')['id'] ?? null,
                ]);

                // Update stock alert tracking immediately when ribs change
                $tracking = \App\Models\StockAlertTracking::where('belt_type', 'poly')
                    ->where('product_id', $polyBelt->id)
                    ->first();
                
                if (!$tracking && $polyBelt->reorder_level && $validated['ribs'] < $polyBelt->reorder_level) {
                    $stockPerDie = \App\Models\DieConfiguration::getStockPerDie('poly', $polyBelt->section);
                    $deficit = $polyBelt->reorder_level - $validated['ribs'];
                    $diesNeeded = ceil($deficit / $stockPerDie);
                    
                    $tracking = \App\Models\StockAlertTracking::create([
                        'belt_type' => 'poly',
                        'section' => $polyBelt->section,
                        'product_id' => $polyBelt->id,
                        'product_sku' => $polyBelt->section . '-' . $polyBelt->size,
                        'current_stock' => $validated['ribs'],
                        'reorder_level' => $polyBelt->reorder_level,
                        'stock_per_die' => $stockPerDie,
                        'dies_needed' => $diesNeeded,
                        'alert_sent' => false,
                        'is_active' => true,
                        'previous_stock' => $validated['ribs'],
                        'last_alerted_stock' => null
                    ]);
                }
                
                if ($tracking) {
                    if ($polyBelt->reorder_level && $validated['ribs'] >= $polyBelt->reorder_level) {
                        $tracking->update([
                            'current_stock' => $validated['ribs'],
                            'previous_stock' => $validated['ribs'],
                            'dies_needed' => 0,
                            'alert_sent' => false,
                            'last_alerted_stock' => null,
                            'is_active' => true
                        ]);
                    } else if ($polyBelt->reorder_level && $validated['ribs'] < $polyBelt->reorder_level) {
                        $stockPerDie = \App\Models\DieConfiguration::getStockPerDie('poly', $polyBelt->section);
                        
                        $previousStock = $tracking->current_stock;
                        $newStock = $validated['ribs'];
                        
                        if ($newStock > $previousStock) {
                            // Recalculate dies based on new stock level
                            $deficit = $polyBelt->reorder_level - $newStock;
                            $diesNeeded = ceil($deficit / $stockPerDie);
                            
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
                                $deficit = $polyBelt->reorder_level - $newStock;
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
            }

            // Create transaction if rate_per_rib changed
            if (isset($validated['rate_per_rib']) && $oldRatePerRib != $validated['rate_per_rib']) {
                InventoryTransaction::create([
                    'category' => 'poly_belts',
                    'product_id' => $polyBelt->id,
                    'type' => 'EDIT',
                    'stock_before' => $polyBelt->ribs,
                    'stock_after' => $polyBelt->ribs,
                    'rate' => $polyBelt->rate_per_rib,
                    'description' => "Rate per rib updated from ₹{$oldRatePerRib} to ₹{$validated['rate_per_rib']}",
                    'user_id' => session('user')['id'] ?? null,
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Product updated successfully',
                'product' => $polyBelt->fresh()
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
     * Delete a poly belt
     */
    public function destroy(int $id)
    {
        $polyBelt = PolyBelt::findOrFail($id);
        $polyBelt->delete();

        return response()->json([
            'message' => 'Product deleted successfully'
        ]);
    }

    /**
     * Bulk import poly belts
     */
    public function bulkImport(Request $request)
    {
        // Pre-process products to convert fields to correct types
        $products = $request->input('products', []);
        foreach ($products as &$product) {
            if (isset($product['size'])) {
                $product['size'] = (string) $product['size'];
            }
            if (isset($product['ribs'])) {
                $product['ribs'] = (int) $product['ribs'];
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
            'products.*.size' => 'required|numeric|min:0',
            'products.*.ribs' => 'required|integer|min:0',
            'products.*.reorder_level' => 'nullable|integer|min:0',
            'products.*.rate_per_rib' => 'nullable|numeric|min:0',
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
                $existing = PolyBelt::where('section', $productData['section'])
                    ->where('size', $productData['size'])
                    ->first();

                if ($existing) {
                    // Update existing
                    $oldRibs = $existing->ribs;
                    $existing->update($productData);

                    // Create transaction
                    if ($oldRibs != $productData['ribs']) {
                        InventoryTransaction::create([
                            'category' => 'poly_belts',
                            'product_id' => $existing->id,
                            'type' => $productData['ribs'] > $oldRibs ? 'IN' : 'OUT',
                            'quantity' => abs($productData['ribs'] - $oldRibs),
                            'stock_before' => $oldRibs,
                            'stock_after' => $productData['ribs'],
                            'rate' => $existing->rate_per_rib,
                            'description' => 'Bulk import update',
                            'user_id' => session('user')['id'] ?? null,
                        ]);
                    }

                    $updatedCount++;
                } else {
                    // Create new
                    $polyBelt = PolyBelt::create($productData);

                    if ($productData['ribs'] > 0) {
                        InventoryTransaction::create([
                            'category' => 'poly_belts',
                            'product_id' => $polyBelt->id,
                            'type' => 'IN',
                            'quantity' => $productData['ribs'],
                            'stock_before' => 0,
                            'stock_after' => $productData['ribs'],
                            'rate' => $polyBelt->rate_per_rib,
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
        $polyBelt = PolyBelt::findOrFail($id);

        $transactions = InventoryTransaction::forProduct('poly_belts', $id)
            ->with('user:id,name')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'product' => $polyBelt,
            'transactions' => $transactions
        ]);
    }

    /**
     * IN/OUT operations
     */
    public function inOut(Request $request)
    {
        // Debug session data
        \Log::info('IN/OUT Operation Debug', [
            'session_id' => session()->getId(),
            'session_user' => session('user'),
            'request_data' => $request->all()
        ]);

        $validated = $request->validate([
            'product_ids' => 'required|array',
            'product_ids.*' => 'required|integer|exists:poly_belts,id',
            'type' => 'required|in:IN,OUT',
            'quantity' => 'required|integer|min:1',
        ]);

        DB::beginTransaction();
        try {
            $results = [];

            foreach ($validated['product_ids'] as $productId) {
                $polyBelt = PolyBelt::findOrFail($productId);
                $oldRibs = $polyBelt->ribs;

                if ($validated['type'] === 'IN') {
                    $polyBelt->ribs += $validated['quantity'];
                    $polyBelt->in_ribs += $validated['quantity'];
                } else {
                    if ($polyBelt->ribs < $validated['quantity']) {
                        $results[] = [
                            'product_id' => $productId,
                            'success' => false,
                            'message' => 'Insufficient ribs'
                        ];
                        continue;
                    }
                    $polyBelt->ribs -= $validated['quantity'];
                    $polyBelt->out_ribs += $validated['quantity'];
                }

                $polyBelt->save();

                // Update stock alert tracking immediately
                $tracking = \App\Models\StockAlertTracking::where('belt_type', 'poly')
                    ->where('product_id', $polyBelt->id)
                    ->first();
                
                if (!$tracking && $polyBelt->reorder_level && $polyBelt->ribs < $polyBelt->reorder_level) {
                    $stockPerDie = \App\Models\DieConfiguration::getStockPerDie('poly', $polyBelt->section);
                    $deficit = $polyBelt->reorder_level - $polyBelt->ribs;
                    $diesNeeded = ceil($deficit / $stockPerDie);
                    
                    $tracking = \App\Models\StockAlertTracking::create([
                        'belt_type' => 'poly',
                        'section' => $polyBelt->section,
                        'product_id' => $polyBelt->id,
                        'product_sku' => $polyBelt->section . '-' . $polyBelt->size,
                        'current_stock' => $polyBelt->ribs,
                        'reorder_level' => $polyBelt->reorder_level,
                        'stock_per_die' => $stockPerDie,
                        'dies_needed' => $diesNeeded,
                        'alert_sent' => false,
                        'is_active' => true,
                        'previous_stock' => $polyBelt->ribs,
                        'last_alerted_stock' => null
                    ]);
                }
                
                if ($tracking) {
                    if ($polyBelt->reorder_level && $polyBelt->ribs >= $polyBelt->reorder_level) {
                        $tracking->update([
                            'current_stock' => $polyBelt->ribs,
                            'previous_stock' => $polyBelt->ribs,
                            'dies_needed' => 0,
                            'alert_sent' => false,
                            'last_alerted_stock' => null,
                            'is_active' => true
                        ]);
                    } else if ($polyBelt->reorder_level && $polyBelt->ribs < $polyBelt->reorder_level) {
                        $stockPerDie = \App\Models\DieConfiguration::getStockPerDie('poly', $polyBelt->section);
                        $previousStock = $tracking->current_stock;
                        $newStock = $polyBelt->ribs;
                        
                        if ($newStock > $previousStock) {
                            // Recalculate dies based on new stock level
                            $deficit = $polyBelt->reorder_level - $newStock;
                            $diesNeeded = ceil($deficit / $stockPerDie);
                            
                            $tracking->update(['current_stock' => $newStock, 'previous_stock' => $newStock, 'dies_needed' => $diesNeeded, 'stock_per_die' => $stockPerDie]);
                        } else if ($newStock < $previousStock) {
                            if ($tracking->alert_sent && $tracking->last_alerted_stock !== null && $newStock < $tracking->last_alerted_stock) {
                                $deficit = $tracking->last_alerted_stock - $newStock;
                                $diesNeeded = ceil($deficit / $stockPerDie);
                                $tracking->update(['current_stock' => $newStock, 'previous_stock' => $previousStock, 'dies_needed' => $diesNeeded, 'stock_per_die' => $stockPerDie, 'alert_sent' => false]);
                            } else if (!$tracking->alert_sent) {
                                $deficit = $polyBelt->reorder_level - $newStock;
                                $diesNeeded = ceil($deficit / $stockPerDie);
                                $tracking->update(['current_stock' => $newStock, 'previous_stock' => $previousStock, 'dies_needed' => $diesNeeded, 'stock_per_die' => $stockPerDie, 'alert_sent' => false]);
                            } else {
                                $tracking->update(['current_stock' => $newStock, 'previous_stock' => $previousStock, 'stock_per_die' => $stockPerDie]);
                            }
                        }
                    }
                }
                
                // Create transaction
                InventoryTransaction::create([
                    'category' => 'poly_belts',
                    'product_id' => $polyBelt->id,
                    'type' => $validated['type'],
                    'quantity' => $validated['quantity'],
                    'stock_before' => $oldRibs,
                    'stock_after' => $polyBelt->ribs,
                    'rate' => $polyBelt->rate_per_rib,
                    'description' => "{$validated['type']} operation: {$validated['quantity']} ribs",
                    'user_id' => session('user')['id'] ?? null,
                ]);

                $results[] = [
                    'product_id' => $productId,
                    'success' => true,
                    'new_ribs' => $polyBelt->ribs,
                    'in_ribs' => $polyBelt->in_ribs,
                    'out_ribs' => $polyBelt->out_ribs,
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

            $updated = \App\Models\PolyBelt::where('section', $request->section)
                             ->update(['rate_per_rib' => $request->rate]);

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
                
                // Clean section name - extract just the section code (e.g., "PK (Special)" -> "PK")
                $section = trim(explode('(', $rawSection)[0]);
                $size = $item['size'];
                
                // Check if product already exists
                $existing = \App\Models\PolyBelt::where('section', $section)
                                               ->where('size', $size)
                                               ->first();
                
                if ($existing) {
                    $skipped++;
                    continue;
                }
                
                \App\Models\PolyBelt::create([
                    'section' => $section,
                    'size' => $size,
                    'ribs' => $item['ribs'] ?? $item['stock'] ?? 0,
                    'rate_per_rib' => $item['rate_per_rib'] ?? $item['rate'] ?? 0,
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
            $deleted = \App\Models\PolyBelt::where('section', $section)->delete();

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
     * Clear all poly belt data
     */
    public function clearAll()
    {
        try {
            $deleted = \App\Models\PolyBelt::query()->delete();

            return response()->json([
                'message' => "Cleared all poly belt data ({$deleted} products)",
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
            $formula = \App\Models\RateFormula::where('category', 'poly_belts')
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
            $divisor = 25.4; // Default for poly belts
            
            // Handle both string and array formats for backward compatibility
            if (is_array($formulaData)) {
                // Old array format - poly belts don't have old array format, but handle just in case
                $multiplier = (float) ($formulaData['multiplier'] ?? 1);
                $divisor = (float) ($formulaData['divisor'] ?? 25.4);
            } else {
                // New string format: "size/25.4*0.59" or "size/30*0.59"
                $formulaStr = $formulaData;
                if (preg_match('/size\/([0-9.]+)\*([0-9.]+)/', $formulaStr, $matches)) {
                    $divisor = (float) $matches[1];
                    $multiplier = (float) $matches[2];
                } elseif (preg_match('/ribs\/([0-9.]+)\*([0-9.]+)/', $formulaStr, $matches)) {
                    // Backward compatibility with old format
                    $divisor = (float) $matches[1];
                    $multiplier = (float) $matches[2];
                } else {
                    return response()->json([
                        'message' => "Invalid formula format for {$request->section} section"
                    ], 400);
                }
            }

            // Update all products in this section
            $products = \App\Models\PolyBelt::where('section', $request->section)->get();
            $updated = 0;

            foreach ($products as $product) {
                $newRatePerRib = ($product->size / $divisor) * $multiplier;
                $product->update([
                    'rate_per_rib' => $newRatePerRib
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

            // Get all active formulas for poly belts
            $formulas = \App\Models\RateFormula::where('category', 'poly_belts')
                                              ->where('is_active', true)
                                              ->get()
                                              ->keyBy('section');

            $totalUpdated = 0;
            $sections = ['PJ', 'PK', 'PL', 'PM', 'PH', 'DPL', 'DPK'];

            foreach ($sections as $section) {
                if (!isset($formulas[$section])) {
                    continue; // Skip if no formula found
                }

                $formula = $formulas[$section];
                $formulaStr = $formula->formula;

                // Parse the formula to get the multiplier and divisor
                $formulaData = $formula->formula;
                $multiplier = 0;
                $divisor = 25.4;
                
                // Handle both string and array formats for backward compatibility
                if (is_array($formulaData)) {
                    // Old array format
                    $multiplier = (float) ($formulaData['multiplier'] ?? 1);
                    $divisor = (float) ($formulaData['divisor'] ?? 25.4);
                } else {
                    // New string format
                    $formulaStr = $formulaData;
                    if (preg_match('/size\/([0-9.]+)\*([0-9.]+)/', $formulaStr, $matches)) {
                        $divisor = (float) $matches[1];
                        $multiplier = (float) $matches[2];
                    } elseif (preg_match('/ribs\/([0-9.]+)\*([0-9.]+)/', $formulaStr, $matches)) {
                        // Backward compatibility
                        $divisor = (float) $matches[1];
                        $multiplier = (float) $matches[2];
                    } else {
                        continue; // Skip invalid formulas
                    }
                }

                // Update all products in this section
                $products = \App\Models\PolyBelt::where('section', $section)->get();

                foreach ($products as $product) {
                    $newRatePerRib = ($product->size / $divisor) * $multiplier;
                    $product->update([
                        'rate_per_rib' => $newRatePerRib
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

            $updated = \App\Models\PolyBelt::query()->update([
                'reorder_level' => $request->min_inventory
            ]);

            DB::commit();

            return response()->json([
                'message' => "Updated minimum inventory level to {$request->min_inventory} for {$updated} Poly belt products",
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
     * Test rate calculation for debugging
     */
    public function testRateCalculation(Request $request, $id)
    {
        $polyBelt = PolyBelt::findOrFail($id);
        
        $formula = RateFormula::where('category', 'poly_belts')
            ->where('section', $polyBelt->section)
            ->first();
            
        $calculatedRate = $polyBelt->calculateRatePerRib();
        
        return response()->json([
            'product' => [
                'id' => $polyBelt->id,
                'section' => $polyBelt->section,
                'size' => $polyBelt->size,
                'ribs' => $polyBelt->ribs,
                'current_rate_per_rib' => $polyBelt->rate_per_rib,
                'current_value' => $polyBelt->value,
            ],
            'formula' => $formula ? $formula->formula : 'No formula found',
            'calculated_rate' => $calculatedRate,
            'calculated_value' => $polyBelt->ribs * $calculatedRate,
            'formula_breakdown' => $formula ? [
                'formula_string' => $formula->formula,
                'size' => $polyBelt->size,
                'calculation' => "({$polyBelt->size} ÷ divisor) × multiplier = {$calculatedRate}"
            ] : null
        ]);
    }
}