{{-- Locked price of a stored reservation (FR-RES-10). --}}
@props(['r'])
@php($rows = \App\Support\QuotePresenter::rows($r->price_lines ?? [], $r->package_id ? 'paket' : 'sewa', $r->promo_name, $r->promo_note, $r->discount))
<div class="sum">
    @foreach($rows as $row)
        <div class="sum-row {{ $row['discount'] ? 'disc' : '' }}"><span>{{ $row['text'] }} @if($row['tag'])<small class="text-xs text-ink-subtle ml-1">{{ $row['tag'] }}</small>@endif</span><b>{{ $row['amount'] }}</b></div>
    @endforeach
    <div class="sum-total"><span>Total</span><b>{{ rp($r->total) }}</b></div>
    <span class="caption">{{ $slot->isEmpty() ? 'Harga dikunci saat reservasi dibuat. Dibayar di tempat.' : $slot }}</span>
</div>
