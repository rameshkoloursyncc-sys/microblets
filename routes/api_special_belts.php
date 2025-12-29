<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SpecialBeltController;

// Special Belts API Routes
Route::prefix('special-belts')->group(function () {
    // Basic CRUD
    Route::get('/', [SpecialBeltController::class, 'index']);
    Route::post('/', [SpecialBeltController::class, 'store']);
    Route::get('/{specialBelt}', [SpecialBeltController::class, 'show']);
    Route::put('/{specialBelt}', [SpecialBeltController::class, 'update']);
    Route::delete('/{specialBelt}', [SpecialBeltController::class, 'destroy']);

    // Section-specific routes
    Route::get('/section/{section}', [SpecialBeltController::class, 'getBySection']);

    // Bulk operations
    Route::post('/bulk-import', [SpecialBeltController::class, 'bulkImport']);

    // IN/OUT operations
    Route::post('/in-out', [SpecialBeltController::class, 'inOutOperation']);

    // Transaction history
    Route::get('/{specialBelt}/transactions', [SpecialBeltController::class, 'getTransactions']);

    // Global settings
    Route::post('/update-global-min-inventory', [SpecialBeltController::class, 'updateGlobalMinInventory']);
});