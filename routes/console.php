<?php

use App\Services\UrgentOrderService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('urgent-orders:mark-ready', function (UrgentOrderService $urgentOrderService) {
    $markedReadyCount = $urgentOrderService->markDueOrdersReady();

    $this->info("Marked {$markedReadyCount} urgent order(s) ready.");
})->purpose("Let MarketLink AI mark urgent orders ready once the farmer's prep time has passed");

// "php artisan schedule:work" chahiye (production mein cron) - SETUP.md dekho.
// na chale to bhi order pages khud update kar dete hain (MarkDueUrgentOrdersReady)
Schedule::command('urgent-orders:mark-ready')->everyMinute()->withoutOverlapping();
