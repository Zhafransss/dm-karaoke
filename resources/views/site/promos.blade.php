@extends('layouts.public', ['nav' => 'promo', 'title' => 'Promo Karaoke Rawalo Banyumas | DM Karaoke & Cafe', 'description' => 'Lihat promo karaoke yang sedang berjalan dan yang akan datang di DM Karaoke & Cafe Rawalo, Banyumas. Harga promo otomatis terpasang saat reservasi.', 'breadcrumbs' => [['Promo', route('promo')]]])

@section('content')
<x-page-head crumb="Promo" eyebrow="Promo" title="Promo yang <em>sedang berjalan</em>" lead="Harga promo otomatis terpasang saat kamu memilih jadwal reservasi." />

{{-- FR-PUB-03 --}}
<section class="band"><div class="wrap">
    @if($running->isNotEmpty())
        <h2 class="sub-h">Sedang berlangsung</h2>
        <div class="grid gap-6 lg:grid-cols-2">@foreach($running as $p)<x-promo-card :promo="$p" />@endforeach</div>
    @else
        <div class="empty"><x-icon name="tag" class="!w-8 !h-8 text-gold mx-auto mb-3" /><p class="m-0">Belum ada promo yang berjalan saat ini. Pantau terus halaman ini ya.</p></div>
    @endif
    @if($soon->isNotEmpty())
        <h2 class="sub-h mt-16">Segera hadir</h2>
        <div class="grid gap-6 lg:grid-cols-2">@foreach($soon as $p)<x-promo-card :promo="$p" />@endforeach</div>
    @endif
    <p class="caption text-center mt-8">Satu reservasi memakai satu promo. Harga promo mengikuti tanggal dan jam kunjungan, bukan tanggal pemesanan.</p>
</div></section>
@endsection
