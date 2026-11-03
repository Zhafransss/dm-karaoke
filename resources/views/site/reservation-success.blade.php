@extends('layouts.public', ['nav' => 'reservasi', 'title' => 'Reservasi Terkirim | DM Karaoke & Cafe', 'noindex' => true])

@section('content')
<x-page-head crumb="Reservasi" eyebrow="Reservasi terkirim" title="Sampai <em>jumpa</em> di DM Karaoke" />

<section class="band"><div class="wrap"><div class="narrow">
    <div class="panel flex flex-col items-center text-center gap-4" role="status">
        <x-icon name="checkCircle" class="!w-11 !h-11 text-success" />
        <h2 class="m-0 font-display text-[40px] leading-[44px] font-medium">Reservasi kamu sudah masuk</h2>
        <div><span class="caption">Kode reservasi</span><div class="code-big">{{ $r->code }}</div></div>
        <dl class="kv text-left w-full max-w-[440px]">
            <dt>Nama</dt><dd>{{ $r->name }}</dd>
            <dt>Ruangan</dt><dd>{{ $r->roomType->name }}</dd>
            <dt>Pesanan</dt><dd>{{ $r->orderLabel() }}</dd>
            <dt>Tanggal</dt><dd>{{ tgl($r->date) }}</dd>
            <dt>Jam</dt><dd>{{ $r->timeLabel() }} ({{ $r->hours }} jam)</dd>
            <dt>Jumlah orang</dt><dd>{{ $r->pax }} orang</dd>
            <dt>Status</dt><dd><x-status-badge :status="$r->status" /></dd>
        </dl>
        <div class="w-full max-w-[440px] text-left"><x-price-summary :r="$r" /></div>
        <p class="notice notice-info text-left max-w-[520px]">Detail reservasi dikirim ke WhatsApp <b>{{ $r->phone }}</b>. Admin juga menerima notifikasi dan akan segera mengonfirmasi jadwalmu. Simpan kode di atas untuk cek status. @if($r->roomType->prepaid){{ $r->roomType->name }} wajib lunas di awal (tanpa deposit); hubungi admin lewat WhatsApp untuk pelunasan agar jadwalmu dikonfirmasi.@else Pembayaran dilakukan di tempat.@endif</p>
        <div class="flex flex-wrap justify-center gap-3">
            <a class="btn btn-primary" href="{{ $waLink }}" target="_blank" rel="noopener"><x-icon name="chat" class="ic-sm" />Hubungi via WhatsApp</a>
            <a class="btn btn-outline" href="{{ route('cek-status', ['kode' => $r->code]) }}">Cek status</a>
            <a class="btn btn-ghost" href="{{ route('reservasi') }}">Buat reservasi lain</a>
        </div>
    </div>
</div></div></section>
@endsection
