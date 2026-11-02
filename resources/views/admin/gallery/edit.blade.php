@extends('layouts.admin', ['title' => $it->isVideo() ? 'Ubah video' : 'Ubah foto', 'subtitle' => 'Galeri'])

@section('content')
<form method="POST" action="{{ route('admin.galeri.update', $it) }}" enctype="multipart/form-data" class="card grid gap-x-4 gap-y-6 sm:grid-cols-2 max-w-[640px]">
    @csrf @method('PUT')
    {{-- Current file, plus an optional replacement (photos go through the crop dialog first) --}}
    <div class="sm:col-span-2 flex flex-col gap-3" x-data="{ src: @js($it->isVideo() ? '' : upload_url($it->image)), name: '', pick(e) { const f = e.target.files[0]; this.name = f ? f.name : ''; if (f && f.type.startsWith('image/')) this.src = URL.createObjectURL(f); } }">
        <div class="relative aspect-video overflow-hidden rounded-md border border-line">
            @if($it->isVideo())<video src="{{ upload_url($it->video) }}" controls playsinline preload="metadata" class="absolute inset-0 w-full h-full object-contain bg-black"></video>
            @else<img :src="src" alt="" class="absolute inset-0 w-full h-full object-cover">@endif
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <label class="btn btn-quiet btn-sm" for="e-file"><x-icon name="upload" class="ic-sm" />{{ $it->isVideo() ? 'Ganti video' : 'Ganti foto' }}</label>
            @if($it->isVideo())
                <input class="sr-only" type="file" id="e-file" name="media" accept="video/mp4,video/webm,video/quicktime" @change="pick">
            @else
                <input class="sr-only" type="file" id="e-file" name="media" accept="image/jpeg,image/png,image/webp" data-crop data-crop-ratio="orig" @change="pick">
            @endif
            <span class="caption" x-show="name" x-text="name + ' (disimpan saat menekan Simpan perubahan)'" x-cloak></span>
        </div>
        @error('media')<span class="err">{{ $message }}</span>@enderror
        <span class="help">{{ $it->isVideo() ? 'MP4, WebM, atau MOV maks. '.config('dmk.max_video_mb').' MB.' : 'JPG, PNG, atau WebP maks. 8 MB. Foto bisa dipotong dulu sebelum disimpan.' }} Kosongkan bila tidak ingin mengganti file.</span>
    </div>
    <div class="field sm:col-span-2"><label class="label" for="e-caption">Judul</label><input class="input" id="e-caption" name="caption" value="{{ old('caption', $it->caption) }}" required maxlength="150"></div>
    <div class="field"><label class="label" for="e-cat">Kategori</label><select class="input" id="e-cat" name="category">@foreach($cats as $c)<option @selected(old('category', $it->category) === $c)>{{ $c }}</option>@endforeach</select></div>
    <div class="field justify-end"><label class="switch"><input type="checkbox" name="published" value="1" @checked(old('published', $it->published))> Tampilkan di galeri</label></div>
    <div class="sm:col-span-2 flex justify-end gap-3"><a class="btn btn-quiet btn-sm" href="{{ route('admin.galeri.index') }}">Batal</a><button class="btn btn-primary btn-sm" type="submit">Simpan perubahan</button></div>
</form>
@endsection
