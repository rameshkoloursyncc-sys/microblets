<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->loadApiRoutes();
    }
    protected function loadApiRoutes(): void
    {
        $apiRoutes = base_path('routes/api.php');

        if (file_exists($apiRoutes)) {
            Route::prefix('api')
                ->middleware('api')
                ->group($apiRoutes);
        }
    }
}
