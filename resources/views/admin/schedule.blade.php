@extends('layouts.admin', ['title' => 'Jadwal ruangan', 'subtitle' => tgl($date)])

@php
    use App\Support\OpHours;
    use Carbon\CarbonImmutable;
    $open = OpHours::open(); $close = OpHours::close(); $cols = $close - $open;
    $prev = CarbonImmutable::parse($date)->subDay()->toDateString();
    $next = CarbonImmutable::parse($date)->addDay()->toDateString();
    $allRooms = $types->flatMap->rooms;
@endphp

@section('actions')
    <button class="btn btn-quiet btn-sm" type="button" x-data @click="$dispatch('open-block')"><x-icon name="ban" class="ic-sm" />Tutup jam</button>
    <a class="btn btn-primary btn-sm" href="{{ route('admin.reservasi.create', ['tanggal' => $date]) }}"><x-icon name="plus" class="ic-sm" />Tambah reservasi</a>
@endsection

@section('content')
{{-- FR-ADM-04: unit × hour timeline incl. early-morning hours of the operational day --}}
<div class="flex flex-wrap items-center justify-between gap-4">
    <form class="flex flex-wrap items-center gap-2" method="GET" action="{{ route('admin.jadwal') }}">
        <a class="icon-btn" href="{{ route('admin.jadwal', ['tanggal' => $prev]) }}" aria-label="Hari sebelumnya"><x-icon name="left" class="ic-sm" /></a>
        <input class="input input-sm !w-auto" type="date" name="tanggal" value="{{ $date }}" aria-label="Pilih tanggal" onchange="this.form.submit()">
        <a class="icon-btn" href="{{ route('admin.jadwal', ['tanggal' => $next]) }}" aria-label="Hari berikutnya"><x-icon name="right" class="ic-sm" /></a>
        @if($date !== $today)<a class="btn btn-quiet btn-sm" href="{{ route('admin.jadwal') }}">Hari ini</a>@endif
    </form>
    <span class="caption">{{ $activeCount }} reservasi aktif · ketuk sel kosong untuk menambah</span>
</div>

<div class="tl-wrap">
    <div class="grid min-w-max" style="grid-template-columns: 128px repeat({{ $cols }}, minmax(88px, 1fr))">
        <div class="tl-hc sticky left-0 z-[4] bg-surface-raised !border-l-0">Unit</div>
        @foreach($hours as $h)<div class="tl-hc">{{ hl($h) }}@if($h >= 24)<span class="block text-[10px] text-gold">dini hari</span>@endif</div>@endforeach
        @php($row = 2)
        @foreach($types as $t)
            <div class="tl-group" style="grid-row: {{ $row++ }}">{{ $t->name }}<span class="ml-2 normal-case tracking-[.06em] text-ink-subtle">{{ $t->rentable ? rp($t->rate_night).'/jam malam' : 'Hanya paket' }}{{ $t->active ? '' : ' · tipe nonaktif' }}</span></div>
            @foreach($t->rooms as $u)
                <div class="tl-label" style="grid-row: {{ $row }}">{{ $u->name }}</div>
                @foreach($hours as $h)
                    @php($past = OpHours::isPast($date, $h + 1))
                    <a class="tl-cell {{ $past ? 'past' : '' }}" style="grid-row: {{ $row }}; grid-column: {{ $h - $open + 2 }}"
                       href="{{ route('admin.reservasi.create', ['unit' => $u->id, 'jam' => $h, 'tanggal' => $date]) }}" aria-label="Tambah reservasi di {{ $u->name }} pukul {{ hl($h) }}" @if($past) tabindex="-1" aria-hidden="true" @endif><span aria-hidden="true">+</span></a>
                @endforeach
                @foreach($reservations[$u->id] ?? [] as $r)
                    @php($s = max($r->start_hour, $open)) @php($e = min($r->endHour(), $close))
                    @continue($e <= $s)
                    <a class="tl-block st-{{ $r->status }}" style="grid-row: {{ $row }}; grid-column: {{ $s - $open + 2 }} / span {{ $e - $s }}" href="{{ route('admin.reservasi.show', $r) }}" title="{{ $r->name }} · {{ $r->timeLabel() }}">
                        <b>{{ $r->name }}</b><span>{{ $r->timeLabel() }} · {{ $r->statusLabel() }}</span>
                    </a>
                @endforeach
                @foreach($blocks[$u->id] ?? [] as $b)
                    @php($s = max($b->start_hour, $open)) @php($e = min($b->end_hour, $close))
                    @continue($e <= $s)
                    <form method="POST" action="{{ route('admin.jadwal.blokir.hapus', $b) }}" class="tl-block st-blok" style="grid-row: {{ $row }}; grid-column: {{ $s - $open + 2 }} / span {{ $e - $s }}"
                          data-confirm="Buka kembali jam ini?" data-confirm-text="{{ $u->name }}, {{ $b->timeLabel() }}. Alasan: {{ $b->reason ?: '—' }}. Ditutup oleh {{ $b->user?->name ?? '-' }}." data-confirm-label="Buka kembali">
                        @csrf @method('DELETE')
                        <button class="text-left bg-transparent border-0 p-0 text-inherit cursor-pointer min-w-0" type="submit" title="Klik untuk membuka kembali"><b class="block">Ditutup</b><span class="block truncate">{{ $b->reason ?: $b->timeLabel() }}</span></button>
                    </form>
                @endforeach
                @php($row++)
            @endforeach
        @endforeach
    </div>
