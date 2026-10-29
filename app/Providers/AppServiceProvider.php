<?php

namespace App\Providers;

use App\Models\Message;
use App\Models\Reservation;
use App\Models\User;
use App\Services\AvailabilityService;
use App\Services\PromoService;
use App\Services\WhatsApp\FonnteGateway;
use App\Services\WhatsApp\SimulatedGateway;
use App\Services\WhatsApp\WhatsAppGateway;
use App\Support\Settings;
use Carbon\Carbon;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Request-scoped so their in-memory caches never leak between requests.
        $this->app->scoped(PromoService::class);
        $this->app->scoped(AvailabilityService::class);

        $this->app->bind(WhatsAppGateway::class, function () {
            $token = (string) Settings::get('wa_token');

            return match (Settings::get('wa_gateway')) {
                'fonnte' => $token ? new FonnteGateway($token) : new SimulatedGateway,
                default => new SimulatedGateway,
            };
        });
    }

    public function boot(): void
    {
        Carbon::setLocale('id');
        Route::resourceVerbs(['create' => 'baru', 'edit' => 'ubah']);
        Paginator::defaultView('partials.pagination');
        Paginator::defaultSimpleView('partials.pagination');

        // Admin sidebar badges (FR-ADM-03 "penanda jumlah menunggu").
        View::composer('layouts.admin', function ($view) {
            $view->with('navCounts', [
                'waiting' => Reservation::where('status', 'menunggu')->count(),
                'unread' => Message::whereNull('read_at')->count(),
            ]);
        });

        // One gate per permission from config/dmk.php (FR-ADM-02).
        foreach (array_keys(config('dmk.perm_labels')) as $perm) {
            Gate::define($perm, fn (User $user) => $user->hasPerm($perm));
        }
    }
}
