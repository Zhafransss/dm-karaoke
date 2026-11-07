@extends('layouts.admin', ['title' => $p->exists ? 'Ubah promo' : 'Tambah promo', 'subtitle' => $p->exists ? $p->name : 'Promo'])

@php
    use App\Support\OpHours;
    $kind = old('kind', $p->kind);
    $window = (bool) old('window', $p->time_start !== null);
    $open = OpHours::open(); $close = OpHours::close();
@endphp

@section('content')
{{-- FR-ADM-12 / FR-ADM-13 --}}
<form method="POST" action="{{ $p->exists ? route('admin.promo.update', $p) : route('admin.promo.store') }}" class="card grid gap-x-4 gap-y-6 md:grid-cols-2 max-w-[860px]"
      x-data="promoForm(@js(['state' => ['kind' => $kind, 'window' => $window, 'x' => (int) old('x', $p->x ?? 3)], 'urls' => ['preview' => route('admin.promo.preview')]]))">
    @csrf
    @if($p->exists) @method('PUT') <input type="hidden" name="id" value="{{ $p->id }}"> @endif
    <div class="field md:col-span-2"><label class="label" for="p-name">Nama promo</label><input class="input" id="p-name" name="name" value="{{ old('name', $p->name) }}" placeholder="Contoh: Sewa 3 Jam Bayar 2 Jam" required maxlength="100"></div>
    <div class="field md:col-span-2"><label class="label" for="p-desc">Deskripsi (tampil di halaman promo)</label><input class="input" id="p-desc" name="description" value="{{ old('description', $p->description) }}" maxlength="255"></div>
    <div class="field md:col-span-2"><label class="label" for="p-kind">Jenis promo</label>
        <select class="input" id="p-kind" name="kind" x-model="kind">@foreach(config('dmk.promo_kinds') as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select></div>
    <div class="field md:col-span-2" x-show="kind !== 'bayar_x'">
        <label class="label" for="p-value">Nilai potongan</label>
        <input class="input" id="p-value" name="value" type="number" min="1" value="{{ old('value', $p->value ?: '') }}" inputmode="numeric" :disabled="kind === 'bayar_x'">
        <span class="help" x-text="kind === 'persen' ? 'Persen dari harga yang kena promo (1–100).' : 'Rupiah, diberikan sekali per transaksi.'"></span>
    </div>
    <div class="field" x-show="kind === 'bayar_x'" x-cloak><label class="label" for="p-x">Sewa (jam)</label><input class="input" id="p-x" name="x" type="number" min="2" max="12" x-model.number="x" :disabled="kind !== 'bayar_x'"></div>
    <div class="field" x-show="kind === 'bayar_x'" x-cloak><label class="label" for="p-y">Bayar (jam)</label><input class="input" id="p-y" name="y" type="number" min="1" max="11" value="{{ old('y', $p->y ?? 2) }}" :disabled="kind !== 'bayar_x'">
        <span class="help">Jam termurah yang dibayar penuh digratiskan, satu kali per transaksi; jam gratis karena LC tidak dihitung. Untuk paket: hanya paket minimal sesuai jam sewa, potongannya senilai tarif sewa per jam (bukan Paket Family atau Master Room).</span></div>

    <fieldset class="field md:col-span-2 border-0 p-0 m-0 min-w-0">
        <legend class="label mb-2">Berlaku untuk</legend>
        <div class="flex flex-col gap-4">
            @foreach($types as $t)
                <div class="px-4 py-3 border border-line rounded-sm bg-bg" data-type="{{ $t->id }}">
                    <div class="flex flex-wrap items-center justify-between gap-3 mb-2 text-sm"><b>{{ $t->name }}</b>
                        <span class="flex gap-1"><button type="button" class="btn btn-ghost btn-sm" @click="setAll({{ $t->id }}, true)">Pilih semua</button><button type="button" class="btn btn-ghost btn-sm" @click="setAll({{ $t->id }}, false)">Kosongkan</button></span></div>
                    <div class="flex flex-wrap gap-1.5">
                        @if($t->rentable)
                            <label class="check !min-h-[34px] !px-3 !py-1.5 !text-[13px]"><input type="checkbox" name="targets[]" value="{{ $t->rentKey() }}" @checked(in_array($t->rentKey(), $targets))> Sewa ruangan saja</label>
                        @endif
                        @foreach($t->packages as $pk)
                            {{-- "Bayar X" only fits packages of >= X hours with an hourly room rate (mirrors Promo::packageEligible). --}}
                            @php($payX = ! $pk->family && $t->rentable ? "x > {$pk->hours}" : 'true')
                            <label class="check !min-h-[34px] !px-3 !py-1.5 !text-[13px] {{ $pk->active ? '' : 'dim' }}" :class="kind === 'bayar_x' && ({{ $payX }}) && 'dim'"><input type="checkbox" name="targets[]" value="{{ $pk->pkgKey() }}" @checked(in_array($pk->pkgKey(), $targets)) :disabled="kind === 'bayar_x' && ({{ $payX }})"> {{ $pk->name }} <span class="text-ink-subtle">· {{ $pk->hours }} jam</span>{{ $pk->active ? '' : ' (nonaktif)' }}</label>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </fieldset>

    <div class="field"><label class="label" for="p-ds">Tanggal mulai</label><input class="input" type="date" id="p-ds" name="date_start" value="{{ old('date_start', $p->date_start?->toDateString()) }}" required></div>
    <div class="field"><label class="label" for="p-de">Tanggal berakhir</label><input class="input" type="date" id="p-de" name="date_end" value="{{ old('date_end', $p->date_end?->toDateString()) }}" required></div>
    <label class="switch md:col-span-2"><input type="checkbox" name="window" value="1" x-model="window"> Hanya berlaku di jam tertentu (opsional)</label>
    <div class="field" x-show="window" x-cloak><label class="label" for="p-ts">Jam mulai promo</label>
        <select class="input" id="p-ts" name="time_start" :disabled="!window">@for($h = $open; $h < $close; $h++)<option value="{{ $h }}" @selected((int) old('time_start', $p->time_start ?? $open) === $h)>{{ hl($h) }}</option>@endfor</select></div>
    <div class="field" x-show="window" x-cloak><label class="label" for="p-te">Jam selesai promo</label>
        <select class="input" id="p-te" name="time_end" :disabled="!window">@for($h = $open + 1; $h <= $close; $h++)<option value="{{ $h }}" @selected((int) old('time_end', $p->time_end ?? min($open + 3, $close)) === $h)>{{ hl($h) }}</option>@endfor</select></div>
    <label class="switch md:col-span-2"><input type="checkbox" name="active" value="1" @checked(old('active', $p->active))> Aktif (sakelar manual)</label>

    <div class="md:col-span-2 flex flex-col gap-3" aria-live="polite">
        <div class="flex flex-col gap-1 px-4 py-3 border border-line rounded-sm bg-bg text-sm leading-[22px] text-ink-muted" x-show="preview" x-cloak>
            <span class="label !text-ink-subtle">Contoh perhitungan</span><span x-text="preview"></span>
        </div>
        <div class="notice notice-info" x-show="overlaps.length" x-cloak>
            <b>Promo aktif lain juga berlaku di target dan waktu yang sama:</b>
            <ul class="my-2 pl-5"><template x-for="o in overlaps" :key="o.name"><li><b x-text="o.name"></b> · <span x-text="o.targets"></span> · <span x-text="o.period"></span></li></template></ul>
            <span>Boleh, misalnya untuk promo bertingkat. Tamu tetap memakai satu promo per reservasi: otomatis yang potongannya terbesar, dan bisa diganti ke promo lain yang berlaku.</span>
        </div>
    </div>
    <div class="md:col-span-2 flex justify-end gap-3">
        <a class="btn btn-quiet btn-sm" href="{{ route('admin.promo.index') }}">Batal</a>
        <button class="btn btn-primary btn-sm" type="submit">{{ $p->exists ? 'Simpan perubahan' : 'Simpan promo' }}</button>
    </div>
</form>
@endsection
