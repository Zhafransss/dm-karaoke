@extends('layouts.admin', ['title' => 'Galeri', 'subtitle' => 'Foto dan video yang ditampilkan muncul di halaman Galeri dan beranda.'])

@section('actions')
    <button class="btn btn-primary btn-sm" type="button" x-data @click="$dispatch('open-upload')"><x-icon name="upload" class="ic-sm" />Unggah foto / video</button>
@endsection

@section('content')
<div class="grid gap-2 sm:gap-4 grid-cols-3">
    <div class="stat"><b>{{ $total }}</b><span>Total file</span></div><div class="stat"><b>{{ $shown }}</b><span>Ditampilkan</span></div><div class="stat"><b>{{ $total - $shown }}</b><span>Disembunyikan</span></div>
</div>
<div class="flex flex-wrap items-center justify-between gap-3">
    <nav class="flex flex-wrap gap-2" aria-label="Filter kategori">
        <a class="chip" href="{{ route('admin.galeri.index') }}" @if(! $cat) aria-current="page" @endif>Semua</a>
        @foreach($cats as $c)<a class="chip" href="{{ route('admin.galeri.index', ['kategori' => $c]) }}" @if($cat === $c) aria-current="page" @endif>{{ $c }}</a>@endforeach
    </nav>
    <span class="caption">{{ $items->count() }} file</span>
</div>
@if($items->isEmpty())
    <div class="empty">Belum ada foto atau video di kategori ini.</div>
@else
<div class="grid gap-4 grid-cols-[repeat(auto-fill,minmax(220px,1fr))]">
    @foreach($items as $it)
        <article class="flex flex-col min-w-0 bg-surface-raised border border-line rounded-md overflow-hidden">
            <div class="media {{ $it->published ? '' : 'opacity-40 grayscale' }}">
                @if($it->isVideo())<x-video-thumb :path="$it->video" />@else<x-img :path="$it->image" :alt="$it->caption" sizes="240px" />@endif
                @if($it->isVideo())<span class="badge absolute top-3 right-3">Video</span>@endif
                <span class="badge badge-dot absolute top-3 left-3 {{ $it->published ? 'badge-success' : '' }}">{{ $it->published ? 'Tampil' : 'Disembunyikan' }}</span>
            </div>
            <div class="flex flex-col gap-1 flex-1 p-4"><b class="text-[15px] leading-[22px] font-medium" style="overflow-wrap:anywhere">{{ $it->caption }}</b><span class="caption">{{ $it->category }} · {{ tgl($it->created_at, 'short') }}</span></div>
            <div class="flex gap-2 px-4 pb-4 mt-auto">
                <a class="icon-btn" href="{{ route('admin.galeri.edit', $it) }}" aria-label="Ubah {{ $it->caption }}" title="Ubah"><x-icon name="edit" class="ic-sm" /></a>
                <form method="POST" action="{{ route('admin.galeri.toggle', $it) }}">@csrf<button class="icon-btn" type="submit" aria-label="{{ $it->published ? 'Sembunyikan' : 'Tampilkan' }} {{ $it->caption }}" title="{{ $it->published ? 'Sembunyikan' : 'Tampilkan' }}"><x-icon :name="$it->published ? 'eyeOff' : 'eye'" class="ic-sm" /></button></form>
                <form method="POST" action="{{ route('admin.galeri.destroy', $it) }}" class="ml-auto" data-confirm="Hapus {{ $it->isVideo() ? 'video' : 'foto' }}?" data-confirm-text="&quot;{{ $it->caption }}&quot; akan dihapus permanen dari galeri." data-confirm-label="Hapus {{ $it->isVideo() ? 'video' : 'foto' }}">
                    @csrf @method('DELETE')<button class="icon-btn is-danger" type="submit" aria-label="Hapus {{ $it->caption }}" title="Hapus"><x-icon name="trash" class="ic-sm" /></button>
                </form>
            </div>
        </article>
    @endforeach
</div>
@endif

<div class="modal-back" x-data="{ open: {{ $errors->hasAny(['media', 'media.*', 'caption', 'category']) ? 'true' : 'false' }}, previews: [], busy: false }" x-show="open" x-cloak
     @open-upload.window="open = true" @keydown.escape.window="open = false" @click.self="open = false">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="up-title">
        <h2 id="up-title">Unggah foto / video</h2>
        <form method="POST" action="{{ route('admin.galeri.store') }}" enctype="multipart/form-data" class="grid gap-4 sm:grid-cols-2" @submit="busy = true">
            @csrf
            <label class="sm:col-span-2 flex flex-col items-center gap-2 px-4 py-8 border border-dashed border-gold-deep rounded-md bg-surface text-center cursor-pointer hover:border-gold hover:bg-gold-soft" for="u-file">
                <x-icon name="upload" class="!w-8 !h-8 text-gold" /><b class="text-[15px] font-medium">Pilih foto atau video</b><span class="caption">Foto: JPG, PNG, WebP maks. 8 MB. Video: MP4, WebM, MOV maks. {{ config('dmk.max_video_mb') }} MB. Bisa lebih dari satu.</span>
            </label>
            <input class="sr-only" id="u-file" type="file" name="media[]" data-crop data-crop-ratio="orig" accept="image/jpeg,image/png,image/webp,video/mp4,video/webm,video/quicktime" multiple required @change="previews = [...$event.target.files].map(f => ({ src: URL.createObjectURL(f), video: f.type.startsWith('video/') }))">
            <div class="sm:col-span-2 grid grid-cols-[repeat(auto-fill,minmax(72px,1fr))] gap-2" x-show="previews.length">
                <template x-for="p in previews" :key="p.src"><div class="relative aspect-square overflow-hidden rounded-sm border border-line"><img x-show="!p.video" :src="p.src" alt="" class="w-full h-full object-cover"><video x-show="p.video" :src="p.src + '#t=0.1'" preload="metadata" muted class="w-full h-full object-cover"></video></div></template>
            </div>
            <div class="field sm:col-span-2"><label class="label" for="u-caption">Judul</label><input class="input" id="u-caption" name="caption" value="{{ old('caption') }}" placeholder="Contoh: Ruang VIP setelah renovasi" maxlength="150"><span class="help">Kosongkan untuk memakai nama file. Untuk beberapa file, judul diberi nomor.</span></div>
            <div class="field"><label class="label" for="u-cat">Kategori</label><select class="input" id="u-cat" name="category">@foreach($cats as $c)<option @selected(old('category') === $c)>{{ $c }}</option>@endforeach</select></div>
            <div class="field justify-end"><label class="switch"><input type="checkbox" name="published" value="1" checked> Tampilkan di galeri</label></div>
            <div class="sm:col-span-2 flex justify-end gap-3"><button class="btn btn-quiet btn-sm" type="button" @click="open = false">Batal</button><button class="btn btn-primary btn-sm" type="submit" :disabled="busy" x-text="busy ? 'Mengunggah…' : 'Simpan'">Simpan</button></div>
        </form>
        <button class="icon-btn absolute top-4 right-4" type="button" @click="open = false" aria-label="Tutup"><x-icon name="close" /></button>
    </div>
</div>
@endsection
