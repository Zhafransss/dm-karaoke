<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Support\Phone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

/** Guest status check by code + phone, no login (FR-RES-13). */
class StatusController extends Controller
{
    public function show(Request $request)
    {
        return view('site.status', ['code' => $request->query('kode', ''), 'phone' => '', 'result' => null, 'searched' => false]);
    }

    public function check(Request $request)
    {
        $key = 'status:'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, config('dmk.rate_limit.status_checks_per_minute'))) {
            return back()->withInput()->withErrors(['code' => 'Terlalu banyak percobaan. Tunggu sebentar lalu coba lagi.']);
        }
        RateLimiter::hit($key, 60);

        $data = $request->validate(
            ['code' => ['required', 'string', 'max:20'], 'phone' => ['required', 'string', 'max:20']],
            ['code.required' => 'Isi kode reservasi.', 'phone.required' => 'Isi nomor WhatsApp.']
        );
        $code = strtoupper(trim($data['code']));
        $r = Reservation::with('roomType', 'room')->where('code', $code)->first();
        // Both code and phone must match; a wrong phone reveals nothing (T-27).
        if ($r && (! $r->phone || Phone::toWa($r->phone) !== Phone::toWa($data['phone']))) {
            $r = null;
        }

        return view('site.status', ['code' => $code, 'phone' => $data['phone'], 'result' => $r, 'searched' => true]);
    }
}
