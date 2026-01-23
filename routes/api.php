<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\PolyBeltController;

// Example test route
Route::get('/ping', function () {
    return response()->json(['message' => 'API is working']);
});

// Temporary test route for dashboard stats (no auth required)
Route::get('/test-dashboard-stats', [\App\Http\Controllers\Api\DashboardController::class, 'getInventoryStats']);
Route::get('/test-poly-debug', [\App\Http\Controllers\Api\DashboardController::class, 'getPolyBeltTotalDebug']);
Route::get('/test-tpu-debug', [\App\Http\Controllers\Api\DashboardController::class, 'getTpuBeltTotalDebug']);
Route::get('/test-poly-rate/{id}', [\App\Http\Controllers\Api\PolyBeltController::class, 'testRateCalculation']);

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
    Route::get('dashboard/inventory-stats', [\App\Http\Controllers\Api\DashboardController::class, 'getInventoryStats']);
    Route::get('dashboard/low-stock-items', [\App\Http\Controllers\Api\DashboardController::class, 'getLowStockItems']);
    Route::post('dashboard/send-stock-alert', [\App\Http\Controllers\Api\DashboardController::class, 'sendStockAlert']);
    Route::get('dashboard/download-excel-report', [\App\Http\Controllers\Api\DashboardController::class, 'downloadExcelReport']);
    Route::get('dashboard/debug-stock-data', [\App\Http\Controllers\Api\DashboardController::class, 'debugStockData']);
    
    // Smart Stock Alert Routes
    Route::post('dashboard/send-smart-stock-alert', [\App\Http\Controllers\Api\DashboardController::class, 'sendSmartStockAlert']);
    Route::get('dashboard/die-requirements', [\App\Http\Controllers\Api\DashboardController::class, 'getDieRequirements']);
    
    // Die Configuration Routes
    Route::get('die-configurations', [\App\Http\Controllers\Api\DieConfigurationController::class, 'index']);
    Route::post('die-configurations', [\App\Http\Controllers\Api\DieConfigurationController::class, 'store']);
    Route::put('die-configurations/bulk', [\App\Http\Controllers\Api\DieConfigurationController::class, 'bulkUpdate']);
    Route::delete('die-configurations', [\App\Http\Controllers\Api\DieConfigurationController::class, 'destroy']);
    Route::post('die-configurations/seed-defaults', [\App\Http\Controllers\Api\DieConfigurationController::class, 'seedDefaults']);
    Route::get('die-configurations/get', [\App\Http\Controllers\Api\DieConfigurationController::class, 'getConfiguration']);
    
    // Debug routes for individual belt types
    Route::get('dashboard/vee-belts-debug', [\App\Http\Controllers\Api\DashboardController::class, 'getVeeBeltTotalDebug']);
    Route::get('dashboard/cogged-belts-debug', [\App\Http\Controllers\Api\DashboardController::class, 'getCoggedBeltTotalDebug']);
    Route::get('dashboard/poly-belts-debug', [\App\Http\Controllers\Api\DashboardController::class, 'getPolyBeltTotalDebug']);
    Route::get('dashboard/tpu-belts-debug', [\App\Http\Controllers\Api\DashboardController::class, 'getTpuBeltTotalDebug']);
    Route::get('dashboard/timing-belts-debug', [\App\Http\Controllers\Api\DashboardController::class, 'getTimingBeltTotalDebug']);
    Route::get('dashboard/special-belts-debug', [\App\Http\Controllers\Api\DashboardController::class, 'getSpecialBeltTotalDebug']);
    
    // Debug route for all belt types combined
    Route::get('dashboard/all-belts-debug', [\App\Http\Controllers\Api\DashboardController::class, 'getAllBeltTotalsDebug']);
    
    // Section-wise totals for vee belts
    Route::get('dashboard/vee-belts-sections', [\App\Http\Controllers\Api\DashboardController::class, 'getVeeBeltSectionTotals']);
    
    // Check table structures
    Route::get('dashboard/check-tables', [\App\Http\Controllers\Api\DashboardController::class, 'checkTableStructures']);

    // Inventory Routes with Stock Alert Middleware (temporarily disabled)
    // Route::middleware(['stock.alert'])->group(function () {
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
    // });

    // Timing Belt Excel Import/Export Routes (without stock alert middleware)
    Route::post('timing-belts/upload-excel', [App\Http\Controllers\TimingBeltExcelController::class, 'uploadExcel']);
    Route::post('timing-belts/import-to-database', [App\Http\Controllers\TimingBeltExcelController::class, 'importToDatabase']);
    Route::get('timing-belts/download-json', [App\Http\Controllers\TimingBeltExcelController::class, 'downloadJson']);

    // Rate Formula API Routes (without stock alert middleware)
    require __DIR__.'/api_rate_formulas.php';
});

// Authentication API Routes
require __DIR__.'/api_auth.php';
