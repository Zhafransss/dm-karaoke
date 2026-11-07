@extends('layouts.public', ['nav' => 'kontak', 'title' => 'Kontak & Lokasi Karaoke di Rawalo | DM Karaoke & Cafe', 'description' => 'Alamat Jl. Raya Margasana, Menganti, Rawalo, Banyumas. Peta lokasi, jam buka, WhatsApp, dan form pesan DM Karaoke & Cafe.', 'breadcrumbs' => [['Kontak', route('kontak')]]])

@php
    use App\Support\OpHours;
    use App\Support\Phone;
    use App\Support\Settings;
    $biz = config('dmk.business');
    $openLabel = hl(OpHours::open()).'–'.hl(OpHours::close());
@endphp

@section('content')
<x-page-head crumb="Kontak" eyebrow="Kontak" title="Kami tunggu <em>kedatanganmu</em>" lead="Hubungi kami lewat WhatsApp, datang langsung, atau tinggalkan pesan dan saran." />

{{-- FR-PUB-06 --}}
<section class="band" aria-labelledby="about-title"><div class="wrap">
    <div class="grid gap-8 lg:gap-16 lg:grid-cols-[6fr_5fr] items-center">
        <div>
            <x-heading eyebrow="Tentang kami" title="Tempat bernyanyi dan <em>berkumpul</em>" :center="false" id="about-title" />
            <p class="m-0 mb-4 text-[17px] leading-7 text-ink-muted max-w-[60ch]">DM Karaoke &amp; Cafe adalah tempat karaoke dan cafe di Jl. Raya Margasana, Rawalo, Banyumas. Kami menyediakan ruang karaoke privat untuk kumpul keluarga, merayakan ulang tahun, atau melepas penat bersama teman dan rekan kerja.</p>
            <p class="m-0 mb-4 text-[17px] leading-7 text-ink-muted max-w-[60ch]">Setiap ruangan dilengkapi sound system dan mic nirkabel, dan menu cafe kami bisa dipesan langsung dari ruangan.</p>
            <div class="flex flex-wrap items-center gap-3 mt-6"><a class="btn btn-primary" href="{{ route('reservasi') }}">Cek jadwal &amp; reservasi</a><a class="btn btn-ghost" href="{{ route('galeri') }}">Lihat galeri <x-icon name="right" class="ic-sm" /></a></div>
        </div>
        <div class="w-full max-w-[440px] mx-auto p-1.5 border border-gold-deep bg-bg rounded-t-arch rounded-b-sm overflow-hidden">
            <img src="{{ asset('images/about.webp') }}" alt="Meja resepsionis DM Karaoke & Cafe dengan dinding logo" loading="lazy" class="w-full aspect-[4/5] object-cover rounded-t-arch rounded-b-sm">
        </div>
    </div>
</div></section>

<section class="band band-alt" aria-labelledby="info-title"><div class="wrap">
    <h2 id="info-title" class="sr-only">Informasi kontak</h2>
    <div class="grid gap-6 lg:grid-cols-3">
        <article class="info-card">
            <x-icon name="chat" class="text-gold !w-[26px] !h-[26px]" /><h3>WhatsApp</h3>
            <p class="font-display !text-[30px] !leading-[1.1] font-semibold tabular select-all">{{ Settings::get('wa_display') }}</p>
            <p class="text-ink-muted">Pertanyaan, acara rombongan, dan info lainnya.</p>
            <div class="flex flex-wrap gap-3 mt-auto pt-2">
                <a class="btn btn-primary btn-sm" href="{{ Phone::waLink('Halo DM Karaoke & Cafe, saya mau tanya.') }}" target="_blank" rel="noopener">Chat sekarang</a>
                <button class="btn btn-quiet btn-sm" type="button" onclick="copyText(@js(Settings::get('wa_display')), this)"><x-icon name="copy" class="ic-sm" />Salin</button>
            </div>
        </article>
        <article class="info-card">
            <x-icon name="pin" class="text-gold !w-[26px] !h-[26px]" /><h3>Alamat</h3>
            <p>{{ $biz['address'] }}</p><p class="caption">Plus Code {{ $biz['plus_code'] }}</p>
            <div class="flex flex-wrap gap-3 mt-auto pt-2">
                <a class="btn btn-outline btn-sm" href="{{ $biz['maps_link'] }}" target="_blank" rel="noopener"><x-icon name="nav" class="ic-sm" />Petunjuk arah</a>
                <button class="btn btn-quiet btn-sm" type="button" onclick="copyText(@js($biz['address']), this)"><x-icon name="copy" class="ic-sm" />Salin</button>
            </div>
        </article>
        <article class="info-card">
            <x-icon name="clock" class="text-gold !w-[26px] !h-[26px]" /><h3>Jam buka</h3>
            <p class="font-display !text-[26px] !leading-[1.1] font-semibold">Setiap hari, {{ $openLabel }}</p>
            <p class="text-ink-muted">Reservasi online bisa dibuat kapan saja. Instagram <a href="https://instagram.com/{{ $biz['instagram'] }}" target="_blank" rel="noopener">&#64;{{ $biz['instagram'] }}</a>.</p>
            <div class="flex flex-wrap gap-3 mt-auto pt-2"><a class="btn btn-outline btn-sm" href="{{ route('reservasi') }}">Cek jadwal kosong</a></div>
        </article>
    </div>
    <div class="mt-6"><x-map /></div>
