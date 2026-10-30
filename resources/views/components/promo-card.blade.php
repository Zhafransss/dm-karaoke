@props(['promo', 'labels' => []])
@php
    $p = $promo;
    $labels = $labels ?: app(\App\Services\PromoService::class)->targetLabels();
    $state = $p->state();
    [$big, $unit] = $p->figure();
    $rooms = $p->targetSummary($labels);
    // Book button: first target decides type and mode.
    $first = $p->targets()[0] ?? null;
    $book = route('reservasi');
    if ($first) {
        [$k, $id] = explode(':', $first);
        $pk = $k === 'pkg' ? \App\Models\Package::find($id) : null;
        $book = route('reservasi', array_filter(['tipe' => $pk?->room_type_id ?? $id, 'paket' => $pk?->id, 'tanggal' => max($p->ds(), \App\Support\OpHours::today()), 'jam' => $p->time_start]));
    }
@endphp
<article class="promo {{ $state === 'terjadwal' ? 'soon' : '' }}">
    <div class="promo-fig"><span class="promo-big">{{ $big }}</span><span class="promo-unit">{{ $unit }}</span></div>
    <div class="flex flex-col gap-3 p-6 min-w-0">
        <div>@if($state === 'berjalan')<span class="badge badge-success badge-dot">Sedang berlangsung</span>@else<span class="badge">Mulai {{ tgl($p->date_start, 'short') }}</span>@endif</div>
        <h3 class="m-0 font-display text-[28px] leading-8 font-semibold">{{ $p->name }}</h3>
        @if($p->description)<p class="m-0 text-sm leading-[22px] text-ink-muted">{{ $p->description }}</p>@endif
        <dl class="kv kv-sm"><dt>Berlaku</dt><dd>{{ $rooms }}</dd><dt>Periode</dt><dd>{{ $p->periodLabel() }}</dd></dl>
        <div class="mt-auto pt-3 border-t border-line flex flex-wrap items-center justify-between gap-3">
            <span class="caption">{{ $p->ruleText() }}</span>
            <a class="btn btn-ghost" href="{{ $book }}">Reservasi <x-icon name="right" class="ic-sm" /></a>
        </div>
    </div>
</article>
