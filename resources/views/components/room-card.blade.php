@props(['type', 'promo' => null, 'date' => null])
@php
    $t = $type;
    $minPkg = $t->minPackagePrice();
    $bookUrl = route('reservasi', array_filter(['tipe' => $t->slug, 'tanggal' => $date]));
@endphp
<article class="room {{ $t->featured ? 'featured' : '' }}">
    <div class="media media-scrim">
        <x-img :path="$t->image" :alt="'Suasana '.$t->name.' DM Karaoke'" />
        <div class="absolute top-4 left-4 z-[1] flex gap-2">
            @if($promo)<span class="badge badge-gold">Promo</span>@endif
            @if($t->publicCap())<span class="badge">Maks. {{ $t->publicCap() }} orang</span>@endif
        </div>
    </div>
    <div class="flex flex-col flex-1 gap-4 p-6">
        <div>
            <h3 class="m-0 font-display text-[28px] leading-[34px] font-semibold">{{ $t->name }}</h3>
            <p class="mt-1 mb-0 text-sm leading-[22px] text-ink-muted">{{ $t->description }}</p>
        </div>
        <ul class="ticks">@foreach(array_filter($t->facilities ?? []) as $f)<li>{{ $f }}</li>@endforeach</ul>
        <div class="mt-auto pt-4 border-t border-line flex flex-wrap items-end justify-between gap-4">
            <div>
                @if($t->rentable)
                    <span class="price-from">Sewa per jam</span><span class="price-val">{{ rp($t->rate_night) }}</span>
                    <span class="price-sub">Siang {{ hl(\App\Support\OpHours::open()) }}–{{ hl(\App\Support\OpHours::dayEnd()) }} mulai {{ rp($t->rate_day) }}@if($minPkg) · Paket mulai {{ rp($minPkg) }}@endif</span>
                @else
                    <span class="price-from">Paket mulai dari</span><span class="price-val">{{ rp($minPkg) }}</span>
                    <span class="price-sub">3 jam room, LC, minuman, dan food platter</span>
                @endif
                @if($promo)<span class="block mt-1.5 text-xs leading-[18px] text-gold">Promo: {{ $promo->name }}</span>@endif
            </div>
            <a class="btn {{ $t->featured ? 'btn-primary' : 'btn-outline' }} btn-sm" href="{{ $bookUrl }}">Pilih jadwal</a>
        </div>
    </div>
</article>
