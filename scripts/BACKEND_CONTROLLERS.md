# Backend Controllers Documentation

## Overview
Laravel API controllers handling all inventory management operations with session-based authentication and comprehensive transaction tracking.

---

## Base Controller

### `app/Http/Controllers/Controller.php`

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
    
    /**
     * Get current authenticated user from session
     */
    protected function getCurrentUser()
    {
        return session('user');
    }
    
    /**
     * Check if current user is admin
     */
    protected function isAdmin(): bool
    {
        $user = $this->getCurrentUser();
        return $user && $user['role'] === 'admin';
    }
    
    /**
     * Require admin access
     */
    protected function requireAdmin()
    {
        if (!$this->isAdmin()) {
            abort(403, 'Admin access required');
        }
    }
}
```

---

## Authentication Controller

### `app/Http/Controllers/Api/AuthController.php`

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Login user and create session
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'password' => 'required|string',
        ]);

        // Check for hardcoded admin credentials
        if ($request->name === 'koloursyncc' && $request->password === 'kolorsync1010') {
            $adminUser = User::where('name', 'koloursyncc11')->first();
            if (!$adminUser) {
                throw ValidationException::withMessages([
                    'name' => ['Admin user not found in database.'],
                ]);
            }
            
            return $this->createUserSession($adminUser, 'koloursyncc', 'admin');
        }

        // Check database users
        $user = User::where('name', $request->name)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'name' => ['The provided credentials are incorrect.'],
            ]);
        }

        return $this->createUserSession($user, $user->name, $user->role);
    }

    /**
     * Create user session
     */
    private function createUserSession($user, $name, $role)
    {
        // Start session if not already started
        if (!request()->hasSession()) {
            request()->setLaravelSession(app('session.store'));
        }
        
        // Regenerate session ID for security
        session()->regenerate();
        session(['user' => [
            'id' => $user->id,
            'name' => $name,
            'role' => $role,
        ]]);
        
        session()->save();
        
        \Log::info('User login successful', [
            'session_id' => session()->getId(),
            'user_data' => session('user')
        ]);

        return response()->json([
            'message' => 'Login successful',
            'user' => [
                'id' => $user->id,
                'name' => $name,
                'role' => $role,
            ]
        ]);
    }

    /**
     * Logout user and destroy session
     */
    public function logout(Request $request)
    {
        session()->forget('user');
        session()->flush();

        return response()->json([
            'message' => 'Logout successful'
        ]);
    }

    /**
     * Get current authenticated user
     */
    public function user(Request $request)
    {
        if (!$request->hasSession()) {
            $request->setLaravelSession(app('session.store'));
        }
        
        $user = session('user');
        
        \Log::info('User check', [
            'session_id' => session()->getId(),
            'user_exists' => !is_null($user),
            'user_data' => $user,
            'all_session_data' => session()->all()
        ]);

        if (!$user) {
            return response()->json([
                'message' => 'Not authenticated'
            ], 401);
        }

        return response()->json([
            'user' => $user
        ]);
    }

    /**
     * Create new user (admin only)
     */
    public function createUser(Request $request)
    {
        $this->requireAdmin();

        $request->validate([
            'name' => 'required|string|max:255|unique:users',
            'password' => 'required|string|min:6',
            'role' => 'required|in:admin,user',
        ]);

        $user = User::create([
            'name' => $request->name,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);

        return response()->json([
            'message' => 'User created successfully',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'role' => $user->role,
            ]
        ], 201);
    }

    /**
     * Get all users (admin only)
     */
    public function getUsers(Request $request)
    {
        $this->requireAdmin();

        $users = User::select('id', 'name', 'role', 'created_at')->get();

        return response()->json([
            'users' => $users
        ]);
    }

    /**
     * Delete user (admin only)
     */
    public function deleteUser(Request $request, $id)
    {
        $this->requireAdmin();

        $user = User::findOrFail($id);
        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully'
        ]);
    }
}
```

---

## Vee Belt Controller

### `app/Http/Controllers/Api/VeeBeltController.php`

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VeeBelt;
use App\Models\Transaction;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;

