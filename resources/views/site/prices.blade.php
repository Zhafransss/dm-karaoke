@extends('layouts.public', [
    'nav' => 'harga',
    'title' => 'Daftar Harga | DM Karaoke & Cafe',
    'description' => 'Daftar harga paket Standard, VIP, dan Master Room serta tarif sewa ruangan per jam di DM Karaoke & Cafe Rawalo, Banyumas.',
])

@php
    use App\Support\OpHours;
    use App\Support\QuotePresenter;
    $open = OpHours::open(); $dayEnd = OpHours::dayEnd(); $close = OpHours::close();
    $rentable = $types->where('rentable', true);
    $vip = $rentable->firstWhere('slug', 'vip-room') ?? $rentable->last();
@endphp

@section('content')
<x-page-head crumb="Harga" eyebrow="Daftar harga" title="Harga <em>paket</em> dan sewa ruangan" :lead="($types->contains('prepaid', true) ? 'Harga dibayar langsung di tempat, kecuali ruangan yang wajib lunas di awal.' : 'Semua harga dibayar langsung di tempat.').' Pilih paket atau sewa ruangan saja, lalu cek jam yang masih kosong.'" />

{{-- FR-PUB-02 --}}
<section class="band"><div class="wrap">
    <p class="notice notice-info max-w-[760px] mx-auto mb-16 text-center"><b>Khusus usia 21 tahun ke atas.</b> Layanan pemandu lagu (LC) dan paket yang memuat minuman beralkohol hanya untuk tamu berusia 21 tahun ke atas.</p>
    <x-heading eyebrow="Sewa ruangan saja" title="Bayar <em>per jam</em>" lead="Tanpa paket. Buka setiap hari {{ hl($open) }}–{{ hl($close) }}." id="rent-title" />
    @if($rentable->isNotEmpty())
    <div class="table-wrap mt-2">
        <table class="table price-table">
            <thead><tr>
                <th scope="col">Ruangan</th>
                <th scope="col">Siang {{ hl($open) }}–{{ hl($dayEnd) }}<small>dengan LC dan deposit</small></th>
                <th scope="col">Siang {{ hl($open) }}–{{ hl($dayEnd) }}<small>tanpa LC dan deposit</small></th>
                <th scope="col">Malam {{ hl($dayEnd) }}–{{ hl($close) }}<small>dengan atau tanpa LC</small></th>
            </tr></thead>
            <tbody>
                @foreach($rentable as $t)
                    <tr>
                        <th scope="row">{{ $t->name }}@if($t->publicCap())<small class="block">Maks. {{ $t->publicCap() }} orang</small>@endif</th>
                        <td><b class="text-success font-semibold">Gratis</b></td>
                        <td>{{ rp($t->rate_day) }}<small>per jam</small></td>
                        <td>{{ rp($t->rate_night) }}<small>per jam</small></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
    <ul class="max-w-[760px] mx-auto mt-6 pl-5 flex flex-col gap-2 text-sm leading-[22px] text-ink-muted list-disc">
        @if($vip)<li>Booking yang melewati jam {{ hl($dayEnd) }} dihitung per jam sesuai tarifnya. Contoh {{ $vip->name }} {{ hl($dayEnd - 1) }}–{{ hl($dayEnd + 2) }} tanpa LC: {{ rp($vip->rate_day) }} + 2 × {{ rp($vip->rate_night) }} = {{ rp($vip->rate_day + 2 * $vip->rate_night) }}.</li>@endif
        @foreach($types->filter(fn ($t) => $t->rentable || $t->prepaid) as $dt)<li><b class="font-medium text-ink">{{ $dt->name }}:</b> {{ $dt->depositText() }}</li>@endforeach
        <li>LC (pemandu lagu) bersifat pilihan, tidak wajib. Penambahan jam dilakukan langsung bersama admin.</li>
    </ul>
    <div class="flex justify-center mt-8"><a class="btn btn-primary" href="{{ route('reservasi') }}">Cek jadwal &amp; reservasi</a></div>
</div></section>

@foreach($types as $i => $t)
    @php($ps = $t->activePackages->where('family', false))
    @continue($ps->isEmpty())
    <section class="band {{ $i % 2 ? '' : 'band-alt' }}" aria-labelledby="hg-{{ $t->id }}"><div class="wrap">
        <x-heading eyebrow="Paket" :title="e($t->name)" id="hg-{{ $t->id }}"
            :lead="$t->rentable ? 'Setiap paket sudah termasuk room '.$ps->pluck('hours')->unique()->sort()->implode(' atau ').' jam sesuai paket.'.($t->publicCap() ? ' Kapasitas maksimal '.$t->publicCap().' orang.' : '') : 'Hanya tersedia dalam bentuk paket. Setiap paket termasuk 3 jam room, 3 LC, 3 Le Minerale, 2 Hexos, food platter, dan tissue.'" />
        @foreach($ps->groupBy('brand') as $brand => $list)
            <div class="{{ $loop->first ? '' : 'mt-12' }}">
                <h3 class="m-0 mb-4 font-display text-[28px] leading-8 font-semibold text-gold">{{ $brand }}</h3>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 {{ $list->count() === 4 ? 'xl:grid-cols-4' : '' }}">
                    @foreach($list as $p)<x-pkg-card :pkg="$p" :type-slug="$t->slug" :promo="$promoSvc->firstFor([$p->pkgKey()], $today)" />@endforeach
                </div>
            </div>
        @endforeach
    </div></section>
@endforeach

@if($family->isNotEmpty())
<section class="band" aria-labelledby="hg-family"><div class="wrap">
    <x-heading eyebrow="Paket Family" title="Nyanyi bareng <em>keluarga</em> di siang hari" lead="Mulai pukul 15.00 selama 3 jam, cocok untuk keluarga dan teman." id="hg-family" />
    <div class="grid gap-4 sm:grid-cols-2 max-w-[700px] mx-auto">
        @foreach($family as $p)
            <div class="flex flex-col gap-2"><span class="text-[11px] leading-4 tracking-[.18em] uppercase text-gold">{{ $p->roomType->name }}</span><x-pkg-card :pkg="$p" :promo="$promoSvc->firstFor([$p->pkgKey()], $today)" /></div>
        @endforeach
    </div>
</div></section>
@endif

<section class="band band-alt"><div class="wrap flex flex-wrap justify-center gap-3">
    <a class="btn btn-primary" href="{{ route('reservasi') }}">Mulai reservasi</a><a class="btn btn-outline" href="{{ route('promo') }}">Lihat promo</a>
</div></section>
@endsection
