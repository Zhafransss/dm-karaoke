<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex, nofollow">
    <title>Masuk Admin | DM Karaoke & Cafe</title>
    <link rel="icon" type="image/webp" href="{{ asset('images/logo-mark.webp') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<main class="relative min-h-screen grid place-items-center bg-bg bg-spotlight px-4 py-12 overflow-hidden" id="main">
    <div class="relative z-[1] w-full max-w-[420px] flex flex-col gap-6 bg-surface-raised border border-gold-deep rounded-lg px-8 pt-12 pb-8 shadow-modal" x-data="{ show: false }">
        <span class="absolute inset-1.5 border border-gold-deep rounded opacity-40 pointer-events-none"></span>
        <img src="{{ asset('images/logo-full.webp') }}" alt="DM Karaoke & Cafe" width="168" height="120" class="w-[168px] mx-auto">
        <h1 class="m-0 text-center font-display text-[32px] leading-[38px] font-semibold">Masuk admin</h1>
        <form method="POST" action="{{ route('admin.login.attempt') }}" class="flex flex-col gap-4" novalidate>
            @csrf
            <div class="field">
                <label class="label" for="l-user">Username</label>
                <input class="input" id="l-user" name="username" value="{{ old('username') }}" autocomplete="username" autocapitalize="none" required autofocus>
            </div>
            <div class="field">
                <label class="label" for="l-pass">Kata sandi</label>
                <div class="relative">
                    <input class="input pr-[52px]" id="l-pass" name="password" :type="show ? 'text' : 'password'" type="password" autocomplete="current-password" required>
                    <button type="button" class="absolute right-1.5 top-1.5 w-9 h-9 grid place-items-center bg-transparent border-0 text-ink-muted cursor-pointer hover:text-gold" @click="show = !show" :aria-label="show ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'">
                        <x-icon name="eye" x-show="!show" /><x-icon name="eyeOff" x-show="show" x-cloak />
                    </button>
                </div>
            </div>
            <label class="switch"><input type="checkbox" name="remember" value="1"> Ingat saya di perangkat ini</label>
            @error('username')<p class="notice" role="alert">{{ $message }}</p>@enderror
            <button class="btn btn-primary btn-block" type="submit"><x-icon name="lock" class="ic-sm" />Masuk</button>
        </form>
        <a class="btn btn-ghost self-center" href="{{ route('home') }}"><x-icon name="left" class="ic-sm" />Kembali ke website</a>
    </div>
</main>
@include('partials.overlays')
</body>
</html>
