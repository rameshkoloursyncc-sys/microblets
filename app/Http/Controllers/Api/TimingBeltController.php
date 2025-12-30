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
            'section' => 'required|string|max:10',
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
            'section' => 'sometimes|required|string|max:10',
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
                    'type' => $item['type'] ?? '1 (FULL SLEEVE)',
                    'mm' => $item['mm'] ?? 0,
                    'total_mm' => $item['total_mm'] ?? $item['mm'] ?? 0,
                    'rate' => $item['rate'] ?? 0,
                    'reorder_level' => $item['reorder_level'] ?? 5,
                    'remark' => $item['remark'] ?? null,
                    'created_by' => session('user')['id'] ?? null,
                ];

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
     * Perform IN/OUT operations for timing belts
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
                
                $oldStock = $timingBelt->total_mm;
                $change = $request->quantity;
                
                if ($request->action === 'IN') {
                    $timingBelt->total_mm += $change;
                    $timingBelt->in_mm += $change;
                } else { // OUT
                    if ($timingBelt->total_mm < $change) {
                        throw new \Exception("Insufficient stock for {$timingBelt->section}-{$timingBelt->size}. Available: {$timingBelt->total_mm}mm, Requested: {$change}mm");
                    }
                    $timingBelt->total_mm -= $change;
                    $timingBelt->out_mm += $change;
                }
                
                $timingBelt->save();

                // Create transaction record
                InventoryTransaction::create([
                    'category' => 'timing_belts',
                    'product_id' => $timingBelt->id,
                    'type' => $request->action,
                    'quantity' => $request->quantity,
                    'stock_before' => $oldStock,
                    'stock_after' => $timingBelt->total_mm,
                    'rate' => $timingBelt->rate ?? 0,
                    'description' => "{$request->action} {$change}mm",
                    'user_id' => session('user')['id'] ?? null,
                ]);

                $results[] = [
                    'id' => $timingBelt->id,
                    'section' => $timingBelt->section,
                    'size' => $timingBelt->size,
                    'old_stock' => $oldStock,
                    'new_stock' => $timingBelt->total_mm,
                    'change' => $request->quantity,
                ];
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
}
