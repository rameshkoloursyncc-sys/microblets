<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    // Check if user is logged in via session
    $user = session('user');
    
    if ($user) {
        // User is logged in, redirect to inventory
        return redirect('/inventory');
    }
    
    // User not logged in, redirect to inventory (which will show login page)
    return redirect('/inventory');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('inventory', function () {
    return Inertia::render('inventory');
})->name('inventory');

require __DIR__.'/settings.php';
