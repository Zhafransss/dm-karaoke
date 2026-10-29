@extends('layouts.admin', ['title' => $e->exists ? 'Ubah event' : 'Tambah event', 'subtitle' => $e->exists ? $e->title : 'Event'])

@php($times = array_map(fn ($h) => str_pad($h, 2, '0', STR_PAD_LEFT).'.00', range(0, 23)))

@section('content')
{{-- FR-ADM-14 --}}
<form method="POST" action="{{ $e->exists ? route('admin.event.update', $e) : route('admin.event.store') }}" enctype="multipart/form-data" class="card grid gap-x-4 gap-y-6 md:grid-cols-2 max-w-[860px]">
    @csrf
    @if($e->exists) @method('PUT') @endif
    <div class="field md:col-span-2"><label class="label" for="ev-title">Judul event</label><input class="input" id="ev-title" name="title" value="{{ old('title', $e->title) }}" required maxlength="150"></div>
    <div class="field md:col-span-2"><label class="label" for="ev-date">Tanggal</label><input class="input" type="date" id="ev-date" name="date" value="{{ old('date', $e->date?->toDateString()) }}" required></div>
    <div class="field"><label class="label" for="ev-start">Jam mulai</label><select class="input" id="ev-start" name="start_time">@foreach($times as $t)<option @selected(old('start_time', $e->start_time) === $t)>{{ $t }}</option>@endforeach</select></div>
    <div class="field"><label class="label" for="ev-end">Jam selesai</label><select class="input" id="ev-end" name="end_time">@foreach($times as $t)<option @selected(old('end_time', $e->end_time) === $t)>{{ $t }}</option>@endforeach</select></div>
    <div class="field md:col-span-2"><label class="label" for="ev-body">Deskripsi</label><textarea class="input" id="ev-body" name="body" rows="6" maxlength="5000">{{ old('body', $e->body) }}</textarea></div>
    <div class="field md:col-span-2" x-data="imagePick(@js($e->image ? upload_url($e->image, 'sm') : ''))">
        <span class="label">Poster</span>
        <div class="flex flex-wrap items-center gap-4">
            <img :src="src" x-show="src" alt="" class="w-[120px] aspect-[4/5] object-cover rounded-sm border border-line">
            <label class="btn btn-quiet btn-sm" for="ev-photo"><x-icon name="upload" class="ic-sm" />{{ $e->image ? 'Ganti poster' : 'Pilih poster' }}</label>
            <input class="sr-only" type="file" id="ev-photo" name="image" accept="image/jpeg,image/png,image/webp" @change="pick">
        </div>
        <span class="help">Tanpa poster, sistem memakai gambar bawaan. JPG, PNG, atau WebP maks. 8 MB.</span>
    </div>
    <div class="field"><label class="label" for="ev-status">Status</label>
        <select class="input" id="ev-status" name="status"><option value="terbit" @selected(old('status', $e->published ? 'terbit' : 'draf') === 'terbit')>Terbit</option><option value="draf" @selected(old('status', $e->published ? 'terbit' : 'draf') === 'draf')>Draf</option></select></div>
    <div class="field justify-end"><label class="switch"><input type="checkbox" name="featured" value="1" @checked(old('featured', $e->featured))> Tampilkan di beranda</label></div>
    <div class="md:col-span-2 flex justify-end gap-3">
        <a class="btn btn-quiet btn-sm" href="{{ route('admin.event.index') }}">Batal</a>
        <button class="btn btn-primary btn-sm" type="submit">{{ $e->exists ? 'Simpan perubahan' : 'Simpan event' }}</button>
    </div>
</form>
@endsection
