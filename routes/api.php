<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProductController;

// Example test route
Route::get('/ping', function () {
    return response()->json(['message' => 'API is working']);
});


Route::apiResource('products', ProductController::class);
Route::post('products/bulk-upload', [ProductController::class, 'bulkUpload']);

// Vee Belts API Routes
require __DIR__.'/api_vee_belts.php';

// Cogged Belts API Routes
require __DIR__.'/api_cogged_belts.php';

// Poly Belts API Routes
require __DIR__.'/api_poly_belts.php';

// TPU Belts API Routes
require __DIR__.'/api_tpu_belts.php';

// Rate Formula API Routes
require __DIR__.'/api_rate_formulas.php';
