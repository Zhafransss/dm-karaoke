@extends('layouts.admin', ['title' => 'Ruangan & harga', 'subtitle' => 'Tarif sewa per jam, kapasitas, dan unit ruangan yang dipakai jadwal reservasi.'])

@section('actions')
    <a class="btn btn-quiet btn-sm" href="{{ route('admin.paket.index') }}"><x-icon name="box" class="ic-sm" />Kelola paket</a>
    <a class="btn btn-primary btn-sm" href="{{ route('admin.ruangan.create') }}"><x-icon name="plus" class="ic-sm" />Tambah tipe ruangan</a>
@endsection

@section('content')
<p class="notice notice-info">Perubahan harga hanya berlaku untuk reservasi baru; reservasi yang sudah masuk tetap memakai harga saat dibuat. Tarif siang dan malam berlaku untuk sewa ruangan saja, harga paket diatur di menu Paket. Batas jam siang diatur di Pengaturan.</p>
<div class="flex flex-col gap-4">
    @foreach($types as $t)
        @php($promo = $promoSvc->firstFor([$t->rentKey(), ...$t->packages->map->pkgKey()], $today))
        <article class="grid gap-6 md:grid-cols-[168px_minmax(0,1fr)_auto] items-center p-4 bg-surface-raised border border-line rounded-md {{ $t->active ? '' : 'dim' }}">
            <div class="media rounded-sm"><x-img :path="$t->image" alt="" sizes="168px" /></div>
            <div class="flex flex-col gap-2 min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <h3 class="m-0 font-display text-[28px] leading-8 font-semibold">{{ $t->name }}</h3>
                    @unless($t->active)<span class="badge">Nonaktif</span>@endunless
                    @unless($t->rentable)<span class="badge">Hanya paket</span>@endunless
                    @if($t->prepaid)<span class="badge badge-gold">Lunas di awal</span>@elseif($t->deposit)<span class="badge">Deposit {{ $t->depositLabel() }}</span>@endif
                    @if($promo)<span class="badge badge-gold">Promo: {{ $promo->name }}</span>@endif
                </div>
                <p class="caption m-0">{{ $t->cap ? 'Maks. '.$t->cap.' orang'.($t->show_cap ? '' : ' (tidak ditampilkan di website)') : 'Kapasitas tidak ditentukan' }} · {{ $t->packages->count() }} paket · {{ collect($t->facilities)->filter()->join(', ') }}</p>
                <div class="flex flex-wrap gap-1.5">
                    @forelse($t->rooms as $u)<span class="badge {{ $u->active ? '' : 'dim' }}">{{ $u->name }}{{ $u->active ? '' : ' · nonaktif' }}</span>
                    @empty<span class="caption !text-danger">Belum ada unit, tipe ini belum bisa dipesan.</span>@endforelse
                </div>
            </div>
            <div class="flex flex-col md:items-end gap-1 md:text-right">
                @if($t->rentable)
                    <span class="price-from">Sewa malam / jam</span><span class="price-val">{{ rp($t->rate_night) }}</span>
                    <span class="price-sub md:text-right">Siang tanpa LC {{ rp($t->rate_day) }} · dengan LC gratis</span>
                @else
                    <span class="price-from">Sewa ruangan</span><span class="price-val">—</span><span class="price-sub">Hanya lewat paket</span>
                @endif
                <div class="flex gap-2 mt-3">
                    <a class="btn btn-outline btn-sm" href="{{ route('admin.ruangan.edit', $t) }}">Ubah</a>
                    <form method="POST" action="{{ route('admin.ruangan.destroy', $t) }}" data-confirm="Hapus tipe ruangan?" data-confirm-text="{{ $t->name }} beserta unit dan paketnya akan dihapus." data-confirm-label="Hapus ruangan">
                        @csrf @method('DELETE')<button class="icon-btn is-danger" type="submit" aria-label="Hapus {{ $t->name }}" title="Hapus"><x-icon name="trash" class="ic-sm" /></button>
                    </form>
                </div>
            </div>
        </article>
    @endforeach
</div>
@endsection
