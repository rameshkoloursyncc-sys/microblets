<?php

use App\Http\Controllers\Api\VeeBeltController;
use App\Http\Controllers\Api\RateFormulaController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Vee Belts API Routes
|--------------------------------------------------------------------------
|
| Add these routes to your routes/api.php file:
| require __DIR__.'/api_vee_belts.php';
|
*/

// VEE BELTS CRUD (No auth for now - add 'auth:sanctum' middleware later)
Route::prefix('vee-belts')->group(function () {
    Route::get('/', [VeeBeltController::class, 'index']);
    Route::get('/section/{section}', [VeeBeltController::class, 'bySection']);
    Route::post('/', [VeeBeltController::class, 'store']);
    Route::put('/{id}', [VeeBeltController::class, 'update']);
    Route::delete('/{id}', [VeeBeltController::class, 'destroy']);
    
    // Bulk operations
    Route::post('/bulk-import', [VeeBeltController::class, 'bulkImport']);
    Route::post('/in-out', [VeeBeltController::class, 'inOut']);
    
    // Transaction history
    Route::get('/{id}/transactions', [VeeBeltController::class, 'transactions']);
    
    // Settings page endpoints
    Route::post('/update-section-rate', [VeeBeltController::class, 'updateSectionRate']);
    Route::post('/seed-section', [VeeBeltController::class, 'seedSection']);
    Route::delete('/clear-section/{section}', [VeeBeltController::class, 'clearSection']);
    Route::delete('/clear-all', [VeeBeltController::class, 'clearAll']);
    
    // Rate recalculation endpoints
    Route::post('/recalculate-section-rates', [VeeBeltController::class, 'recalculateSectionRates']);
    Route::post('/recalculate-all-rates', [VeeBeltController::class, 'recalculateAllRates']);
    
    // Global inventory management
    Route::post('/update-global-min-inventory', [VeeBeltController::class, 'updateGlobalMinInventory']);
});

// RATE FORMULAS (No auth for now - add 'auth:sanctum' and 'role:admin' middleware later)
Route::prefix('rate-formulas')->group(function () {
    Route::get('/', [RateFormulaController::class, 'index']);
    Route::post('/', [RateFormulaController::class, 'store']);
    Route::put('/{id}', [RateFormulaController::class, 'update']);
    Route::delete('/{id}', [RateFormulaController::class, 'destroy']);
});
