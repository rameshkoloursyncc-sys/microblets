<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TpuBeltController;

/*
|--------------------------------------------------------------------------
| TPU Belts API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('tpu-belts')->group(function () {
    // Basic CRUD operations
    Route::get('/', [TpuBeltController::class, 'index']);
    Route::post('/', [TpuBeltController::class, 'store']);
    Route::get('/{tpuBelt}', [TpuBeltController::class, 'show']);
    Route::put('/{tpuBelt}', [TpuBeltController::class, 'update']);
    Route::delete('/{tpuBelt}', [TpuBeltController::class, 'destroy']);

    // Section-specific routes
    Route::get('/section/{section}', [TpuBeltController::class, 'getBySection']);

    // Bulk operations
    Route::post('/bulk-import', [TpuBeltController::class, 'bulkImport']);
    Route::post('/in-out', [TpuBeltController::class, 'inOutOperation']);

    // Transaction history
    Route::get('/{tpuBelt}/transactions', [TpuBeltController::class, 'getTransactions']);

    // Settings page endpoints
    Route::post('/update-section-rate', [TpuBeltController::class, 'updateSectionRate']);
    Route::post('/update-global-min-inventory', [TpuBeltController::class, 'updateGlobalMinInventory']);
    Route::post('/seed-section', [TpuBeltController::class, 'seedSection']);
    Route::delete('/clear-section/{section}', [TpuBeltController::class, 'clearSection']);
    Route::delete('/clear-all', [TpuBeltController::class, 'clearAll']);
    
    // Rate recalculation endpoints
    Route::post('/recalculate-section-rates', [TpuBeltController::class, 'recalculateSectionRates']);
    Route::post('/recalculate-all-rates', [TpuBeltController::class, 'recalculateAllRates']);
});