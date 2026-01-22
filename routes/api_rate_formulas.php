<?php

use App\Http\Controllers\Api\RateFormulaController;
use Illuminate\Support\Facades\Route;

// RATE FORMULAS API
Route::prefix('rate-formulas')->group(function () {
    Route::get('/', [RateFormulaController::class, 'index']);
    Route::get('/all', [RateFormulaController::class , 'getAllFormulas']);
    Route::post('/', [RateFormulaController::class, 'store']);
    Route::put('/{id}', [RateFormulaController::class, 'update']);
    Route::delete('/{id}', [RateFormulaController::class, 'destroy']);
    
    // Settings page specific routes
    Route::post('/update', [RateFormulaController::class, 'updateBySection']);
});