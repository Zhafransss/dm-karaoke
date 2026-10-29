@props(['pkg', 'promo' => null, 'typeSlug' => null])
<article class="pkg {{ $pkg->family ? 'pkg-family' : '' }}">
    <div class="flex items-start justify-between gap-2">
        <h4 class="m-0 text-[15px] leading-[22px] font-medium text-ink">{{ $pkg->name }}</h4>
        @if($promo)<span class="badge badge-gold">Promo</span>@endif
    </div>
    <div class="pkg-price">{{ rp($pkg->price) }}</div>
    <p class="-mt-1 mb-0 text-xs leading-[18px] tracking-[.1em] uppercase text-ink-subtle">{{ $pkg->hours }} jam @if($pkg->start_hour !== null)· mulai {{ hl($pkg->start_hour) }}@endif</p>
    <ul class="ticks flex-1">@foreach($pkg->items ?? [] as $i)<li>{{ $i }}</li>@endforeach</ul>
    <a class="btn btn-outline btn-sm self-start mt-2" href="{{ route('reservasi', ['tipe' => $typeSlug ?? $pkg->roomType?->slug, 'paket' => $pkg->id]) }}">Pesan paket</a>
</article>
