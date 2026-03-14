<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VeeBelt;
use App\Models\InventoryTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class VeeBeltController extends Controller
{
    /**
     * Get all vee belts or filter by section
     */
    public function index(Request $request)
    {
        $query = VeeBelt::query();

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
                  ->orWhere('size', 'like', "%{$search}%");
            });
        }

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
        return VeeBelt::bySection($section)
            ->with('stockAlert')
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
        $existing = VeeBelt::where('section', $validated['section'])
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
                $veeBelt = new VeeBelt($validated);
                $validated['rate'] = $veeBelt->calculateRate();
            }

            // Calculate value
            $validated['value'] = $validated['balance_stock'] * $validated['rate'];

            $veeBelt = VeeBelt::create($validated);

            // Create transaction record
            if ($validated['balance_stock'] > 0) {
                InventoryTransaction::create([
                    'category' => 'vee_belts',
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
        $veeBelt = VeeBelt::findOrFail($id);

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
                    'category' => 'vee_belts',
                    'product_id' => $veeBelt->id,
                    'type' => $type,
                    'quantity' => $quantity,
                    'stock_before' => $oldStock,
                    'stock_after' => $validated['balance_stock'],
                    'rate' => $veeBelt->rate,
                    'description' => "Stock updated from {$oldStock} to {$validated['balance_stock']}",
                    'user_id' => session('user')['id'] ?? null,
                ]);

                // Update stock alert tracking immediately when stock changes
                $tracking = \App\Models\StockAlertTracking::where('belt_type', 'vee')
                    ->where('product_id', $veeBelt->id)
                    ->first();
                
                \Log::info("=== VEE BELT STOCK UPDATE ===", [
                    'product_id' => $veeBelt->id,
                    'section' => $veeBelt->section,
                    'size' => $veeBelt->size,
                    'old_stock' => $oldStock,
                    'new_stock' => $validated['balance_stock'],
                    'reorder_level' => $veeBelt->reorder_level,
                    'type' => $type,
                    'tracking_exists' => $tracking ? 'YES' : 'NO'
                ]);
                
                // Create tracking if doesn't exist and stock is below reorder level
                if (!$tracking && $veeBelt->reorder_level && $validated['balance_stock'] < $veeBelt->reorder_level) {
                    \Log::info("CREATING NEW TRACKING RECORD");
                    
                    $stockPerDie = \App\Models\DieConfiguration::getStockPerDie('vee', $veeBelt->section);
                    $deficit = $veeBelt->reorder_level - $validated['balance_stock'];
                    $diesNeeded = ceil($deficit / $stockPerDie);
                    
                    $tracking = \App\Models\StockAlertTracking::create([
                        'belt_type' => 'vee',
                        'section' => $veeBelt->section,
                        'product_id' => $veeBelt->id,
                        'product_sku' => $veeBelt->section . '-' . $veeBelt->size,
                        'current_stock' => $validated['balance_stock'],
                        'reorder_level' => $veeBelt->reorder_level,
                        'stock_per_die' => $stockPerDie,
                        'dies_needed' => $diesNeeded,
                        'alert_sent' => false,
                        'is_active' => true,
                        'previous_stock' => $validated['balance_stock'],
                        'last_alerted_stock' => null
                    ]);
                    
                    \Log::info("NEW TRACKING CREATED", [
                        'current_stock' => $validated['balance_stock'],
                        'dies_needed' => $diesNeeded,
                        'deficit' => $deficit
                    ]);
                }
                
                if ($tracking) {
                    \Log::info("TRACKING BEFORE UPDATE", [
                        'current_stock' => $tracking->current_stock,
                        'previous_stock' => $tracking->previous_stock,
                        'last_alerted_stock' => $tracking->last_alerted_stock,
                        'dies_needed' => $tracking->dies_needed,
                        'alert_sent' => $tracking->alert_sent ? 'YES' : 'NO'
                    ]);
                    
                    // Stock went above reorder level - reset tracking
                    if ($veeBelt->reorder_level && $validated['balance_stock'] >= $veeBelt->reorder_level) {
                        \Log::info("CASE: Stock above reorder level - RESET", [
                            'balance_stock' => $validated['balance_stock'],
                            'reorder_level' => $veeBelt->reorder_level,
                            'comparison' => $validated['balance_stock'] . ' >= ' . $veeBelt->reorder_level
                        ]);
                        
                        $tracking->update([
                            'current_stock' => $validated['balance_stock'],
                            'previous_stock' => $validated['balance_stock'],
                            'dies_needed' => 0,
                            'alert_sent' => false,
                            'last_alerted_stock' => null,
                            'is_active' => true
                        ]);
                        
                        \Log::info("TRACKING AFTER RESET", [
                            'current_stock' => $validated['balance_stock'],
                            'previous_stock' => $validated['balance_stock'],
                            'dies_needed' => 0,
                            'alert_sent' => 'NO',
                            'last_alerted_stock' => 'null'
                        ]);
                    }
                    // Stock still below reorder level - update current and previous stock
                    else if ($veeBelt->reorder_level && $validated['balance_stock'] < $veeBelt->reorder_level) {
                        \Log::info("CASE: Stock below reorder level", [
                            'balance_stock' => $validated['balance_stock'],
                            'reorder_level' => $veeBelt->reorder_level,
                            'comparison' => $validated['balance_stock'] . ' < ' . $veeBelt->reorder_level
                        ]);
                        $stockPerDie = \App\Models\DieConfiguration::getStockPerDie('vee', $veeBelt->section);
                        
                        $previousStock = $tracking->current_stock;
                        $newStock = $validated['balance_stock'];
                        
                        \Log::info("CASE: Stock below reorder level", [
                            'previous_stock' => $previousStock,
                            'new_stock' => $newStock,
                            'stock_per_die' => $stockPerDie
                        ]);
                        
                        if ($newStock > $previousStock) {
                            \Log::info("SUB-CASE: Stock IMPROVED (IN transaction)");
                            
                            // Recalculate dies based on last alerted stock (incremental) or reorder level (first time)
                            if ($tracking->last_alerted_stock !== null) {
                                // Use incremental calculation from last alerted stock
                                $deficit = $tracking->last_alerted_stock - $newStock;
                                $diesNeeded = $deficit > 0 ? ceil($deficit / $stockPerDie) : 0;
                            } else {
                                // First time, use reorder level
                                $deficit = $veeBelt->reorder_level - $newStock;
                                $diesNeeded = ceil($deficit / $stockPerDie);
                            }
                            
                            $tracking->update([
                                'current_stock' => $newStock,
                                'previous_stock' => $newStock,
                                'dies_needed' => $diesNeeded,
                                'stock_per_die' => $stockPerDie
                            ]);
                            
                            \Log::info("TRACKING AFTER IN", [
                                'current_stock' => $newStock,
                                'previous_stock' => $newStock,
                                'last_alerted_stock' => $tracking->last_alerted_stock,
                                'deficit' => $deficit,
                                'dies_needed' => $diesNeeded,
                                'calculation' => $tracking->last_alerted_stock !== null ? 'incremental' : 'from_reorder_level',
                                'alert_sent' => $tracking->alert_sent ? 'YES' : 'NO'
                            ]);
                        } else if ($newStock < $previousStock) {
                            \Log::info("SUB-CASE: Stock DROPPED (OUT transaction)");
                            
                            if ($tracking->alert_sent && $tracking->last_alerted_stock !== null && $newStock < $tracking->last_alerted_stock) {
                                \Log::info("ALERT LOGIC: Incremental alert needed", [
                                    'alert_sent' => 'YES',
                                    'last_alerted_stock' => $tracking->last_alerted_stock,
                                    'new_stock' => $newStock,
                                    'condition' => 'newStock < last_alerted_stock'
                                ]);
                                
                                $deficit = $tracking->last_alerted_stock - $newStock;
                                $diesNeeded = ceil($deficit / $stockPerDie);
                                
                                \Log::info("CALCULATION", [
                                    'deficit' => $deficit,
                                    'formula' => "last_alerted_stock ({$tracking->last_alerted_stock}) - new_stock ({$newStock})",
                                    'dies_needed' => $diesNeeded,
                                    'formula_dies' => "ceil({$deficit} / {$stockPerDie})"
                                ]);
                                
                                $tracking->update([
                                    'current_stock' => $newStock,
                                    'previous_stock' => $previousStock,
                                    'dies_needed' => $diesNeeded,
                                    'stock_per_die' => $stockPerDie,
                                    'alert_sent' => false
                                ]);
                                
                                \Log::info("TRACKING AFTER INCREMENTAL ALERT", [
                                    'current_stock' => $newStock,
                                    'previous_stock' => $previousStock,
                                    'dies_needed' => $diesNeeded,
                                    'alert_sent' => 'NO (new alert needed)'
                                ]);
                            } else if (!$tracking->alert_sent) {
                                \Log::info("ALERT LOGIC: First time alert", [
                                    'alert_sent' => 'NO',
                                    'condition' => 'First time below min'
                                ]);
                                
                                $deficit = $veeBelt->reorder_level - $newStock;
                                $diesNeeded = ceil($deficit / $stockPerDie);
                                
                                \Log::info("CALCULATION", [
                                    'deficit' => $deficit,
                                    'formula' => "reorder_level ({$veeBelt->reorder_level}) - new_stock ({$newStock})",
                                    'dies_needed' => $diesNeeded,
                                    'formula_dies' => "ceil({$deficit} / {$stockPerDie})"
                                ]);
                                
                                $tracking->update([
                                    'current_stock' => $newStock,
                                    'previous_stock' => $previousStock,
                                    'dies_needed' => $diesNeeded,
                                    'stock_per_die' => $stockPerDie,
                                    'alert_sent' => false
                                ]);
                                
                                \Log::info("TRACKING AFTER FIRST ALERT", [
                                    'current_stock' => $newStock,
                                    'previous_stock' => $previousStock,
                                    'dies_needed' => $diesNeeded,
                                    'alert_sent' => 'NO'
                                ]);
                            } else {
                                \Log::info("ALERT LOGIC: Stock dropped but no new alert", [
                                    'alert_sent' => 'YES',
                                    'last_alerted_stock' => $tracking->last_alerted_stock,
                                    'new_stock' => $newStock,
                                    'condition' => 'newStock >= last_alerted_stock (no new alert)'
                                ]);
                                
                                $tracking->update([
                                    'current_stock' => $newStock,
                                    'previous_stock' => $previousStock,
                                    'stock_per_die' => $stockPerDie
                                ]);
                                
                                \Log::info("TRACKING AFTER UPDATE (no alert change)", [
                                    'current_stock' => $newStock,
                                    'previous_stock' => $previousStock,
                                    'dies_needed' => $tracking->dies_needed,
                                    'alert_sent' => 'YES (unchanged)'
                                ]);
                            }
                        }
                    }
                } else {
                    \Log::warning("NO TRACKING RECORD FOUND - Will be created by sync");
                }
            }

            // Create transaction if rate changed
            if (isset($validated['rate']) && $oldRate != $validated['rate']) {
                InventoryTransaction::create([
                    'category' => 'vee_belts',
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

            // Get updated tracking data for response
            $trackingData = null;
            if (isset($validated['balance_stock'])) {
                $trackingRecord = \App\Models\StockAlertTracking::where('belt_type', 'vee')
                    ->where('product_id', $veeBelt->id)
                    ->first();
                
                if ($trackingRecord) {
                    $trackingData = [
                        'current_stock' => $trackingRecord->current_stock,
                        'previous_stock' => $trackingRecord->previous_stock,
                        'last_alerted_stock' => $trackingRecord->last_alerted_stock,
                        'dies_needed' => $trackingRecord->dies_needed,
                        'alert_sent' => $trackingRecord->alert_sent,
                        'reorder_level' => $trackingRecord->reorder_level,
                        'stock_per_die' => $trackingRecord->stock_per_die
                    ];
                }
            }

            return response()->json([
                'message' => 'Product updated successfully',
                'product' => $veeBelt->fresh(),
                'tracking' => $trackingData
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("VEE BELT UPDATE FAILED", [
                'product_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
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
        $veeBelt = VeeBelt::findOrFail($id);
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
                $existing = VeeBelt::where('section', $productData['section'])
                    ->where('size', $productData['size'])
                    ->first();

                if ($existing) {
                    // Update existing
                    $oldStock = $existing->balance_stock;
                    $existing->update($productData);

                    // Create transaction
                    if ($oldStock != $productData['balance_stock']) {
                        InventoryTransaction::create([
                            'category' => 'vee_belts',
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
                        $temp = new VeeBelt($productData);
                        $productData['rate'] = $temp->calculateRate();
                    }

                    $productData['value'] = $productData['balance_stock'] * $productData['rate'];
                    $veeBelt = VeeBelt::create($productData);

                    if ($productData['balance_stock'] > 0) {
                        InventoryTransaction::create([
                            'category' => 'vee_belts',
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
        $veeBelt = VeeBelt::findOrFail($id);

        $transactions = InventoryTransaction::forProduct('vee_belts', $id)
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
            'product_ids.*' => 'required|integer|exists:vee_belts,id',
            'type' => 'required|in:IN,OUT',
            'quantity' => 'required|integer|min:1',
        ]);

        DB::beginTransaction();
        try {
            $results = [];

            foreach ($validated['product_ids'] as $productId) {
                $veeBelt = VeeBelt::findOrFail($productId);
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

                // Update stock alert tracking immediately
                $tracking = \App\Models\StockAlertTracking::where('belt_type', 'vee')
                    ->where('product_id', $veeBelt->id)
                    ->first();
                
                \Log::info("=== VEE BELT IN/OUT ===", [
                    'product_id' => $veeBelt->id,
                    'section' => $veeBelt->section,
                    'size' => $veeBelt->size,
                    'old_stock' => $oldStock,
                    'new_stock' => $veeBelt->balance_stock,
                    'reorder_level' => $veeBelt->reorder_level,
                    'type' => $validated['type'],
                    'quantity' => $validated['quantity'],
                    'tracking_exists' => $tracking ? 'YES' : 'NO'
                ]);
                
                // Create tracking if doesn't exist and stock is below reorder level
                if (!$tracking && $veeBelt->reorder_level && $veeBelt->balance_stock < $veeBelt->reorder_level) {
                    \Log::info("CREATING NEW TRACKING RECORD");
                    
                    $stockPerDie = \App\Models\DieConfiguration::getStockPerDie('vee', $veeBelt->section);
                    $deficit = $veeBelt->reorder_level - $veeBelt->balance_stock;
                    $diesNeeded = ceil($deficit / $stockPerDie);
                    
                    $tracking = \App\Models\StockAlertTracking::create([
                        'belt_type' => 'vee',
                        'section' => $veeBelt->section,
                        'product_id' => $veeBelt->id,
                        'product_sku' => $veeBelt->section . '-' . $veeBelt->size,
                        'current_stock' => $veeBelt->balance_stock,
                        'reorder_level' => $veeBelt->reorder_level,
                        'stock_per_die' => $stockPerDie,
                        'dies_needed' => $diesNeeded,
                        'alert_sent' => false,
                        'is_active' => true,
                        'previous_stock' => $veeBelt->balance_stock,
                        'last_alerted_stock' => null
                    ]);
                    
                    \Log::info("NEW TRACKING CREATED", [
                        'current_stock' => $veeBelt->balance_stock,
                        'dies_needed' => $diesNeeded
                    ]);
                }
                
                if ($tracking) {
                    \Log::info("TRACKING BEFORE UPDATE", [
                        'current_stock' => $tracking->current_stock,
                        'previous_stock' => $tracking->previous_stock,
                        'last_alerted_stock' => $tracking->last_alerted_stock,
                        'dies_needed' => $tracking->dies_needed,
                        'alert_sent' => $tracking->alert_sent ? 'YES' : 'NO'
                    ]);
                    
                    // Stock went above reorder level - reset tracking
                    if ($veeBelt->reorder_level && $veeBelt->balance_stock >= $veeBelt->reorder_level) {
                        \Log::info("CASE: Stock above reorder level - RESET");
                        
                        $tracking->update([
                            'current_stock' => $veeBelt->balance_stock,
                            'previous_stock' => $veeBelt->balance_stock,
                            'dies_needed' => 0,
                            'alert_sent' => false,
                            'last_alerted_stock' => null,
                            'is_active' => true
                        ]);
                    }
                    // Stock still below reorder level
                    else if ($veeBelt->reorder_level && $veeBelt->balance_stock < $veeBelt->reorder_level) {
                        $stockPerDie = \App\Models\DieConfiguration::getStockPerDie('vee', $veeBelt->section);
                        
                        $previousStock = $tracking->current_stock;
                        $newStock = $veeBelt->balance_stock;
                        
                        \Log::info("CASE: Stock below reorder level");
                        
                        if ($newStock > $previousStock) {
                            \Log::info("SUB-CASE: Stock IMPROVED (IN)");
                            
                            // Recalculate dies based on last alerted stock (incremental) or reorder level (first time)
                            if ($tracking->last_alerted_stock !== null) {
                                // Use incremental calculation from last alerted stock
                                $deficit = $tracking->last_alerted_stock - $newStock;
                                $diesNeeded = $deficit > 0 ? ceil($deficit / $stockPerDie) : 0;
                            } else {
                                // First time, use reorder level
                                $deficit = $veeBelt->reorder_level - $newStock;
                                $diesNeeded = ceil($deficit / $stockPerDie);
                            }
                            
                            $tracking->update([
                                'current_stock' => $newStock,
                                'previous_stock' => $newStock,
                                'dies_needed' => $diesNeeded,
                                'stock_per_die' => $stockPerDie
                            ]);
                        } else if ($newStock < $previousStock) {
                            \Log::info("SUB-CASE: Stock DROPPED (OUT)");
                            
                            if ($tracking->alert_sent && $tracking->last_alerted_stock !== null && $newStock < $tracking->last_alerted_stock) {
                                \Log::info("ALERT LOGIC: Incremental alert needed");
                                
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
                                \Log::info("ALERT LOGIC: First time alert");
                                
                                $deficit = $veeBelt->reorder_level - $newStock;
                                $diesNeeded = ceil($deficit / $stockPerDie);
                                
                                $tracking->update([
                                    'current_stock' => $newStock,
                                    'previous_stock' => $previousStock,
                                    'dies_needed' => $diesNeeded,
                                    'stock_per_die' => $stockPerDie,
                                    'alert_sent' => false
                                ]);
                            } else {
                                \Log::info("ALERT LOGIC: Stock dropped but no new alert");
                                
                                $tracking->update([
                                    'current_stock' => $newStock,
                                    'previous_stock' => $previousStock,
                                    'stock_per_die' => $stockPerDie
                                ]);
                            }
                        }
                    }
                }
                

                // Create transaction
                InventoryTransaction::create([
                    'category' => 'vee_belts',
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

            $updated = VeeBelt::where('section', $request->section)
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
                
                // Clean section name - extract just the section code (e.g., "SPA (Special)" -> "SPA")
                $section = trim(explode('(', $rawSection)[0]);
                $size = $item['size'];
                
                // Check if product already exists
                $existing = VeeBelt::where('section', $section)
                                  ->where('size', $size)
                                  ->first();
                
                if ($existing) {
                    $skipped++;
                    continue;
                }
                
                VeeBelt::create([
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
            $deleted = VeeBelt::where('section', $section)->delete();

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
     * Clear all vee belt data
     */
    public function clearAll()
    {
        try {
            $deleted = VeeBelt::query()->delete();

            return response()->json([
                'message' => "Cleared all vee belt data ({$deleted} products)",
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
            $formula = \App\Models\RateFormula::where('category', 'vee_belts')
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
                // Old array format: {"type": "multiply", "multiplier": 1.05} or {"type": "divide_multiply", "divisor": 10, "multiplier": 1.50}
                if ($formulaData['type'] === 'divide_multiply') {
                    $divisor = (float) ($formulaData['divisor'] ?? 10);
                    $multiplier = (float) ($formulaData['multiplier'] ?? 1);
                } else {
                    $multiplier = (float) ($formulaData['multiplier'] ?? 1);
                }
            } else {
                // New string format: "size/10*1.50" or "size*1.05"
                $formulaStr = $formulaData;
                if (preg_match('/size\/([0-9.]+)\*([0-9.]+)/', $formulaStr, $matches)) {
                    // Format: "size/10*1.50" -> divisor=10, multiplier=1.50
                    $divisor = (float) $matches[1];
                    $multiplier = (float) $matches[2];
                } elseif (preg_match('/size\*([0-9.]+)/', $formulaStr, $matches)) {
                    // Format: "size*1.05" -> multiplier=1.05
                    $multiplier = (float) $matches[1];
                } else {
                    return response()->json([
                        'message' => "Invalid formula format for {$request->section} section"
                    ], 400);
                }
            }

            // Update all products in this section
            $products = VeeBelt::where('section', $request->section)->get();
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
                'multiplier_used' => $multiplier,
                'divisor_used' => $divisor
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

            // Get all active formulas for vee belts
            $formulas = \App\Models\RateFormula::where('category', 'vee_belts')
                                              ->where('is_active', true)
                                              ->get()
                                              ->keyBy('section');

            $totalUpdated = 0;
            $sections = ['A', 'B', 'C', 'D', 'E', 'SPA', 'SPB', 'SPC', 'SPZ', '3V', '5V', '8V'];

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
                        $divisor = (float) $matches[1];
                        $multiplier = (float) $matches[2];
                    } elseif (preg_match('/size\*([0-9.]+)/', $formulaStr, $matches)) {
                        $multiplier = (float) $matches[1];
                    } else {
                        continue; // Skip invalid formulas
                    }
                }

                // Update all products in this section
                $products = VeeBelt::where('section', $section)->get();

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

            $updated = VeeBelt::query()->update([
                'reorder_level' => $request->min_inventory
            ]);

            DB::commit();

            return response()->json([
                'message' => "Updated minimum inventory level to {$request->min_inventory} for {$updated} Vee belt products",
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