</div>
<div class="legend">
    <span><i class="st-menunggu"></i>Menunggu</span><span><i class="st-dikonfirmasi"></i>Dikonfirmasi</span><span><i class="st-selesai"></i>Selesai</span>
    <span><i class="st-tidak_datang"></i>Tidak datang</span><span><i class="st-blok"></i>Jam ditutup (klik untuk membuka)</span>
</div>

{{-- FR-ADM-08: close hours on a unit --}}
<div class="modal-back" x-data="{ open: {{ $errors->has('block') ? 'true' : 'false' }} }" x-show="open" x-cloak @open-block.window="open = true" @keydown.escape.window="open = false" @click.self="open = false">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="block-title">
        <h2 id="block-title">Tutup jam</h2>
        <p class="m-0 muted">Jam yang ditutup tidak bisa dipesan tamu, misalnya untuk perawatan atau acara privat.</p>
        <form method="POST" action="{{ route('admin.jadwal.blokir') }}" class="grid gap-4 sm:grid-cols-2">
            @csrf
            <div class="field"><label class="label" for="b-unit">Unit</label>
                <select class="input" id="b-unit" name="room_id">@foreach($types as $t)<optgroup label="{{ $t->name }}">@foreach($t->rooms as $u)<option value="{{ $u->id }}" @selected(old('room_id') == $u->id)>{{ $u->name }}</option>@endforeach</optgroup>@endforeach</select></div>
            <div class="field"><label class="label" for="b-date">Tanggal</label><input class="input" type="date" id="b-date" name="date" value="{{ old('date', $date) }}"></div>
            <div class="field"><label class="label" for="b-hour">Jam mulai</label>
                <select class="input" id="b-hour" name="start_hour">@foreach($hours as $h)<option value="{{ $h }}" @selected(old('start_hour') == $h)>{{ hl($h) }}</option>@endforeach</select></div>
            <div class="field"><label class="label" for="b-dur">Durasi</label>
                <select class="input" id="b-dur" name="hours">@for($n = 1; $n <= $cols; $n++)<option value="{{ $n }}" @selected(old('hours', 2) == $n)>{{ $n }} jam</option>@endfor</select></div>
            <div class="field sm:col-span-2"><label class="label" for="b-reason">Alasan</label><input class="input" id="b-reason" name="reason" value="{{ old('reason') }}" placeholder="Contoh: perawatan sound system" maxlength="255"></div>
            @error('block')<p class="notice sm:col-span-2" role="alert">{{ $message }}</p>@enderror
            <div class="sm:col-span-2 flex justify-end gap-3"><button class="btn btn-quiet btn-sm" type="button" @click="open = false">Batal</button><button class="btn btn-primary btn-sm" type="submit">Tutup jam ini</button></div>
        </form>
        <button class="icon-btn absolute top-4 right-4" type="button" @click="open = false" aria-label="Tutup"><x-icon name="close" /></button>
    </div>
</div>
@endsection
