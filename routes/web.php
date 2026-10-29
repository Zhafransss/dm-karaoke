<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Site;
use Illuminate\Support\Facades\Route;

/* ---------- Public site (SRS §6.1) ---------- */
Route::controller(Site\SiteController::class)->group(function () {
    Route::get('/', 'home')->name('home');
    Route::get('/harga', 'prices')->name('harga');
    Route::get('/promo', 'promos')->name('promo');
    Route::get('/event', 'events')->name('event');
    Route::get('/event/{slug}', 'event')->name('event.show');
    Route::get('/galeri', 'gallery')->name('galeri');
    Route::get('/kontak', 'contact')->name('kontak');
    Route::post('/kontak', 'contactStore')->name('kontak.store');
    Route::get('/sitemap.xml', 'sitemap')->name('sitemap');
    Route::get('/robots.txt', 'robots')->name('robots');
});

Route::controller(Site\ReservationController::class)->group(function () {
    Route::get('/reservasi', 'create')->name('reservasi');
    Route::get('/reservasi/ketersediaan', 'availability')->middleware('throttle:120,1')->name('reservasi.ketersediaan');
    Route::post('/reservasi/harga', 'price')->middleware('throttle:120,1')->name('reservasi.harga');
    Route::post('/reservasi', 'store')->name('reservasi.store');
    Route::get('/reservasi/{code}/sukses', 'success')->name('reservasi.sukses');
});

Route::get('/cek-status', [Site\StatusController::class, 'show'])->name('cek-status');
Route::post('/cek-status', [Site\StatusController::class, 'check'])->name('cek-status.check');

/* ---------- Admin panel (SRS §6.2) ---------- */
Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('/login', [Admin\AuthController::class, 'show'])->name('login');
        Route::post('/login', [Admin\AuthController::class, 'login'])->name('login.attempt');
    });
    Route::post('/logout', [Admin\AuthController::class, 'logout'])->middleware('auth')->name('logout');

    Route::middleware(['auth', 'active'])->group(function () {
        Route::get('/', Admin\DashboardController::class)->middleware('can:dashboard')->name('dashboard');

        Route::middleware('can:reservasi')->group(function () {
            Route::get('/jadwal', [Admin\ScheduleController::class, 'index'])->name('jadwal');
            Route::post('/jadwal/blokir', [Admin\ScheduleController::class, 'storeBlock'])->name('jadwal.blokir');
            Route::delete('/jadwal/blokir/{block}', [Admin\ScheduleController::class, 'destroyBlock'])->name('jadwal.blokir.hapus');

            Route::controller(Admin\ReservationController::class)->prefix('reservasi')->name('reservasi.')->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/baru', 'create')->name('create');
                Route::post('/', 'store')->name('store');
                Route::post('/pratinjau', 'preview')->name('preview');
                Route::get('/{reservation}', 'show')->name('show');
                Route::post('/{reservation}/status', 'status')->name('status');
                Route::post('/{reservation}/pindah', 'move')->name('move');
            });
        });

        Route::middleware('can:ruangan')->group(function () {
            Route::resource('ruangan', Admin\RoomTypeController::class)->parameters(['ruangan' => 'roomType'])->except('show');
            Route::resource('paket', Admin\PackageController::class)->parameters(['paket' => 'package'])->except('show');
            Route::post('/paket/{package}/toggle', [Admin\PackageController::class, 'toggle'])->name('paket.toggle');
        });

        Route::middleware('can:promo')->group(function () {
            Route::post('/promo/pratinjau', [Admin\PromoController::class, 'preview'])->name('promo.preview');
            Route::resource('promo', Admin\PromoController::class)->except('show');
            Route::post('/promo/{promo}/toggle', [Admin\PromoController::class, 'toggle'])->name('promo.toggle');
        });

        Route::middleware('can:event')->group(function () {
            Route::resource('event', Admin\EventController::class)->except('show');
            Route::post('/event/{event}/toggle', [Admin\EventController::class, 'toggle'])->name('event.toggle');
        });

        Route::middleware('can:galeri')->controller(Admin\GalleryController::class)->prefix('galeri')->name('galeri.')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('/', 'store')->name('store');
            Route::get('/{item}/ubah', 'edit')->name('edit');
            Route::put('/{item}', 'update')->name('update');
            Route::post('/{item}/toggle', 'toggle')->name('toggle');
            Route::delete('/{item}', 'destroy')->name('destroy');
        });

        Route::middleware('can:pesan')->controller(Admin\MessageController::class)->prefix('pesan')->name('pesan.')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('/baca-semua', 'readAll')->name('read-all');
            Route::post('/{message}/baca', 'toggleRead')->name('toggle');
            Route::delete('/{message}', 'destroy')->name('destroy');
        });

        Route::middleware('can:pengguna')->group(function () {
            Route::resource('pengguna', Admin\UserController::class)->parameters(['pengguna' => 'user'])->except('show');
            Route::post('/pengguna/{user}/toggle', [Admin\UserController::class, 'toggle'])->name('pengguna.toggle');
        });

        Route::middleware('can:pengaturan')->group(function () {
            Route::get('/pengaturan', [Admin\SettingController::class, 'edit'])->name('pengaturan');
            Route::put('/pengaturan', [Admin\SettingController::class, 'update'])->name('pengaturan.update');
            Route::post('/pengaturan/tes-wa', [Admin\SettingController::class, 'testWa'])->name('pengaturan.tes-wa');
        });
    });
});
