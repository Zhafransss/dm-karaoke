@extends('layouts.public', ['nav' => 'home'])

@php
    use App\Support\OpHours;
    use App\Support\Phone;
    use App\Support\Settings;
    $openLabel = hl(OpHours::open()).'–'.hl(OpHours::close());
@endphp

@section('content')
{{-- FR-PUB-01: hero, rooms with "mulai dari" price, running promos, upcoming events, gallery, WhatsApp --}}
<section class="relative overflow-hidden text-center bg-bg" aria-labelledby="hero-title">
    <img src="{{ asset('images/hero.webp') }}" alt="" class="absolute inset-0 w-full h-full object-cover opacity-30" fetchpriority="high">
    <div class="absolute inset-0 z-[1]" style="background: radial-gradient(ellipse at 50% 0%, rgba(201,165,76,.18) 0%, rgba(10,9,7,0) 60%), linear-gradient(180deg, rgba(10,9,7,.55) 0%, rgba(10,9,7,.7) 55%, #0a0907 100%)"></div>
    <svg class="absolute left-1/2 -bottom-px w-[1200px] h-[360px] -translate-x-1/2 z-[1] pointer-events-none" viewBox="0 0 1200 360" aria-hidden="true" fill="none" stroke="#8c6d2c" opacity=".5">
        <circle cx="600" cy="360" r="160"/><circle cx="600" cy="360" r="240"/><circle cx="600" cy="360" r="320"/><circle cx="600" cy="360" r="400"/><circle cx="600" cy="360" r="480"/>
    </svg>
    <div class="wrap relative z-[2]">
        <div class="flex flex-col items-center gap-6 pt-16 pb-12 md:pt-32 md:pb-24">
            <img src="{{ asset('images/logo-mark.webp') }}" alt="" width="84" height="91" class="w-16 md:w-[84px] h-auto" style="filter: drop-shadow(0 0 24px rgba(201,165,76,.35))">
            <h1 id="hero-title" class="m-0 max-w-[15ch] font-display font-medium text-[52px] md:text-[88px] leading-[.95] tracking-[-.01em]" style="text-wrap: balance">
                <span class="block mb-6 font-sans text-xs leading-4 font-medium tracking-[.28em] uppercase text-gold">DM Karaoke &amp; Cafe · Rawalo, Banyumas</span>
                Panggung malam ini <em>milikmu</em>
            </h1>
            <p class="m-0 max-w-[48ch] text-base leading-[26px] md:text-lg md:leading-[30px] text-ink-muted">Ruang karaoke privat untuk keluarga, teman, dan rekan kerja. Cek jam yang masih kosong lalu reservasi langsung dari website.</p>
            <div class="flex flex-wrap justify-center gap-4 mt-2">
                <a class="btn btn-primary" href="{{ route('reservasi') }}">Cek jadwal &amp; reservasi</a>
                <a class="btn btn-outline" href="{{ route('harga') }}">Lihat daftar harga</a>
            </div>
            <ul class="flex flex-wrap justify-center gap-x-8 gap-y-3 mt-4 p-0 list-none text-xs leading-4 tracking-[.2em] uppercase text-ink-subtle [&>li]:inline-flex [&>li]:items-center [&>li]:gap-2 [&>li]:before:content-[''] [&>li]:before:w-[5px] [&>li]:before:h-[5px] [&>li]:before:bg-gold [&>li]:before:rotate-45">
                <li>Ruang privat</li><li>Cafe &amp; camilan</li><li>Buka {{ $openLabel }}</li>
            </ul>
        </div>
    </div>
</section>

@if($promos->isNotEmpty())
<section class="band band-alt" aria-labelledby="promo-title"><div class="wrap">
    <x-heading eyebrow="Promo berjalan" title="Lebih <em>hemat</em> minggu ini" lead="Harga promo otomatis terpasang saat kamu memilih jadwal." id="promo-title" />
    <div class="grid gap-6 lg:grid-cols-2">@foreach($promos as $p)<x-promo-card :promo="$p" />@endforeach</div>
    <div class="flex justify-center mt-8"><a class="btn btn-outline" href="{{ route('promo') }}">Semua promo</a></div>
</div></section>
@endif

<section class="band" aria-labelledby="rooms-title"><div class="wrap">
    <x-heading eyebrow="Pilihan ruangan" title="Pilih ruangan <em>sesuai</em> rombonganmu" lead="Sewa per jam atau pilih paket. Semua dibayar langsung di tempat." id="rooms-title" />
    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        @foreach($types as $t)
            <x-room-card :type="$t" :promo="$promoSvc->firstFor([$t->rentKey(), ...$t->activePackages->map->pkgKey()], $today)" />
        @endforeach
    </div>
    <div class="flex justify-center mt-8"><a class="btn btn-outline" href="{{ route('harga') }}">Lihat daftar harga lengkap</a></div>
</div></section>

