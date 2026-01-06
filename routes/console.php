<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule daily stock alert report (low stock + out of stock)
Schedule::command('report:low-stock')
    ->dailyAt('08:00')
    ->timezone('Asia/Kolkata')
    ->description('Send daily stock alert report (low stock + out of stock) via email');