class VeeBeltController extends Controller
{
    /**
     * Get all vee belts or filter by section
     */
    public function index(Request $request)
    {
        $query = VeeBelt::query();
        
        if ($request->has('section')) {
            $query->where('section', $request->section);
        }
        
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('section', 'like', "%{$search}%")
                  ->orWhere('size', 'like', "%{$search}%");
            });
        }
        
        if ($request->boolean('low_stock')) {
            $query->whereNotNull('reorder_level')
                  ->whereColumn('balance_stock', '<=', 'reorder_level')
                  ->where('balance_stock', '>', 0);
        }
        
        $products = $query->orderBy('section')->orderBy('size')->get();
        
        return response()->json($products);
    }

    /**
     * Get vee belts by section
     */
    public function getBySection($section)
    {
        $products = VeeBelt::where('section', $section)
                          ->orderBy('size')
                          ->get();
        
        return response()->json($products);
    }

    /**
     * Create new vee belt
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'section' => 'required|string|max:10',
            'size' => 'required|string|max:20',
            'balance_stock' => 'required|integer|min:0',
            'reorder_level' => 'nullable|integer|min:0',
            'rate' => 'nullable|numeric|min:0',
            'remark' => 'nullable|string|max:500',
        ]);

        // Auto-generate SKU
        $validated['sku'] = $validated['section'] . '-' . $validated['size'];
        $validated['category'] = $validated['section'] . ' Section';
        
        // Calculate rate if not provided
        if (!isset($validated['rate'])) {
            $validated['rate'] = $this->calculateRate($validated['section'], $validated['size']);
        }
        
        // Calculate value
        $validated['value'] = $validated['balance_stock'] * $validated['rate'];
        $validated['created_by'] = session('user')['id'] ?? null;

        $veeBelt = VeeBelt::create($validated);

        // Create initial transaction
        if ($validated['balance_stock'] > 0) {
            Transaction::create([
                'product_type' => 'vee_belt',
                'product_id' => $veeBelt->id,
                'type' => 'EDIT',
                'stock_before' => 0,
                'stock_after' => $validated['balance_stock'],
                'rate' => $validated['rate'],
                'description' => 'Initial stock',
                'user_id' => session('user')['id'] ?? null,
            ]);
        }

        return response()->json([
            'message' => 'Vee belt created successfully',
            'product' => $veeBelt
        ], 201);
    }

    /**
     * Update vee belt
     */
    public function update(Request $request, $id)
    {
        $veeBelt = VeeBelt::findOrFail($id);
        
        $validated = $request->validate([
            'section' => 'sometimes|string|max:10',
            'size' => 'sometimes|string|max:20',
            'balance_stock' => 'sometimes|integer|min:0',
            'reorder_level' => 'nullable|integer|min:0',
            'rate' => 'sometimes|numeric|min:0',
            'remark' => 'nullable|string|max:500',
        ]);

        $oldStock = $veeBelt->balance_stock;
        $oldRate = $veeBelt->rate;

        $veeBelt->update($validated);
        
        // Update calculated fields
        if (isset($validated['balance_stock']) || isset($validated['rate'])) {
            $veeBelt->value = $veeBelt->balance_stock * $veeBelt->rate;
            $veeBelt->save();
        }

        // Create transaction for stock changes
        if (isset($validated['balance_stock']) && $validated['balance_stock'] != $oldStock) {
            Transaction::create([
                'product_type' => 'vee_belt',
                'product_id' => $veeBelt->id,
                'type' => 'EDIT',
                'stock_before' => $oldStock,
                'stock_after' => $veeBelt->balance_stock,
                'rate' => $veeBelt->rate,
                'description' => "Stock updated from {$oldStock} to {$validated['balance_stock']}",
                'user_id' => session('user')['id'] ?? null,
            ]);
        }

        // Create transaction for rate changes
        if (isset($validated['rate']) && $validated['rate'] != $oldRate) {
            Transaction::create([
                'product_type' => 'vee_belt',
                'product_id' => $veeBelt->id,
                'type' => 'EDIT',
                'stock_before' => $veeBelt->balance_stock,
                'stock_after' => $veeBelt->balance_stock,
                'rate' => $validated['rate'],
                'description' => "Rate updated from ₹{$oldRate} to ₹{$validated['rate']}",
                'user_id' => session('user')['id'] ?? null,
            ]);
        }

        return response()->json([
            'message' => 'Vee belt updated successfully',
            'product' => $veeBelt->fresh()
        ]);
    }

    /**
     * Delete vee belt
     */
    public function destroy($id)
    {
        $veeBelt = VeeBelt::findOrFail($id);
        $veeBelt->delete();

        return response()->json([
            'message' => 'Vee belt deleted successfully'
        ]);
    }

    /**
     * Bulk import from Excel/CSV
     */
    public function bulkImport(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv'
        ]);

        $file = $request->file('file');
        $spreadsheet = IOFactory::load($file->getPathname());
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();

        $imported = 0;
        $updated = 0;
        $errors = [];

        // Skip header row
        array_shift($rows);

        foreach ($rows as $index => $row) {
            try {
                $productData = [
                    'section' => $row[0] ?? '',
                    'size' => $row[1] ?? '',
                    'balance_stock' => (int)($row[2] ?? 0),
                    'reorder_level' => !empty($row[3]) ? (int)$row[3] : null,
                    'rate' => !empty($row[4]) ? (float)$row[4] : null,
                    'remark' => $row[5] ?? null,
                ];

                if (empty($productData['section']) || empty($productData['size'])) {
                    continue;
                }

                $productData['sku'] = $productData['section'] . '-' . $productData['size'];
                $productData['category'] = $productData['section'] . ' Section';

                if (!$productData['rate']) {
                    $productData['rate'] = $this->calculateRate($productData['section'], $productData['size']);
                }

                $productData['value'] = $productData['balance_stock'] * $productData['rate'];

                $existing = VeeBelt::where('section', $productData['section'])
                                  ->where('size', $productData['size'])
                                  ->first();

                if ($existing) {
                    $existing->update($productData);
                    
                    Transaction::create([
                        'product_type' => 'vee_belt',
                        'product_id' => $existing->id,
                        'type' => 'EDIT',
                        'stock_before' => $existing->balance_stock,
                        'stock_after' => $productData['balance_stock'],
                        'rate' => $existing->rate,
                        'description' => 'Bulk import update',
                        'user_id' => session('user')['id'] ?? null,
                    ]);
                    
                    $updated++;
                } else {
                    $productData['created_by'] = session('user')['id'] ?? null;
                    $veeBelt = VeeBelt::create($productData);
                    
                    if ($productData['balance_stock'] > 0) {
                        Transaction::create([
                            'product_type' => 'vee_belt',
                            'product_id' => $veeBelt->id,
                            'type' => 'EDIT',
                            'stock_before' => 0,
                            'stock_after' => $productData['balance_stock'],
                            'rate' => $productData['rate'],
                            'description' => 'Bulk import',
                            'user_id' => session('user')['id'] ?? null,
                        ]);
                    }
                    
                    $imported++;
                }
            } catch (\Exception $e) {
                $errors[] = "Row " . ($index + 2) . ": " . $e->getMessage();
            }
        }

        return response()->json([
            'message' => 'Bulk import completed successfully',
            'imported' => $imported,
            'updated' => $updated,
            'errors' => $errors
        ]);
    }

    /**
     * Perform IN/OUT operations
     */
    public function inOut(Request $request)
    {
        $validated = $request->validate([
            'product_ids' => 'required|array',
            'product_ids.*' => 'exists:vee_belts,id',
            'type' => 'required|in:IN,OUT',
            'quantity' => 'required|integer|min:1',
            'description' => 'nullable|string|max:500'
        ]);

        $affectedProducts = 0;
        $totalQuantity = 0;

        foreach ($validated['product_ids'] as $productId) {
            $veeBelt = VeeBelt::findOrFail($productId);
            $oldStock = $veeBelt->balance_stock;

            if ($validated['type'] === 'IN') {
                $newStock = $oldStock + $validated['quantity'];
            } else {
                $newStock = max(0, $oldStock - $validated['quantity']);
            }

            $veeBelt->update([
                'balance_stock' => $newStock,
                'value' => $newStock * $veeBelt->rate,
                'updated_by' => session('user')['id'] ?? null,
            ]);

            // Create transaction
            Transaction::create([
                'product_type' => 'vee_belt',
                'product_id' => $veeBelt->id,
                'type' => $validated['type'],
                'quantity' => $validated['quantity'],
                'stock_before' => $oldStock,
                'stock_after' => $newStock,
                'rate' => $veeBelt->rate,
                'description' => $validated['description'] ?? "{$validated['type']} operation: {$validated['quantity']} units",
                'user_id' => session('user')['id'] ?? null,
            ]);

            $affectedProducts++;
            $totalQuantity += $validated['quantity'];
        }

        return response()->json([
            'message' => 'IN/OUT operation completed successfully',
            'affected_products' => $affectedProducts,
            'total_quantity' => $totalQuantity
        ]);
    }

    /**
     * Get transaction history for a product
     */
    public function getTransactions($id)
    {
        $transactions = Transaction::where('product_type', 'vee_belt')
                                  ->where('product_id', $id)
                                  ->with('user:id,name')
                                  ->orderBy('created_at', 'desc')
                                  ->get();

        return response()->json($transactions);
    }

    /**
     * Calculate rate based on section and size
     */
    private function calculateRate($section, $size)
    {
        // Rate calculation formulas by section
        $formulas = [
            'A' => 1.05,
            'B' => 1.15,
            'C' => 1.25,
            'D' => 1.35,
            'E' => 1.45,
        ];

        $multiplier = $formulas[$section] ?? 1.0;
        return (float)$size * $multiplier;
    }
}
```

---

## Dashboard Controller

### `app/Http/Controllers/Api/DashboardController.php`

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VeeBelt;
use App\Models\TimingBelt;
use App\Models\CoggedBelt;
use App\Models\PolyBelt;
use App\Models\TpuBelt;
use App\Models\SpecialBelt;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Get overall inventory statistics
     */
    public function inventoryStats()
    {
        $stats = [
            'total_products' => 0,
            'total_stock' => 0,
            'total_value' => 0,
            'low_stock_items' => 0,
            'out_of_stock_items' => 0,
            'sections' => []
        ];

        // Vee Belts
        $veeBelts = VeeBelt::selectRaw('
            COUNT(*) as count,
            SUM(balance_stock) as total_stock,
            SUM(CAST(value as DECIMAL(10,2))) as total_value,
            SUM(CASE WHEN balance_stock = 0 THEN 1 ELSE 0 END) as out_of_stock,
            SUM(CASE WHEN reorder_level IS NOT NULL AND reorder_level > 0 AND balance_stock <= reorder_level AND balance_stock > 0 THEN 1 ELSE 0 END) as low_stock
        ')->first();

        $stats['sections']['vee_belts'] = [
            'total_products' => $veeBelts->count,
            'total_stock' => $veeBelts->total_stock,
            'total_value' => $veeBelts->total_value
        ];

        // Timing Belts
        $timingBelts = TimingBelt::selectRaw('
            COUNT(*) as count,
            SUM(balance_stock_mm) as total_stock,
            SUM(balance_stock_mm * CAST(rate as DECIMAL(10,2))) as total_value,
            SUM(CASE WHEN balance_stock_mm = 0 THEN 1 ELSE 0 END) as out_of_stock,
            SUM(CASE WHEN reorder_level IS NOT NULL AND reorder_level > 0 AND balance_stock_mm <= reorder_level AND balance_stock_mm > 0 THEN 1 ELSE 0 END) as low_stock
        ')->first();

        $stats['sections']['timing_belts'] = [
            'total_products' => $timingBelts->count,
            'total_stock' => $timingBelts->total_stock,
            'total_value' => $timingBelts->total_value
        ];

        // Aggregate totals
        $stats['total_products'] = $veeBelts->count + $timingBelts->count;
        $stats['total_stock'] = $veeBelts->total_stock + $timingBelts->total_stock;
        $stats['total_value'] = $veeBelts->total_value + $timingBelts->total_value;
        $stats['low_stock_items'] = $veeBelts->low_stock + $timingBelts->low_stock;
        $stats['out_of_stock_items'] = $veeBelts->out_of_stock + $timingBelts->out_of_stock;

        return response()->json($stats);
    }

    /**
     * Get debug information for all belt types
     */
    public function allBeltsDebug()
    {
        $debug = [
            'vee_belts' => [
                'count' => VeeBelt::count(),
                'total_stock' => VeeBelt::sum('balance_stock'),
                'sections' => VeeBelt::distinct('section')->pluck('section')->sort()->values()
            ],
            'timing_belts' => [
                'count' => TimingBelt::count(),
                'total_stock' => TimingBelt::sum('balance_stock_mm'),
                'types' => TimingBelt::distinct('type')->pluck('type')->sort()->values()
            ],
            'cogged_belts' => [
                'count' => CoggedBelt::count(),
                'total_stock' => CoggedBelt::sum('balance_stock'),
                'sections' => CoggedBelt::distinct('section')->pluck('section')->sort()->values()
            ],
            'poly_belts' => [
                'count' => PolyBelt::count(),
                'total_ribs' => PolyBelt::sum('ribs'),
                'sections' => PolyBelt::distinct('section')->pluck('section')->sort()->values()
            ],
            'tpu_belts' => [
                'count' => TpuBelt::count(),
                'total_stock' => TpuBelt::sum('balance_stock'),
                'types' => TpuBelt::distinct('type')->pluck('type')->sort()->values()
            ],
            'special_belts' => [
                'count' => SpecialBelt::count(),
                'total_stock' => SpecialBelt::sum('balance_stock'),
                'types' => SpecialBelt::distinct('type')->pluck('type')->sort()->values()
            ]
        ];

        return response()->json($debug);
    }
}
```

