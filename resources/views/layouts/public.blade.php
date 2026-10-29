@php
    use App\Support\OpHours;
    use App\Support\Phone;
    use App\Support\Settings;
    $biz = config('dmk.business');
    $title = $title ?? 'DM Karaoke & Cafe | Karaoke & Cafe di Rawalo, Banyumas';
    $description = $description ?? 'Ruang karaoke privat dan cafe di Jl. Raya Margasana, Rawalo, Banyumas. Cek jadwal kosong dan reservasi online.';
    $nav = $nav ?? null;
    $openLabel = hl(OpHours::open()).'–'.hl(OpHours::close());
    $links = ['home' => 'Beranda', 'harga' => 'Harga', 'reservasi' => 'Reservasi', 'promo' => 'Promo', 'event' => 'Event', 'galeri' => 'Galeri', 'kontak' => 'Kontak'];
    $ld = [
        '@context' => 'https://schema.org', '@type' => 'EntertainmentBusiness', 'name' => $biz['name'],
        'description' => 'Tempat karaoke privat dan cafe di Rawalo, Banyumas.', 'url' => route('home'),
        'image' => asset('images/logo-full.webp'), 'logo' => asset('images/logo-full.webp'), 'telephone' => '+'.Settings::get('wa_number'),
        'address' => ['@type' => 'PostalAddress', 'streetAddress' => $biz['street'], 'addressLocality' => $biz['locality'], 'addressRegion' => $biz['region'], 'postalCode' => $biz['postal_code'], 'addressCountry' => 'ID'],
        'geo' => ['@type' => 'GeoCoordinates', 'latitude' => $biz['lat'], 'longitude' => $biz['lng']],
        'hasMap' => $biz['maps_link'], 'areaServed' => 'Kabupaten Banyumas', 'priceRange' => 'Rp40.000–Rp2.600.000',
        'openingHoursSpecification' => [['@type' => 'OpeningHoursSpecification', 'dayOfWeek' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'], 'opens' => str_replace('.', ':', hl(OpHours::open())), 'closes' => str_replace('.', ':', hl(OpHours::close()))]],
        'sameAs' => ['https://instagram.com/'.$biz['instagram']],
    ];
@endphp
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }}</title>
    <meta name="description" content="{{ $description }}">
    <meta name="robots" content="{{ ($noindex ?? false) ? 'noindex, nofollow' : 'index, follow' }}">
    <link rel="canonical" href="{{ $canonical ?? url()->current() }}">
    <meta name="theme-color" content="#0a0907">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="id_ID">
    <meta property="og:site_name" content="{{ $biz['name'] }}">
    <meta property="og:title" content="{{ $title }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:url" content="{{ $canonical ?? url()->current() }}">
    <meta property="og:image" content="{{ $ogImage ?? asset('images/logo-full.webp') }}">
    <meta name="twitter:card" content="summary_large_image">
    <link rel="icon" type="image/webp" href="{{ asset('images/logo-mark.webp') }}">
    <script type="application/ld+json">{!! json_encode($ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.analytics')
</head>
<body>
<a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-[100] focus:bg-gold focus:text-gold-on focus:px-4 focus:py-2 focus:rounded-sm">Lewati ke konten</a>

<header class="site-header" x-data="{ open: false }">
    <div class="wrap">
        <div class="flex items-center justify-between gap-6 h-16 xl:h-[72px]">
            <a class="inline-flex items-center gap-3 min-w-0 no-underline" href="{{ route('home') }}" aria-label="DM Karaoke & Cafe, ke beranda">
                <img src="{{ asset('images/logo-mark.webp') }}" alt="" width="37" height="40" class="h-10 w-auto">
                <span class="flex flex-col leading-none"><b class="text-[15px] font-semibold tracking-[.16em] uppercase text-gold">DM Karaoke</b><small class="mt-[5px] text-[10px] tracking-[.3em] uppercase text-ink-muted">&amp; Cafe</small></span>
            </a>
            <nav aria-label="Navigasi utama" class="hidden xl:block">
                <ul class="flex items-center gap-6 m-0 p-0 list-none">
                    @foreach($links as $r => $label)
                        <li><a class="nav-link" href="{{ route($r) }}" @if($nav === $r) aria-current="page" @endif>{{ $label }}</a></li>
                    @endforeach
                </ul>
            </nav>
            <div class="flex items-center gap-3">
                <a class="btn btn-primary btn-sm hidden xl:inline-flex" href="{{ route('reservasi') }}">Reservasi</a>
                <button class="icon-btn xl:hidden w-11 h-11" type="button" @click="open = !open" :aria-expanded="open" aria-controls="mnav" aria-label="Buka menu"><x-icon name="menu" /></button>
            </div>
        </div>
        <div id="mnav" class="xl:hidden border-t border-line pt-4 pb-6" x-show="open" x-cloak>
            <ul class="m-0 mb-4 p-0 list-none">
                @foreach($links as $r => $label)
                    <li><a class="mnav-link" href="{{ route($r) }}" @if($nav === $r) aria-current="page" @endif>{{ $label }}</a></li>
                @endforeach
                <li><a class="mnav-link" href="{{ route('cek-status') }}">Cek status reservasi</a></li>
            </ul>
            <a class="btn btn-primary btn-block" href="{{ route('reservasi') }}">Cek jadwal &amp; reservasi</a>
        </div>
    </div>
</header>

<main id="main" tabindex="-1">
    @yield('content')
</main>

<footer class="bg-surface border-t border-gold-deep pt-16" style="padding-bottom: calc(32px + env(safe-area-inset-bottom))">
    <div class="wrap">
        <div class="grid gap-8 grid-cols-1 sm:grid-cols-2 lg:grid-cols-[4fr_3fr_3fr_3fr]">
            <div>
                <img class="w-[170px] h-auto mb-4" src="{{ asset('images/logo-full.webp') }}" alt="Logo DM Karaoke & Cafe" width="170" height="121" loading="lazy">
                <p class="m-0 text-sm leading-[22px] text-ink-muted">Ruang karaoke privat dan cafe untuk kumpul keluarga, teman, dan rekan kerja.</p>
            </div>
            <div>
                <h3 class="m-0 mb-3 text-xs leading-4 font-medium tracking-[.2em] uppercase text-gold">Kunjungi</h3>
                <p class="m-0 text-sm leading-[22px] text-ink-muted">{{ $biz['address'] }}</p>
                <p class="caption mt-2 mb-0">Plus Code {{ $biz['plus_code'] }}</p>
            </div>
            <div>
                <h3 class="m-0 mb-3 text-xs leading-4 font-medium tracking-[.2em] uppercase text-gold">Reservasi</h3>
                <ul class="m-0 p-0 list-none flex flex-col gap-2 text-sm leading-[22px] text-ink-muted [&_a]:text-ink-muted [&_a]:no-underline [&_a:hover]:text-gold-bright">
                    <li><a href="{{ route('reservasi') }}">Jadwal &amp; reservasi</a></li>
                    <li><a href="{{ route('harga') }}">Daftar harga</a></li>
                    <li><a href="{{ route('cek-status') }}">Cek status reservasi</a></li>
                    <li><a href="{{ route('promo') }}">Promo</a></li>
                    <li>Buka setiap hari {{ $openLabel }}</li>
                </ul>
            </div>
            <div>
                <h3 class="m-0 mb-3 text-xs leading-4 font-medium tracking-[.2em] uppercase text-gold">Hubungi</h3>
                <ul class="m-0 p-0 list-none flex flex-col gap-2 text-sm leading-[22px] text-ink-muted [&_a]:text-ink-muted [&_a]:no-underline [&_a:hover]:text-gold-bright">
                    <li><a href="{{ Phone::waLink('Halo DM Karaoke & Cafe, saya mau tanya.') }}" target="_blank" rel="noopener">WhatsApp {{ Settings::get('wa_display') }}</a></li>
                    <li><a href="https://instagram.com/{{ $biz['instagram'] }}" target="_blank" rel="noopener">Instagram &#64;{{ $biz['instagram'] }}</a></li>
                    <li><a href="{{ $biz['maps_link'] }}" target="_blank" rel="noopener">Petunjuk arah</a></li>
                    <li><a href="{{ route('kontak') }}">Kontak &amp; tentang kami</a></li>
                </ul>
            </div>
        </div>
        <div class="mt-12 pt-6 border-t border-line flex flex-wrap justify-between gap-4 text-xs leading-[18px] text-ink-subtle">
            <span>© {{ date('Y') }} DM Karaoke &amp; Cafe, Rawalo, Banyumas</span>
            <a class="text-ink-subtle" href="{{ route('admin.login') }}" rel="nofollow">Masuk admin</a>
        </div>
    </div>
</footer>

@if($nav !== 'reservasi')
    <a class="fab" href="{{ route('reservasi') }}" aria-label="Cek jadwal dan reservasi"><x-icon name="calendar" /><span class="hidden sm:inline">Reservasi</span></a>
@endif

@include('partials.overlays')
</body>
</html>