<section class="band band-alt" aria-labelledby="how-title"><div class="wrap">
    <div class="grid gap-8 lg:gap-16 lg:grid-cols-[5fr_7fr] items-start">
        <div>
            <x-heading eyebrow="Reservasi online" title="Booking dalam <em>tiga</em> langkah" :center="false" id="how-title" />
            <ol class="m-0 p-0 list-none flex flex-col gap-6 [counter-reset:step]">
                @foreach([
                    ['Pilih tanggal dan jam yang kosong', 'Jadwal setiap ruangan tampil langsung, jam yang sudah terisi otomatis tertulis penuh.'],
                    ['Isi nama dan nomor WhatsApp', 'Total harga, termasuk promo yang berlaku, langsung terlihat sebelum kamu mengirim.'],
                    ['Terima konfirmasi lewat WhatsApp', 'Kamu mendapat kode reservasi, lalu admin mengonfirmasi jadwalmu.'],
                ] as $i => [$h, $p])
                    <li class="grid grid-cols-[56px_1fr] gap-4">
                        <span class="font-display text-[40px] leading-none font-medium text-gold">0{{ $i + 1 }}</span>
                        <div><h3 class="m-0 mb-1 text-base leading-6 font-medium tracking-[.04em]">{{ $h }}</h3><p class="m-0 text-sm leading-[22px] text-ink-muted">{{ $p }}</p></div>
                    </li>
                @endforeach
            </ol>
            <div class="flex flex-wrap items-center gap-3 mt-8">
                <a class="btn btn-primary" href="{{ route('reservasi') }}">Mulai reservasi</a>
                <a class="btn btn-ghost" href="{{ route('cek-status') }}">Cek status reservasi <x-icon name="right" class="ic-sm" /></a>
            </div>
        </div>
        <div class="grid gap-6">
            @foreach([['mic', 'Ruang privat', 'Ruangan tertutup untuk rombongan kecil sampai besar, jadi kamu bebas bernyanyi.'], ['speaker', 'Suara jernih', 'Sound system dan mic nirkabel yang dirawat rutin.'], ['cup', 'Cafe di tempat', 'Kopi, minuman segar, dan makanan bisa dipesan langsung ke ruanganmu.']] as [$ic, $h, $p])
                <article class="flex flex-col gap-3 pt-6 border-t border-gold-deep">
                    <x-icon :name="$ic" class="text-gold !w-7 !h-7" />
                    <h3 class="m-0 font-display text-[28px] leading-[34px] font-semibold">{{ $h }}</h3>
                    <p class="m-0 text-[15px] leading-6 text-ink-muted">{{ $p }}</p>
                </article>
            @endforeach
        </div>
    </div>
</div></section>

@if($events->isNotEmpty())
<section class="band" aria-labelledby="ev-title"><div class="wrap">
    <x-heading eyebrow="Event" title="Acara <em>mendatang</em>" lead="Catat tanggalnya dan reservasi ruangan lebih awal." id="ev-title" />
    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">@foreach($events as $e)<x-event-card :event="$e" />@endforeach</div>
    <div class="flex justify-center mt-8"><a class="btn btn-outline" href="{{ route('event') }}">Semua event</a></div>
</div></section>
@endif

<section class="band {{ $events->isNotEmpty() ? 'band-alt' : '' }}" aria-labelledby="gal-title"><div class="wrap">
    <x-heading eyebrow="Galeri" title="Intip suasana <em>DM Karaoke</em>" lead="Foto dan video ruangan, suasana, dan momen seru di DM Karaoke." id="gal-title" />
    @if($gallery->isNotEmpty())
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($gallery as $g)
                <a class="gal-item !mb-0 aspect-[4/3]" href="{{ route('galeri') }}">
                    @if($g->isVideo())<x-video-thumb :path="$g->video" class="absolute inset-0 !h-full !aspect-auto" />@else<x-img :path="$g->image" :alt="$g->caption.' di DM Karaoke & Cafe'" class="absolute inset-0 !h-full object-cover" />@endif
                    <span class="gal-cap"><span>{{ $g->category }}</span><b>{{ $g->caption }}</b></span>
                </a>
            @endforeach
        </div>
    @else
        <div class="empty">Belum ada foto atau video yang ditampilkan.</div>
    @endif
    <div class="flex justify-center mt-8"><a class="btn btn-outline" href="{{ route('galeri') }}">Lihat semua foto &amp; video</a></div>
</div></section>

<section class="band {{ $events->isNotEmpty() ? '' : 'band-alt' }}" aria-labelledby="loc-title"><div class="wrap">
    <x-heading eyebrow="Lokasi" title="Mudah <em>ditemukan</em> di Rawalo" id="loc-title" />
    <div class="grid gap-8 lg:grid-cols-[7fr_5fr] items-stretch">
        <x-map />
        <div class="panel flex flex-col gap-6">
            <div class="info-row"><span class="ic-wrap"><x-icon name="pin" /></span><div><h3>Alamat</h3><p>{{ config('dmk.business.address') }}</p></div></div>
            <div class="info-row"><span class="ic-wrap"><x-icon name="chat" /></span><div><h3>WhatsApp</h3><p class="tabular select-all">{{ Settings::get('wa_display') }}</p></div></div>
            <div class="info-row"><span class="ic-wrap"><x-icon name="clock" /></span><div><h3>Jam buka</h3><p>Setiap hari, {{ $openLabel }}</p></div></div>
            <div class="flex flex-wrap gap-3">
                <a class="btn btn-primary btn-sm" href="{{ Phone::waLink('Halo DM Karaoke & Cafe, saya mau tanya.') }}" target="_blank" rel="noopener"><x-icon name="chat" class="ic-sm" />Chat WhatsApp</a>
                <a class="btn btn-outline btn-sm" href="{{ config('dmk.business.maps_link') }}" target="_blank" rel="noopener"><x-icon name="nav" class="ic-sm" />Petunjuk arah</a>
            </div>
        </div>
    </div>
</div></section>
@endsection