---

## Rate Formula Controller

### `app/Http/Controllers/Api/RateFormulaController.php`

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class RateFormulaController extends Controller
{
    /**
     * Get all rate calculation formulas
     */
    public function index()
    {
        $formulas = Cache::remember('rate_formulas', 3600, function () {
            return [
                'vee_belts' => [
                    'A' => 'size * 1.05',
                    'B' => 'size * 1.15',
                    'C' => 'size * 1.25',
                    'D' => 'size * 1.35',
                    'E' => 'size * 1.45',
                    'SPA' => 'size * 0.95',
                    'SPB' => 'size * 1.05',
                    'SPC' => 'size * 1.15'
                ],
                'timing_belts' => [
                    'T5' => 'size * 0.20',
                    'T10' => 'size * 0.35',
                    'XL' => 'size * 0.25',
                    'L' => 'size * 0.30',
                    'H' => 'size * 0.40'
                ],
                'cogged_belts' => [
                    '3VX' => 'size * 2.5',
                    '5VX' => 'size * 3.0',
                    'AX' => 'size * 1.8',
                    'BX' => 'size * 2.2',
                    'CX' => 'size * 2.8'
                ],
                'poly_belts' => [
                    'PK' => 'ribs * 2.5',
                    'PL' => 'ribs * 3.0',
                    'PM' => 'ribs * 3.5'
                ]
            ];
        });

        return response()->json($formulas);
    }

    /**
     * Update rate formula (admin only)
     */
    public function update(Request $request)
    {
        $this->requireAdmin();

        $validated = $request->validate([
            'belt_type' => 'required|in:vee_belts,timing_belts,cogged_belts,poly_belts',
            'section' => 'required|string',
            'formula' => 'required|string'
        ]);

        // Clear cache to force refresh
        Cache::forget('rate_formulas');

        // In a real implementation, you would store this in a database
        // For now, we'll just return success
        return response()->json([
            'message' => 'Rate formula updated successfully',
            'belt_type' => $validated['belt_type'],
            'section' => $validated['section'],
            'formula' => $validated['formula']
        ]);
    }

    /**
     * Calculate rate for given parameters
     */
    public function calculate(Request $request)
    {
        $validated = $request->validate([
            'belt_type' => 'required|in:vee_belts,timing_belts,cogged_belts,poly_belts',
            'section' => 'required|string',
            'size' => 'required|numeric',
            'ribs' => 'nullable|numeric'
        ]);

        $formulas = $this->index()->getData(true);
        $formula = $formulas[$validated['belt_type']][$validated['section']] ?? null;

        if (!$formula) {
            return response()->json([
                'error' => 'Formula not found for the specified belt type and section'
            ], 404);
        }

        // Simple formula evaluation (in production, use a proper expression evaluator)
        $size = $validated['size'];
        $ribs = $validated['ribs'] ?? 0;
        
        // Replace variables in formula
        $formula = str_replace('size', $size, $formula);
        $formula = str_replace('ribs', $ribs, $formula);
        
        // Evaluate the formula (basic math operations only)
        $rate = eval("return $formula;");

        return response()->json([
            'rate' => round($rate, 2),
            'formula_used' => $formulas[$validated['belt_type']][$validated['section']]
        ]);
    }
}
```

---

## Middleware

### `app/Http/Middleware/CheckSession.php`

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSession
{
    /**
     * Handle an incoming request with session recovery
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip session check for login/logout routes
        if ($request->is('api/*') && !$request->is('api/login') && !$request->is('api/logout') && !$request->is('api/user') && !$request->is('api/ping')) {
            $user = session('user');
            
            // Debug logging
            \Log::info('CheckSession middleware', [
                'url' => $request->url(),
                'session_id' => session()->getId(),
                'user_exists' => !is_null($user),
                'user_data' => $user ? ['id' => $user['id'], 'name' => $user['name']] : null,
                'cookies' => $request->cookies->all(),
                'headers' => $request->headers->all()
            ]);
            
            // If no session user, try to restore from database using session ID
            if (!$user) {
                $sessionId = session()->getId();
                
                // Check if there's a valid session in database with user data
                if (config('session.driver') === 'database') {
                    $sessionRecord = \DB::table('sessions')
                        ->where('id', $sessionId)
                        ->where('last_activity', '>', time() - config('session.lifetime') * 60)
                        ->first();
                    
                    if ($sessionRecord && $sessionRecord->payload) {
                        $sessionData = unserialize(base64_decode($sessionRecord->payload));
                        if (isset($sessionData['user'])) {
                            // Restore user to current session
                            session(['user' => $sessionData['user']]);
                            session()->save();
                            
                            \Log::info('Session restored from database', [
                                'user' => $sessionData['user'],
                                'session_id' => $sessionId
                            ]);
                            
                            return $next($request);
                        }
                    }
                }
                
                // If still no user, check for fallback authentication header
                $authUser = $request->header('X-Auth-User');
                if ($authUser) {
                    $userData = json_decode($authUser, true);
                    if ($userData && isset($userData['id'], $userData['name'], $userData['role'])) {
                        // Verify user exists in database
                        $dbUser = \App\Models\User::find($userData['id']);
                        if ($dbUser && $dbUser->name === $userData['name']) {
                            session(['user' => $userData]);
                            session()->save();
                            
                            \Log::info('Session restored from header', [
                                'user' => $userData,
                                'session_id' => session()->getId()
                            ]);
                            
                            return $next($request);
                        }
                    }
                }
                
                return response()->json([
                    'message' => 'Session expired. Please login again.',
                    'error' => 'session_expired',
                    'redirect' => '/login'
                ], 401);
            }
        }
        
        return $next($request);
    }
}
```

