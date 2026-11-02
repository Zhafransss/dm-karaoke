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

// Run in-process via Artisan::call: Schedule::command() spawns a child process
// through proc_open, which Hostinger shared hosting disables.
Schedule::call(fn () => Artisan::call('wa:retry'))
    ->name('wa:retry')->everyFiveMinutes()->withoutOverlapping();
Schedule::call(fn () => Artisan::call('dmk:prune'))
    ->name('dmk:prune')->dailyAt('02.00');
