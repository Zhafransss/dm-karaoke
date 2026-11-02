@php
    $user = auth()->user();
    $route = request()->route()?->getName() ?? '';
    $is = fn (string $prefix) => str_starts_with($route, $prefix);
    $item = function (string $perm, string $routeName, string $prefix, string $icon, string $label, int $count = 0, string $countLabel = '') use ($user, $is) {
        if (! $user->hasPerm($perm)) {
            return '';
        }

        return view('partials.admin-nav-item', compact('routeName', 'icon', 'label', 'count', 'countLabel') + ['active' => $is($prefix)])->render();
    };
    $groups = [
        'Operasional' => $item('dashboard', 'admin.dashboard', 'admin.dashboard', 'grid', 'Dashboard')
            .$item('reservasi', 'admin.jadwal', 'admin.jadwal', 'calendar', 'Jadwal')
            .$item('reservasi', 'admin.reservasi.index', 'admin.reservasi', 'ticket', 'Reservasi', $navCounts['waiting'], 'menunggu konfirmasi'),
        'Konten' => $item('promo', 'admin.promo.index', 'admin.promo', 'tag', 'Promo')
            .$item('event', 'admin.event.index', 'admin.event', 'mic', 'Event')
            .$item('galeri', 'admin.galeri.index', 'admin.galeri', 'image', 'Galeri')
            .$item('pesan', 'admin.pesan.index', 'admin.pesan', 'mail', 'Pesan', $navCounts['unread'], 'belum dibaca'),
        'Pengaturan' => $item('ruangan', 'admin.ruangan.index', 'admin.ruangan', 'door', 'Ruangan & harga')
            .$item('ruangan', 'admin.paket.index', 'admin.paket', 'box', 'Paket')
            .$item('pengguna', 'admin.pengguna.index', 'admin.pengguna', 'users', 'Pengguna')
            .$item('pengaturan', 'admin.pengaturan', 'admin.pengaturan', 'settings', 'Pengaturan'),
    ];
@endphp
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title ?? 'Admin' }} | Admin DM Karaoke</title>
    <link rel="icon" type="image/webp" href="{{ asset('images/logo-mark.webp') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body x-data="{ side: false }">
<div class="min-h-screen lg:grid lg:grid-cols-[264px_minmax(0,1fr)]">
    <div class="fixed inset-0 z-[70] lg:hidden" style="background: rgba(10,9,7,.72)" x-show="side" x-cloak @click="side = false"></div>
    <aside class="fixed lg:sticky top-0 left-0 z-[80] w-[280px] lg:w-auto h-screen flex flex-col gap-6 overflow-y-auto bg-surface border-r border-line px-4 py-6 shadow-modal lg:shadow-none -translate-x-full lg:translate-x-0 transition-transform"
           :class="side && '!translate-x-0'" aria-label="Menu admin">
        <a class="inline-flex items-center gap-3 px-2 pb-4 border-b border-line no-underline" href="{{ route('admin.dashboard') }}">
            <img src="{{ asset('images/logo-mark.webp') }}" alt="" width="33" height="36" class="h-9 w-auto">
            <span class="flex flex-col leading-none"><b class="text-[15px] font-semibold tracking-[.16em] uppercase text-gold">DM Karaoke</b><small class="mt-[5px] text-[10px] tracking-[.3em] uppercase text-ink-muted">Panel admin</small></span>
        </a>
        @foreach($groups as $label => $html)
            @if($html)<div><p class="side-label">{{ $label }}</p><ul class="m-0 p-0 list-none flex flex-col gap-0.5">{!! $html !!}</ul></div>@endif
        @endforeach
        <div class="mt-auto flex flex-col gap-3 pt-4 border-t border-line">
            <div class="flex items-center gap-3 px-2 min-w-0">
                <span class="avatar">{{ mb_substr($user->name, 0, 1) }}</span>
                <div class="min-w-0"><b class="block text-sm leading-5 font-medium truncate">{{ $user->name }}</b><span class="text-xs leading-4 text-ink-subtle">{{ $user->roleLabel() }}</span></div>
            </div>
            <a class="side-link !text-sm !py-2" href="{{ route('home') }}" target="_blank"><x-icon name="globe" />Lihat website</a>
            <form method="POST" action="{{ route('admin.logout') }}">@csrf<button class="side-link !text-sm !py-2 w-full bg-transparent border-0 cursor-pointer" type="submit"><x-icon name="logout" />Keluar</button></form>
        </div>
    </aside>

    <div class="min-w-0 flex flex-col" id="main">
        <header class="sticky top-0 z-30 flex flex-wrap items-center justify-between gap-4 px-4 py-4 md:px-8 md:py-6 border-b border-line backdrop-blur" style="background: rgba(10,9,7,.94)">
            <div class="flex items-center gap-3 min-w-0">
                <button class="icon-btn lg:hidden" type="button" @click="side = true" aria-label="Buka menu admin"><x-icon name="menu" /></button>
                <div class="min-w-0">
                    <h1 class="m-0 font-display text-[28px] leading-8 md:text-4xl md:leading-10 font-medium">{{ $title ?? 'Admin' }}</h1>
                    @isset($subtitle)<p class="mt-0.5 mb-0 text-sm leading-5 text-ink-subtle">{{ $subtitle }}</p>@endisset
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-3">@yield('actions')</div>
        </header>
        <div class="flex flex-col gap-6 px-4 pt-6 pb-16 md:px-8">
            @if($errors->any() && ! ($hideErrorSummary ?? false))
                <div class="notice" role="alert">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
            @endif
            @yield('content')
        </div>
    </div>
</div>
@include('partials.cropper')
@include('partials.overlays')
</body>
</html>