</div></section>

<section class="band scroll-mt-20" id="pesan" aria-labelledby="msg-title"><div class="wrap"><div class="narrow">
    <x-heading eyebrow="Pesan & Saran" title="Ceritakan <em>pengalamanmu</em>" lead="Saran, kritik, atau pertanyaan akan dibaca langsung oleh pengelola DM Karaoke." id="msg-title" />
    <div class="panel">
        @if(session('message_sent'))
            <div class="flex flex-col items-center text-center gap-3 py-12 px-6" role="status">
                <x-icon name="check" class="!w-10 !h-10 text-success" />
                <h3 class="m-0 font-display text-[32px] leading-[38px] font-semibold">Terima kasih{{ is_string(session('message_sent')) ? ', '.session('message_sent') : '' }}</h3>
                <p class="m-0 muted">Pesanmu sudah kami terima dan akan dibaca oleh pengelola.</p>
                <a class="btn btn-outline btn-sm" href="{{ route('kontak') }}#pesan">Kirim pesan lain</a>
            </div>
        @else
            <form method="POST" action="{{ route('kontak.store') }}" class="grid gap-x-4 gap-y-6 sm:grid-cols-2" novalidate>
                @csrf
                <div class="hidden" aria-hidden="true"><label>Website <input name="website" tabindex="-1" autocomplete="off"></label></div>
                <div class="field">
                    <label class="label" for="m-name">Nama</label>
                    <input class="input" id="m-name" name="name" value="{{ old('name') }}" autocomplete="name" placeholder="Nama kamu" required maxlength="100" @error('name') aria-invalid="true" @enderror>
                    @error('name')<span class="err">{{ $message }}</span>@enderror
                </div>
                <div class="field">
                    <label class="label" for="m-contact">WhatsApp atau email (opsional)</label>
                    <input class="input" id="m-contact" name="contact" value="{{ old('contact') }}" placeholder="Agar kami bisa membalas" maxlength="120">
                </div>
                <div class="field sm:col-span-2">
                    <label class="label" for="m-kind">Jenis pesan</label>
                    <select class="input" id="m-kind" name="kind">@foreach(config('dmk.message_kinds') as $k)<option @selected(old('kind') === $k)>{{ $k }}</option>@endforeach</select>
                </div>
                <div class="field sm:col-span-2">
                    <label class="label" for="m-message">Pesan</label>
                    <textarea class="input" id="m-message" name="message" rows="5" placeholder="Tulis pesanmu di sini" required maxlength="2000" @error('message') aria-invalid="true" @enderror>{{ old('message') }}</textarea>
                    @error('message')<span class="err">{{ $message }}</span>@enderror
                </div>
                <div class="sm:col-span-2 flex justify-end"><button class="btn btn-primary" type="submit">Kirim pesan</button></div>
            </form>
        @endif
    </div>
</div></div></section>
@endsection
