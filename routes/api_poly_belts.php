<?php

use App\Http\Controllers\Api\PolyBeltController;
use Illuminate\Support\Facades\Route;

// POLY BELTS CRUD (No auth for now)
Route::prefix('poly-belts')->group(function () {
    Route::get('/', [PolyBeltController::class, 'index']);
    Route::get('/section/{section}', [PolyBeltController::class, 'bySection']);
    Route::post('/', [PolyBeltController::class, 'store']);
    Route::put('/{id}', [PolyBeltController::class, 'update']);
    Route::delete('/{id}', [PolyBeltController::class, 'destroy']);
    
    // Bulk operations
    Route::post('/bulk-import', [PolyBeltController::class, 'bulkImport']);
    Route::post('/in-out', [PolyBeltController::class, 'inOut']);
    
    // Transaction history
    Route::get('/{id}/transactions', [PolyBeltController::class, 'transactions']);
    
    // Settings page endpoints
    Route::post('/update-section-rate', [PolyBeltController::class, 'updateSectionRate']);
    Route::post('/seed-section', [PolyBeltController::class, 'seedSection']);
    Route::delete('/clear-section/{section}', [PolyBeltController::class, 'clearSection']);
    Route::delete('/clear-all', [PolyBeltController::class, 'clearAll']);
    
    // Rate recalculation endpoints
    Route::post('/recalculate-section-rates', [PolyBeltController::class, 'recalculateSectionRates']);
    Route::post('/recalculate-all-rates', [PolyBeltController::class, 'recalculateAllRates']);
});