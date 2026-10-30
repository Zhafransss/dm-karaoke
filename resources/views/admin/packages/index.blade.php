@extends('layouts.admin', ['title' => 'Paket', 'subtitle' => 'Daftar paket per ruangan. Harga, isi, dan durasi paket diatur di sini.'])

@section('actions')
    <a class="btn btn-primary btn-sm" href="{{ route('admin.paket.create') }}"><x-icon name="plus" class="ic-sm" />Tambah paket</a>
@endsection

@section('content')
<p class="notice notice-info">Harga paket tetap, tidak ikut tarif siang atau malam. Paket Family memakai jam mulai khusus. Klik nama ruangan untuk membuka atau menutup daftar paketnya.</p>
@foreach($types as $t)
    <section class="card" x-data="collapsible('pkg-room-{{ $t->id }}')">
        <h2 class="!mb-0">
            <button type="button" class="flex w-full flex-wrap items-center justify-between gap-3 text-left" @click="toggle()" :aria-expanded="open.toString()" aria-controls="pkg-room-{{ $t->id }}">
                <span class="inline-flex items-center gap-3"><x-icon name="right" class="ic-sm text-gold transition-transform" ::class="open && 'rotate-90'" />{{ $t->name }}</span>
                <span class="caption">{{ $t->packages->count() }} paket · {{ $t->packages->where('active', true)->count() }} aktif @if($t->packages->isNotEmpty())· {{ rp($t->packages->min('price')) }}–{{ rp($t->packages->max('price')) }}@endif</span>
            </button>
        </h2>
        <div id="pkg-room-{{ $t->id }}" class="mt-4" x-show="open" x-cloak>
        @forelse($t->packages->groupBy('brand') as $brand => $list)
            <div class="{{ $loop->first ? '' : 'mt-6' }}">
                <h3 class="m-0 mb-3 font-display text-[22px] leading-[26px] font-semibold text-gold">{{ $brand }}</h3>
                <div class="flex flex-col gap-3">
                    @foreach($list as $p)
                        <article class="grid gap-3 md:gap-6 md:grid-cols-[minmax(0,1fr)_auto_auto] items-center px-4 py-3 bg-bg border border-line rounded-md {{ $p->active ? '' : 'dim' }}">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2"><h4 class="m-0 text-base leading-[22px] font-medium">{{ $p->name }}</h4>@if($p->family)<span class="badge badge-gold">Family</span>@endif @unless($p->active)<span class="badge">Nonaktif</span>@endunless</div>
                                <p class="mt-1.5 mb-0 text-[13px] leading-5 text-ink-subtle">{{ $p->hours }} jam @if($p->start_hour !== null)· hanya mulai {{ hl($p->start_hour) }}@endif · {{ implode(' · ', $p->items ?? []) }}</p>
                            </div>
                            <div class="font-display text-[26px] leading-none text-gold font-semibold tabular whitespace-nowrap">{{ rp($p->price) }}</div>
                            <div class="flex items-center gap-3">
                                <form method="POST" action="{{ route('admin.paket.toggle', $p) }}">@csrf<label class="switch"><input type="checkbox" @checked($p->active) onchange="this.form.submit()"> Aktif</label></form>
                                <a class="icon-btn" href="{{ route('admin.paket.edit', $p) }}" aria-label="Ubah {{ $p->name }}" title="Ubah"><x-icon name="edit" class="ic-sm" /></a>
                                <form method="POST" action="{{ route('admin.paket.destroy', $p) }}" data-confirm="Hapus paket?" data-confirm-text="&quot;{{ $p->name }}&quot; akan dihapus. Promo yang mengarah ke paket ini ikut dilepas." data-confirm-label="Hapus paket">
                                    @csrf @method('DELETE')<button class="icon-btn is-danger" type="submit" aria-label="Hapus {{ $p->name }}" title="Hapus"><x-icon name="trash" class="ic-sm" /></button>
                                </form>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        @empty
            <p class="empty-sm">Belum ada paket untuk ruangan ini. <a href="{{ route('admin.paket.create', ['tipe' => $t->id]) }}">Tambah paket</a></p>
        @endforelse
        </div>
    </section>
@endforeach
@endsection
