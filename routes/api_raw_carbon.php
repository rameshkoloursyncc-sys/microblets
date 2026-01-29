<?php

use App\Http\Controllers\Api\RawCarbonController;
use Illuminate\Support\Facades\Route;

// COGGED BELTS CRUD (No auth for now)
Route::prefix('rawcarbon')->group(function () {
    Route::get('/', [RawCarbonController::class, 'index']);
    Route::get('/section/{section}', [RawCarbonController::class, 'bySection']);
    Route::get('/category/{category}', [RawCarbonController::class, 'byCategory']);
    Route::post('/', [RawCarbonController::class, 'store']);
    Route::put('/{id}', [RawCarbonController::class, 'update']);
    Route::delete('/{id}', [RawCarbonController::class, 'destroy']);
    
    // Bulk operations
    Route::post('/bulk-import', [RawCarbonController::class, 'bulkImport']);
    Route::post('/in-out', [RawCarbonController::class, 'inOut']);
    
    // Transaction history
    Route::get('/{id}/transactions', [RawCarbonController::class, 'transactions']);
    
    // Settings page endpoints
    Route::post('/update-section-rate', [RawCarbonController::class, 'updateSectionRate']);
    Route::post('/seed-section', [RawCarbonController::class, 'seedSection']);
    Route::delete('/clear-section/{section}', [RawCarbonController::class, 'clearSection']);
    Route::delete('/clear-all', [RawCarbonController::class, 'clearAll']);
    
    // Rate recalculation endpoints
    Route::post('/recalculate-section-rates', [RawCarbonController::class, 'recalculateSectionRates']);
    Route::post('/recalculate-all-rates', [RawCarbonController::class, 'recalculateAllRates']);
    
    // Global inventory management
    Route::post('/update-global-min-inventory', [RawCarbonController::class, 'updateGlobalMinInventory']);
});
