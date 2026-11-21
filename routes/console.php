<?php

use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Jadwal (scheduler)
|--------------------------------------------------------------------------
|
| Jalankan scheduler dengan: php artisan schedule:work (lokal)
| atau cron "* * * * * php artisan schedule:run" (server).
|
*/

// Kembalikan garasi yang sewanya sudah selesai menjadi available setiap hari.
Schedule::command('garages:sync-status')
    ->dailyAt('00:01')
    ->timezone(config('app.timezone'))
    ->withoutOverlapping();
