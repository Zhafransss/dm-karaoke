@extends('layouts.admin', ['title' => 'Ubah foto', 'subtitle' => 'Galeri'])

@section('content')
<form method="POST" action="{{ route('admin.galeri.update', $it) }}" class="card grid gap-x-4 gap-y-6 sm:grid-cols-2 max-w-[640px]">
    @csrf @method('PUT')
    <div class="sm:col-span-2 relative aspect-video overflow-hidden rounded-md border border-line"><x-img :path="$it->image" alt="" class="absolute inset-0 w-full h-full object-cover" sizes="640px" /></div>
    <div class="field sm:col-span-2"><label class="label" for="e-caption">Judul</label><input class="input" id="e-caption" name="caption" value="{{ old('caption', $it->caption) }}" required maxlength="150"></div>
    <div class="field"><label class="label" for="e-cat">Kategori</label><select class="input" id="e-cat" name="category">@foreach($cats as $c)<option @selected(old('category', $it->category) === $c)>{{ $c }}</option>@endforeach</select></div>
    <div class="field justify-end"><label class="switch"><input type="checkbox" name="published" value="1" @checked(old('published', $it->published))> Tampilkan di galeri</label></div>
    <div class="sm:col-span-2 flex justify-end gap-3"><a class="btn btn-quiet btn-sm" href="{{ route('admin.galeri.index') }}">Batal</a><button class="btn btn-primary btn-sm" type="submit">Simpan perubahan</button></div>
</form>
@endsection
