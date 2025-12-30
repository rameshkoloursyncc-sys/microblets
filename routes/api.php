<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\DashboardController;

// Example test route
Route::get('/ping', function () {
    return response()->json(['message' => 'API is working']);
});

// Temporary test route for dashboard stats (no auth required)
Route::get('/test-dashboard-stats', [DashboardController::class, 'getInventoryStats']);
Route::get('/test-poly-debug', [DashboardController::class, 'getPolyBeltTotalDebug']);
Route::get('/test-tpu-debug', [DashboardController::class, 'getTpuBeltTotalDebug']);
Route::get('/test-poly-rate/{id}', [PolyBeltController::class, 'testRateCalculation']);

// Test session endpoint
Route::get('/test-session', function () {
    return response()->json([
        'session_id' => session()->getId(),
        'session_user' => session('user'),
        'all_session_data' => session()->all()
    ]);
});


// Protected Product Routes
Route::middleware(['App\Http\Middleware\CheckSession'])->group(function () {
    Route::apiResource('products', ProductController::class);
    Route::post('products/bulk-upload', [ProductController::class, 'bulkUpload']);
});

// Protected API Routes (require authentication)
Route::middleware(['App\Http\Middleware\CheckSession'])->group(function () {
    // Dashboard API Routes
    Route::get('dashboard/inventory-stats', [DashboardController::class, 'getInventoryStats']);
    
    // Debug routes for individual belt types
    Route::get('dashboard/vee-belts-debug', [DashboardController::class, 'getVeeBeltTotalDebug']);
    Route::get('dashboard/cogged-belts-debug', [DashboardController::class, 'getCoggedBeltTotalDebug']);
    Route::get('dashboard/poly-belts-debug', [DashboardController::class, 'getPolyBeltTotalDebug']);
    Route::get('dashboard/tpu-belts-debug', [DashboardController::class, 'getTpuBeltTotalDebug']);
    Route::get('dashboard/timing-belts-debug', [DashboardController::class, 'getTimingBeltTotalDebug']);
    Route::get('dashboard/special-belts-debug', [DashboardController::class, 'getSpecialBeltTotalDebug']);
    
    // Debug route for all belt types combined
    Route::get('dashboard/all-belts-debug', [DashboardController::class, 'getAllBeltTotalsDebug']);
    
    // Section-wise totals for vee belts
    Route::get('dashboard/vee-belts-sections', [DashboardController::class, 'getVeeBeltSectionTotals']);
    
    // Check table structures
    Route::get('dashboard/check-tables', [DashboardController::class, 'checkTableStructures']);

    // Vee Belts API Routes
    require __DIR__.'/api_vee_belts.php';

    // Cogged Belts API Routes
    require __DIR__.'/api_cogged_belts.php';

    // Poly Belts API Routes
    require __DIR__.'/api_poly_belts.php';

    // TPU Belts API Routes
    require __DIR__.'/api_tpu_belts.php';

    // Timing Belts API Routes
    require __DIR__.'/api_timing_belts.php';

    // Special Belts API Routes
    require __DIR__.'/api_special_belts.php';

    // Rate Formula API Routes
    require __DIR__.'/api_rate_formulas.php';
});

// Authentication API Routes
require __DIR__.'/api_auth.php';
