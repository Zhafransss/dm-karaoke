<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/** Username + password login with throttling (FR-ADM-01). */
class AuthController extends Controller
{
    public function show()
    {
        return view('admin.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate(
            ['username' => ['required', 'string', 'max:30'], 'password' => ['required', 'string']],
            ['username.required' => 'Isi username dan kata sandi.', 'password.required' => 'Isi username dan kata sandi.']
        );
        $key = 'login:'.Str::lower($data['username']).'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, config('dmk.rate_limit.login_per_minute'))) {
            $s = RateLimiter::availableIn($key);

            return back()->onlyInput('username')->withErrors(['username' => "Terlalu banyak percobaan. Coba lagi dalam {$s} detik."]);
        }

        if (! Auth::attempt(['username' => Str::lower($data['username']), 'password' => $data['password']], $request->boolean('remember'))) {
            RateLimiter::hit($key, 60);

            return back()->onlyInput('username')->withErrors(['username' => 'Username atau kata sandi salah. Periksa lagi lalu coba masuk.']);
        }
        RateLimiter::clear($key);

        /** @var User $user */
        $user = Auth::user();
        if (! $user->active) {
            Auth::logout();

            return back()->onlyInput('username')->withErrors(['username' => 'Akun ini sedang dinonaktifkan. Hubungi Super Admin.']);
        }
        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->save();

        return redirect()->intended(route('admin.dashboard'))->with('ok', 'Selamat datang, '.$user->name);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login')->with('ok', 'Kamu sudah keluar.');
    }
}
