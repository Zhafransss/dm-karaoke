<?php

use App\Models\WaLog;
use App\Services\WhatsApp\WhatsAppService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
 * Shared hosting cron runs `php artisan schedule:run` every minute (SRS §8).
 */

Artisan::command('wa:retry', function (WhatsAppService $wa) {
    $this->info($wa->retryDue().' pesan dicoba ulang.');
})->purpose('Kirim ulang notifikasi WhatsApp yang gagal (maks. 3 percobaan)');

Artisan::command('dmk:prune', function () {
    $n = WaLog::where('created_at', '<', now()->subDays(90))->delete();
    $this->info("{$n} log WhatsApp lama dihapus.");
})->purpose('Bersihkan log lama');

Schedule::command('wa:retry')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('dmk:prune')->dailyAt('02.00');
