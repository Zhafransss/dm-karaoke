@extends('layouts.admin', ['title' => 'Event', 'subtitle' => 'Event terbit tampil di halaman Event; yang ditandai juga tampil di beranda.'])

@section('actions')
    <a class="btn btn-primary btn-sm" href="{{ route('admin.event.create') }}"><x-icon name="plus" class="ic-sm" />Tambah event</a>
@endsection

@section('content')
<div class="grid gap-2 sm:gap-4 grid-cols-3">
    <div class="stat"><b>{{ $stats['upcoming'] }}</b><span>Mendatang</span></div><div class="stat"><b>{{ $stats['draft'] }}</b><span>Draf</span></div><div class="stat"><b>{{ $stats['past'] }}</b><span>Sudah lewat</span></div>
</div>
@if($list->isEmpty())
    <div class="empty">Belum ada event.</div>
@else
<div class="grid gap-4 grid-cols-[repeat(auto-fill,minmax(240px,1fr))]">
    @foreach($list as $e)
        @php($past = $e->date->toDateString() < $today)
        <article class="flex flex-col min-w-0 bg-surface-raised border border-line rounded-md overflow-hidden">
            <div class="media {{ $e->published ? '' : 'opacity-50 grayscale' }}">
                <x-img :path="$e->image" alt="" sizes="260px" />
                <span class="badge badge-dot absolute top-3 left-3 {{ $e->published ? 'badge-success' : '' }}">{{ $e->published ? 'Terbit' : 'Draf' }}</span>
            </div>
            <div class="flex flex-col gap-2 flex-1 p-4">
                <b class="text-[15px] leading-[22px] font-medium">{{ $e->title }}</b>
                <span class="caption">{{ tgl($e->date, 'mid') }} · {{ $e->start_time }}–{{ $e->end_time }}{{ $past ? ' · sudah lewat' : '' }}@if($e->featured)<span class="text-gold"> · di beranda</span>@endif</span>
            </div>
            <div class="flex gap-2 px-4 pb-4 mt-auto">
                <a class="icon-btn" href="{{ route('admin.event.edit', $e) }}" aria-label="Ubah {{ $e->title }}" title="Ubah"><x-icon name="edit" class="ic-sm" /></a>
                <form method="POST" action="{{ route('admin.event.toggle', $e) }}">@csrf<button class="icon-btn" type="submit" aria-label="{{ $e->published ? 'Jadikan draf' : 'Terbitkan' }} {{ $e->title }}" title="{{ $e->published ? 'Jadikan draf' : 'Terbitkan' }}"><x-icon :name="$e->published ? 'eyeOff' : 'eye'" class="ic-sm" /></button></form>
                @if($e->published)<a class="icon-btn" href="{{ route('event.show', $e->slug) }}" target="_blank" aria-label="Lihat di website" title="Lihat di website"><x-icon name="ext" class="ic-sm" /></a>@endif
                <form method="POST" action="{{ route('admin.event.destroy', $e) }}" class="ml-auto" data-confirm="Hapus event?" data-confirm-text="&quot;{{ $e->title }}&quot; akan dihapus permanen." data-confirm-label="Hapus event">
                    @csrf @method('DELETE')<button class="icon-btn is-danger" type="submit" aria-label="Hapus {{ $e->title }}" title="Hapus"><x-icon name="trash" class="ic-sm" /></button>
                </form>
            </div>
        </article>
    @endforeach
</div>
@endif
@endsection
