<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TimingBeltController;

// Timing Belts API Routes
Route::prefix('timing-belts')->group(function () {
    // Basic CRUD
    Route::get('/', [TimingBeltController::class, 'index']);
    Route::post('/', [TimingBeltController::class, 'store']);
    Route::get('/{timingBelt}', [TimingBeltController::class, 'show']);
    Route::put('/{timingBelt}', [TimingBeltController::class, 'update']);
    Route::delete('/{timingBelt}', [TimingBeltController::class, 'destroy']);

    // Section-specific routes
    Route::get('/section/{section}', [TimingBeltController::class, 'getBySection']);

    // Bulk operations
    Route::post('/bulk-import', [TimingBeltController::class, 'bulkImport']);

    // IN/OUT operations
    Route::post('/in-out', [TimingBeltController::class, 'inOutOperation']);

    // Transaction history
    Route::get('/{timingBelt}/transactions', [TimingBeltController::class, 'getTransactions']);

    // Global settings
    Route::post('/update-global-min-inventory', [TimingBeltController::class, 'updateGlobalMinInventory']);
    
    // Settings page endpoints
    Route::post('/update-section-rate', [TimingBeltController::class, 'updateSectionRate']);
    Route::post('/seed-section', [TimingBeltController::class, 'seedSection']);
    Route::delete('/clear-section/{section}', [TimingBeltController::class, 'clearSection']);
    Route::delete('/clear-all', [TimingBeltController::class, 'clearAll']);
});