@extends('layouts.admin', ['title' => 'Dashboard', 'subtitle' => tgl($today)])

@section('actions')
    @if($canRes)
        <a class="btn btn-quiet btn-sm" href="{{ route('admin.jadwal') }}"><x-icon name="calendar" class="ic-sm" />Buka jadwal</a>
        <a class="btn btn-primary btn-sm" href="{{ route('admin.reservasi.create') }}"><x-icon name="plus" class="ic-sm" />Tambah reservasi</a>
    @endif
@endsection

@section('content')
<div class="grid gap-2 sm:gap-4 grid-cols-2 lg:grid-cols-4">
    @if($canRes)
        <div class="stat"><b>{{ $todays->count() }}</b><span>Reservasi hari ini</span></div>
        <div class="stat"><b>{{ $waiting->count() }}</b><span>Menunggu konfirmasi</span></div>
    @endif
    <div class="stat"><b>{{ $running }}</b><span>Promo berjalan</span></div>
    <div class="stat"><b>{{ $unread }}</b><span>Pesan belum dibaca</span></div>
</div>

@if($canRes)
<div class="grid gap-6 xl:grid-cols-[3fr_2fr] items-start">
    <section class="card">
        <h2>Perlu dikonfirmasi <a class="btn btn-ghost btn-sm" href="{{ route('admin.reservasi.index', ['status' => 'menunggu', 'waktu' => 'semua']) }}">Semua</a></h2>
        @forelse($waiting->take(8) as $r)
            <div class="li">
                <span class="li-time">{{ hl($r->start_hour) }}</span>
                <div class="flex-1 min-w-0"><b class="block font-medium text-[15px] truncate">{{ $r->name }} · {{ $r->roomType->name }}</b><span class="block text-[13px] text-ink-subtle truncate">{{ tgl($r->date, 'mid') }} · {{ $r->hours }} jam · {{ $r->code }}</span></div>
                <div class="flex gap-2 flex-none">
                    <form method="POST" action="{{ route('admin.reservasi.status', $r) }}">@csrf<input type="hidden" name="status" value="dikonfirmasi"><button class="btn btn-primary btn-sm">Konfirmasi</button></form>
                    <a class="btn btn-quiet btn-sm" href="{{ route('admin.reservasi.show', $r) }}">Detail</a>
                </div>
            </div>
        @empty
            <p class="empty-sm">Tidak ada reservasi yang menunggu konfirmasi.</p>
        @endforelse
    </section>
    <section class="card">
        <h2>Jadwal hari ini</h2>
        @forelse($todays as $r)
            <a class="li no-underline text-inherit hover:bg-surface-overlay -mx-2 px-2 rounded-sm" href="{{ route('admin.reservasi.show', $r) }}">
                <span class="li-time">{{ hl($r->start_hour) }}</span>
                <span class="flex-1 min-w-0"><b class="block font-medium text-[15px] text-ink truncate">{{ $r->name }}</b><span class="block text-[13px] text-ink-subtle truncate">{{ $r->room->name }} · sampai {{ hl($r->endHour()) }}</span></span>
                <x-status-badge :status="$r->status" />
            </a>
        @empty
            <p class="empty-sm">Belum ada reservasi hari ini.</p>
        @endforelse
    </section>
</div>
@endif

@if($waLogs->isNotEmpty())
<section class="card">
    <h2>Notifikasi WhatsApp terakhir <a class="btn btn-ghost btn-sm" href="{{ route('admin.pengaturan') }}#log">Log lengkap</a></h2>
    @foreach($waLogs as $l)
        <div class="li">
            <span class="badge {{ $l->recipient === 'admin' ? 'badge-gold' : '' }}">{{ $l->recipient === 'admin' ? 'Admin' : 'Tamu' }}</span>
            <div class="flex-1 min-w-0"><b class="block font-medium text-[15px] truncate">{{ strtok($l->body, "\n") }}</b><span class="block text-[13px] text-ink-subtle truncate">{{ $l->to }} · {{ tgl($l->created_at, 'datetime') }} · {{ $l->status }}</span></div>
        </div>
    @endforeach
</section>
@endif
@endsection
