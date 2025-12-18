<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

// Authentication routes (no middleware needed)
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout']);
Route::get('/user', [AuthController::class, 'user']);

// Admin only routes (protected by middleware)
Route::middleware(['App\Http\Middleware\CheckSession'])->group(function () {
    Route::post('/users', [AuthController::class, 'createUser']);
    Route::get('/users', [AuthController::class, 'getUsers']);
    Route::delete('/users/{id}', [AuthController::class, 'deleteUser']);
});