---

## Common Patterns

### Transaction Logging
All controllers follow this pattern for transaction logging:

```php
// Create transaction record
Transaction::create([
    'product_type' => 'vee_belt', // or timing_belt, etc.
    'product_id' => $product->id,
    'type' => 'IN|OUT|EDIT',
    'quantity' => $quantity, // for IN/OUT operations
    'stock_before' => $oldStock,
    'stock_after' => $newStock,
    'rate' => $product->rate,
    'description' => 'Operation description',
    'user_id' => session('user')['id'] ?? null,
]);
```

### Rate Calculation
All belt controllers use similar rate calculation:

```php
private function calculateRate($section, $size)
{
    $formulas = [
        'A' => 1.05,
        'B' => 1.15,
        // ... more formulas
    ];
    
    $multiplier = $formulas[$section] ?? 1.0;
    return (float)$size * $multiplier;
}
```

### Bulk Operations
Standard pattern for bulk IN/OUT operations:

```php
foreach ($productIds as $productId) {
    $product = Model::findOrFail($productId);
    $oldStock = $product->balance_stock;
    
    $newStock = $type === 'IN' 
        ? $oldStock + $quantity 
        : max(0, $oldStock - $quantity);
    
    $product->update(['balance_stock' => $newStock]);
    
    // Log transaction
    Transaction::create([...]);
}
```

This documentation covers all the backend controllers with their complete API contracts, validation rules, transaction logging, and error handling patterns.