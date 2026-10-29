@extends('layouts.admin', ['title' => $t->exists ? 'Ubah '.$t->name : 'Tambah tipe ruangan', 'subtitle' => 'Ruangan & harga'])

@php
    $units = old('units') ? collect(old('units'))->map(fn ($u) => (object) ['id' => $u['id'] ?? null, 'name' => $u['name'] ?? '', 'active' => ! empty($u['active'])]) : $units->map(fn ($u) => (object) ['id' => $u->id, 'name' => $u->name, 'active' => $u->active]);
    $fac = old('facilities', array_pad($t->facilities ?? [], 3, ''));
@endphp

@section('content')
{{-- FR-ADM-10 --}}
<form method="POST" action="{{ $t->exists ? route('admin.ruangan.update', $t) : route('admin.ruangan.store') }}" enctype="multipart/form-data" class="card grid gap-x-4 gap-y-6 md:grid-cols-2 max-w-[860px]"
      x-data="{ units: @js($units->values()), add() { const base = (document.getElementById('t-name').value.trim().split(' ')[0] || 'Ruang'); this.units.push({ id: null, name: base + ' ' + (this.units.length + 1), active: true }); } }">
    @csrf
    @if($t->exists) @method('PUT') @endif
    <div class="field"><label class="label" for="t-name">Nama tipe</label><input class="input" id="t-name" name="name" value="{{ old('name', $t->name) }}" placeholder="Contoh: Standard Room" required maxlength="80"></div>
    <div class="field"><label class="label" for="t-cap">Kapasitas maksimal (orang)</label><input class="input" id="t-cap" name="cap" type="number" min="1" value="{{ old('cap', $t->cap) }}" inputmode="numeric" placeholder="Kosongkan bila tidak dibatasi"></div>
    <label class="switch"><input type="checkbox" name="show_cap" value="1" @checked(old('show_cap', $t->show_cap))> Tampilkan kapasitas di website</label>
    <label class="switch"><input type="checkbox" name="rentable" value="1" @checked(old('rentable', $t->rentable))> Bisa disewa tanpa paket (per jam)</label>
    <div class="field"><label class="label" for="t-rday">Siang tanpa LC (Rp per jam)</label><input class="input" id="t-rday" name="rate_day" type="number" min="0" step="1000" value="{{ old('rate_day', $t->rate_day) }}" inputmode="numeric"><span class="help">Dengan LC dan deposit, siang gratis.</span></div>
    <div class="field"><label class="label" for="t-rnight">Malam (Rp per jam)</label><input class="input" id="t-rnight" name="rate_night" type="number" min="0" step="1000" value="{{ old('rate_night', $t->rate_night) }}" inputmode="numeric"></div>
    <label class="switch"><input type="checkbox" name="active" value="1" @checked(old('active', $t->active))> Tampil dan bisa dipesan</label>
    <label class="switch"><input type="checkbox" name="featured" value="1" @checked(old('featured', $t->featured))> Tandai sebagai pilihan unggulan</label>
    <div class="field md:col-span-2"><label class="label" for="t-desc">Deskripsi singkat</label><input class="input" id="t-desc" name="description" value="{{ old('description', $t->description) }}" maxlength="255"></div>
    <fieldset class="field md:col-span-2 border-0 p-0 m-0 min-w-0">
        <legend class="label mb-2">Fasilitas (3 poin)</legend>
        <div class="grid gap-2 sm:grid-cols-3">@for($i = 0; $i < 3; $i++)<input class="input" name="facilities[]" value="{{ $fac[$i] ?? '' }}" aria-label="Fasilitas {{ $i + 1 }}" maxlength="60">@endfor</div>
    </fieldset>
    <div class="field md:col-span-2" x-data="imagePick(@js($t->image ? upload_url($t->image, 'sm') : ''))">
        <span class="label">Foto</span>
        <div class="flex flex-wrap items-center gap-4">
            <img :src="src" x-show="src" alt="" class="w-[120px] aspect-[4/3] object-cover rounded-sm border border-line">
            <label class="btn btn-quiet btn-sm" for="t-photo"><x-icon name="upload" class="ic-sm" />{{ $t->image ? 'Ganti foto' : 'Pilih foto' }}</label>
            <input class="sr-only" type="file" id="t-photo" name="image" accept="image/jpeg,image/png,image/webp" @change="pick">
        </div>
        <span class="help">JPG, PNG, atau WebP maks. 8 MB. Otomatis dikompres menjadi WebP.</span>
    </div>
    <div class="field md:col-span-2">
        <span class="label">Unit ruangan</span>
        <template x-for="(u, i) in units" :key="i">
            <div class="grid grid-cols-[minmax(0,1fr)_auto_auto] gap-3 items-center">
                <input type="hidden" :name="`units[${i}][id]`" :value="u.id ?? ''">
                <input class="input !h-10" :name="`units[${i}][name]`" x-model="u.name" aria-label="Nama unit" maxlength="60">
                <label class="switch"><input type="checkbox" :name="`units[${i}][active]`" value="1" x-model="u.active"> Aktif</label>
                <button type="button" class="icon-btn is-danger" @click="units.splice(i, 1)" :disabled="units.length === 1" aria-label="Hapus unit"><x-icon name="trash" class="ic-sm" /></button>
            </div>
        </template>
        <div><button type="button" class="btn btn-quiet btn-sm" @click="add()"><x-icon name="plus" class="ic-sm" />Tambah unit</button></div>
        <span class="help">Jadwal dihitung per unit. Unit nonaktif tidak bisa dipesan, tapi riwayatnya tetap tersimpan.</span>
    </div>
    <div class="md:col-span-2 flex justify-end gap-3">
        <a class="btn btn-quiet btn-sm" href="{{ route('admin.ruangan.index') }}">Batal</a>
        <button class="btn btn-primary btn-sm" type="submit">{{ $t->exists ? 'Simpan perubahan' : 'Tambah ruangan' }}</button>
    </div>
</form>
@endsection
