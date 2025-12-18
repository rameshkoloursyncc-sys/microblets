<?php

use App\Http\Controllers\Api\CoggedBeltController;
use Illuminate\Support\Facades\Route;

// COGGED BELTS CRUD (No auth for now)
Route::prefix('cogged-belts')->group(function () {
    Route::get('/', [CoggedBeltController::class, 'index']);
    Route::get('/section/{section}', [CoggedBeltController::class, 'bySection']);
    Route::post('/', [CoggedBeltController::class, 'store']);
    Route::put('/{id}', [CoggedBeltController::class, 'update']);
    Route::delete('/{id}', [CoggedBeltController::class, 'destroy']);
    
    // Bulk operations
    Route::post('/bulk-import', [CoggedBeltController::class, 'bulkImport']);
    Route::post('/in-out', [CoggedBeltController::class, 'inOut']);
    
    // Transaction history
    Route::get('/{id}/transactions', [CoggedBeltController::class, 'transactions']);
    
    // Settings page endpoints
    Route::post('/update-section-rate', [CoggedBeltController::class, 'updateSectionRate']);
    Route::post('/seed-section', [CoggedBeltController::class, 'seedSection']);
    Route::delete('/clear-section/{section}', [CoggedBeltController::class, 'clearSection']);
    Route::delete('/clear-all', [CoggedBeltController::class, 'clearAll']);
    
    // Rate recalculation endpoints
    Route::post('/recalculate-section-rates', [CoggedBeltController::class, 'recalculateSectionRates']);
    Route::post('/recalculate-all-rates', [CoggedBeltController::class, 'recalculateAllRates']);
});
