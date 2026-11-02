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
            <input class="sr-only" type="file" id="t-photo" name="image" data-crop data-crop-ratio="4:3" accept="image/jpeg,image/png,image/webp" @change="pick">
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

@if($t->exists)
{{-- Extra photos and videos for the public detail page. A separate form: file inputs must not nest inside the form above. --}}
<section class="card max-w-[860px] flex flex-col gap-5 scroll-mt-28" id="media" x-data="{ previews: [], busy: false }">
    <div>
        <h2 class="m-0">Foto &amp; video tambahan</h2>
        <p class="mt-1 mb-0 text-sm leading-[22px] text-ink-subtle">Foto utama di atas jadi sampul kartu ruangan. Foto dan video di sini tampil di halaman detail ruangan. @if($t->active)<a class="text-gold" href="{{ route('ruangan.show', $t->slug) }}" target="_blank" rel="noopener">Lihat halamannya</a>@endif</p>
    </div>
    @php($mediaErrors = collect($errors->get('media.*'))->flatten()->merge($errors->get('media'))->unique())
    @foreach($mediaErrors as $m)<p class="notice m-0">{{ $m }}</p>@endforeach
    @if($t->media->isEmpty())
        <p class="empty-sm m-0">Belum ada foto atau video tambahan.</p>
    @else
        <div class="grid gap-3 grid-cols-[repeat(auto-fill,minmax(150px,1fr))]">
            @foreach($t->media as $m)
                <div class="flex flex-col gap-2">
                    <div class="media rounded-sm border border-line">
                        @if($m->isVideo())<x-video-thumb :path="$m->path" /><span class="badge absolute top-2 right-2">Video</span>@else<x-img :path="$m->path" alt="" sizes="160px" />@endif
                    </div>
                    <form method="POST" action="{{ route('admin.ruangan.media.destroy', [$t, $m]) }}" data-confirm="Hapus {{ $m->isVideo() ? 'video' : 'foto' }}?" data-confirm-text="File ini akan dihapus permanen dari halaman detail ruangan." data-confirm-label="Hapus">
                        @csrf @method('DELETE')<button class="btn btn-quiet btn-sm w-full" type="submit"><x-icon name="trash" class="ic-sm" />Hapus</button>
                    </form>
                </div>
            @endforeach
        </div>
    @endif
    <form method="POST" action="{{ route('admin.ruangan.media.store', $t) }}" enctype="multipart/form-data" class="flex flex-col gap-4" @submit="busy = true">
        @csrf
        <label class="flex flex-col items-center gap-2 px-4 py-8 border border-dashed border-gold-deep rounded-md bg-surface text-center cursor-pointer hover:border-gold hover:bg-gold-soft" for="m-file">
            <x-icon name="upload" class="!w-8 !h-8 text-gold" /><b class="text-[15px] font-medium">Pilih foto atau video</b>
            <span class="caption">Foto: JPG, PNG, WebP maks. 8 MB. Video: MP4, WebM, MOV maks. {{ config('dmk.max_video_mb') }} MB. Bisa lebih dari satu.</span>
        </label>
        <input class="sr-only" id="m-file" type="file" name="media[]" data-crop data-crop-ratio="4:3" accept="image/jpeg,image/png,image/webp,video/mp4,video/webm,video/quicktime" multiple required
               @change="previews = [...$event.target.files].map(f => ({ src: URL.createObjectURL(f), video: f.type.startsWith('video/') }))">
        <div class="grid grid-cols-[repeat(auto-fill,minmax(72px,1fr))] gap-2" x-show="previews.length" x-cloak>
            <template x-for="p in previews" :key="p.src"><div class="relative aspect-square overflow-hidden rounded-sm border border-line"><img x-show="!p.video" :src="p.src" alt="" class="w-full h-full object-cover"><video x-show="p.video" :src="p.src + '#t=0.1'" preload="metadata" muted class="w-full h-full object-cover"></video></div></template>
        </div>
        <div class="flex justify-end"><button class="btn btn-primary btn-sm" type="submit" :disabled="busy || !previews.length" x-text="busy ? 'Mengunggah…' : 'Unggah'">Unggah</button></div>
    </form>
</section>
@else
<p class="caption max-w-[860px]">Simpan ruangan dulu, lalu tambahkan foto dan video lainnya untuk halaman detailnya.</p>
@endif
@endsection
