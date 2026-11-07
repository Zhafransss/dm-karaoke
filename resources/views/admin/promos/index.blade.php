@extends('layouts.admin', ['title' => 'Promo', 'subtitle' => 'Promo bisa diarahkan ke sewa ruangan atau paket tertentu.'])

@php
    $badges = ['berjalan' => ['badge-success', 'Berjalan'], 'terjadwal' => ['badge-gold', 'Terjadwal'], 'nonaktif' => ['', 'Nonaktif'], 'berakhir' => ['', 'Berakhir']];
    $n = fn ($s) => $list->filter(fn ($p) => $p->state($today) === $s)->count();
@endphp

@section('actions')
    <a class="btn btn-primary btn-sm" href="{{ route('admin.promo.create') }}"><x-icon name="plus" class="ic-sm" />Tambah promo</a>
@endsection

@section('content')
<div class="grid gap-2 sm:gap-4 grid-cols-3">
    <div class="stat"><b>{{ $n('berjalan') }}</b><span>Berjalan</span></div><div class="stat"><b>{{ $n('terjadwal') }}</b><span>Terjadwal</span></div><div class="stat"><b>{{ $n('nonaktif') }}</b><span>Nonaktif</span></div>
</div>
<p class="caption m-0">Aturan: satu promo per transaksi. Beberapa promo boleh aktif bersamaan di target yang sama, misalnya promo bertingkat "3 jam bayar 2" dan "4 jam bayar 2". Tamu otomatis mendapat potongan terbesar dan bisa memilih promo lain yang berlaku. Promo berlaku bila sakelar aktif dan tanggal kunjungan masuk periode.</p>
@forelse($list as $p)
    @php($st = $p->state($today)) @php([$big, $unit] = $p->figure())
    @php($names = collect($p->targets())->map(fn ($k) => $labels[$k] ?? null)->filter())
    <article class="grid gap-3 md:gap-6 md:grid-cols-[112px_minmax(0,1fr)_auto] items-center p-4 bg-surface-raised border border-line rounded-md {{ in_array($st, ['nonaktif', 'berakhir']) ? 'dim' : '' }}">
        <div class="flex md:flex-col items-center md:justify-center gap-3 md:gap-1 md:w-[112px] md:h-24 px-4 py-3 md:p-1.5 bg-gold-soft border border-gold-deep rounded-sm text-center">
            <b class="font-display text-[32px] leading-none text-gold font-semibold tabular">{{ $big }}</b><span class="text-[10px] leading-[14px] tracking-[.12em] uppercase text-gold">{{ $unit }}</span>
        </div>
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2"><h3 class="m-0 font-display text-2xl leading-7 font-semibold">{{ $p->name }}</h3><span class="badge badge-dot {{ $badges[$st][0] }}">{{ $badges[$st][1] }}</span></div>
            <p class="mt-1.5 mb-0 text-[13px] leading-5 text-ink-subtle">{{ $names->count() > 3 ? $names->take(2)->join(', ').' +'.($names->count() - 2).' lainnya' : ($names->join(', ') ?: 'Belum ada target') }} · {{ $p->periodLabel() }}</p>
            @if($p->description)<p class="mt-1 mb-0 text-sm leading-[22px] text-ink-muted">{{ $p->description }}</p>@endif
        </div>
        <div class="flex items-center gap-3">
            <form method="POST" action="{{ route('admin.promo.toggle', $p) }}">@csrf<label class="switch"><input type="checkbox" @checked($p->active) onchange="this.form.submit()"> Aktif</label></form>
            <a class="icon-btn" href="{{ route('admin.promo.edit', $p) }}" aria-label="Ubah {{ $p->name }}" title="Ubah"><x-icon name="edit" class="ic-sm" /></a>
            <form method="POST" action="{{ route('admin.promo.destroy', $p) }}" data-confirm="Hapus promo?" data-confirm-text="&quot;{{ $p->name }}&quot; akan dihapus. Reservasi yang sudah memakai promo ini tetap menyimpan harganya." data-confirm-label="Hapus promo">
                @csrf @method('DELETE')<button class="icon-btn is-danger" type="submit" aria-label="Hapus {{ $p->name }}" title="Hapus"><x-icon name="trash" class="ic-sm" /></button>
            </form>
        </div>
    </article>
@empty
    <div class="empty">Belum ada promo.</div>
@endforelse
@endsection
