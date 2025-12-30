<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SpecialBelt;
use App\Models\InventoryTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SpecialBeltController extends Controller
{
    /**
     * Display a listing of special belts
     */
    public function index(Request $request)
    {
        $query = SpecialBelt::query();

        // Filter by section if provided
        if ($request->has('section')) {
            $query->bySection($request->section);
        }

        // Filter by type if provided
        if ($request->has('type')) {
            $query->byType($request->type);
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

        $specialBelts = $query->orderByRaw('CAST(size AS UNSIGNED) ASC')
                             ->get();

        return response()->json($specialBelts);
    }

    /**
     * Get special belts by specific section
     */
    public function getBySection($section)
    {
        $specialBelts = SpecialBelt::bySection($section)
                                  ->orderByRaw('CAST(size AS UNSIGNED) ASC')
                                  ->get();

        return response()->json($specialBelts);
    }

    /**
     * Store a newly created special belt
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'section' => 'required|string|max:20',
            'size' => 'required|string|max:20',
            'type' => 'required|string|max:30',
            'balance_stock' => 'required|integer|min:0',
            'rate' => 'required|numeric|min:0',
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
            $data['created_by'] = session('user')['id'] ?? null;
            
            $specialBelt = SpecialBelt::create($data);
            return response()->json($specialBelt, 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create special belt',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified special belt
     */
    public function show(SpecialBelt $specialBelt)
    {
        return response()->json($specialBelt);
    }

    /**
     * Update the specified special belt
     */
    public function update(Request $request, SpecialBelt $specialBelt)
    {
        $validator = Validator::make($request->all(), [
            'section' => 'sometimes|required|string|max:20',
            'size' => 'sometimes|required|string|max:20',
            'type' => 'sometimes|required|string|max:30',
            'balance_stock' => 'sometimes|required|integer|min:0',
            'rate' => 'sometimes|required|numeric|min:0',
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
            
            $specialBelt->update($data);
            return response()->json($specialBelt);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update special belt',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified special belt
     */
    public function destroy(SpecialBelt $specialBelt)
    {
        try {
            $specialBelt->delete();
            return response()->json(['message' => 'Special belt deleted successfully']);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete special belt',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk import special belts
     */
    public function bulkImport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'data' => 'required|array',
            'data.*.section' => 'required|string',
            'data.*.size' => 'required',
            'data.*.type' => 'required|string',
            'data.*.balance_stock' => 'required|integer|min:0',
            'data.*.rate' => 'required|numeric|min:0',
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
                SpecialBelt::query()->delete();
            }

            $imported = 0;
            foreach ($request->data as $item) {
                $createData = [
                    'section' => $item['section'],
                    'size' => (string)$item['size'],
                    'type' => $item['type'],
                    'balance_stock' => $item['balance_stock'],
                    'rate' => $item['rate'],
                    'reorder_level' => $item['reorder_level'] ?? 5,
                    'remark' => $item['remark'] ?? null,
                    'created_by' => session('user')['id'] ?? null,
                ];

                SpecialBelt::create($createData);
                $imported++;
            }

            DB::commit();

            return response()->json([
                'message' => "Successfully imported {$imported} special belts",
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
     * Perform IN/OUT operations for special belts
     */
    public function inOutOperation(Request $request)
    {
        \Log::info('Special Belt IN/OUT operation started', [
            'request_data' => $request->all(),
            'user' => session('user')
        ]);

        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'exists:special_belts,id',
            'action' => 'required|in:IN,OUT',
            'quantity' => 'required|integer|min:1',
            'remark' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            \Log::error('Special Belt IN/OUT validation failed', [
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
                $specialBelt = SpecialBelt::findOrFail($id);
                $oldStock = $specialBelt->balance_stock;
                $change = (int)$request->quantity;
                
                if ($request->action === 'IN') {
                    $specialBelt->balance_stock += $change;
                    $specialBelt->in_stock += $change;
                } else { // OUT
                    if ($specialBelt->balance_stock < $change) {
                        throw new \Exception("Insufficient stock for {$specialBelt->section}-{$specialBelt->size}. Available: {$specialBelt->balance_stock}, Requested: {$change}");
                    }
                    $specialBelt->balance_stock -= $change;
                    $specialBelt->out_stock += $change;
                }
                
                $specialBelt->save();

                // Create transaction record
                InventoryTransaction::create([
                    'category' => 'special_belts',
                    'product_id' => $specialBelt->id,
                    'type' => $request->action,
                    'quantity' => $request->quantity,
                    'stock_before' => $oldStock,
                    'stock_after' => $specialBelt->balance_stock,
                    'rate' => $specialBelt->rate,
                    'description' => "{$request->action} {$change} pieces",
                    'user_id' => session('user')['id'] ?? null,
                ]);

                $results[] = [
                    'id' => $specialBelt->id,
                    'section' => $specialBelt->section,
                    'size' => $specialBelt->size,
                    'type' => $specialBelt->type,
                    'old_stock' => $oldStock,
                    'new_stock' => $specialBelt->balance_stock,
                    'change' => $change,
                ];
            }

            DB::commit();

            return response()->json([
                'message' => "Successfully processed {$request->action} operation for " . count($request->ids) . " special belts",
                'results' => $results
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Special Belt IN/OUT operation failed', [
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
     * Get transaction history for a special belt
     */
    public function getTransactions(SpecialBelt $specialBelt)
    {
        $transactions = InventoryTransaction::where('category', 'special_belts')
                                          ->where('product_id', $specialBelt->id)
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

            $updated = SpecialBelt::query()->update([
                'reorder_level' => $request->min_inventory
            ]);

            DB::commit();

            return response()->json([
                'message' => "Updated minimum inventory level to {$request->min_inventory} for {$updated} special belt products",
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