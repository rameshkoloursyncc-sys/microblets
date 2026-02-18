<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule daily stock alert report (low stock + out of stock)
Schedule::command('report:low-stock', [
        '--email' => [
            'sales@microbelts.com',
            'ramesh.koloursyncc@gmail.com',
        ],
    ])
    ->dailyAt('20:10')
    ->timezone('Asia/Kolkata')
    ->appendOutputTo(storage_path('logs/stock-alerts.log'));
