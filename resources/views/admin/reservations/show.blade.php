@extends('layouts.admin', ['title' => $r->code, 'subtitle' => $r->roomType->name.' · '.tgl($r->date).' · '.$r->timeLabel()])

@php
    $actions = [
        'dikonfirmasi' => ['Konfirmasi', 'btn-primary', null],
        'selesai' => ['Tandai selesai', 'btn-primary', null],
        'tidak_datang' => ['Tidak datang', 'btn-quiet', 'Tandai tidak datang?'],
        'dibatalkan' => [$r->status === 'menunggu' ? 'Tolak' : 'Batalkan', 'btn-quiet', $r->status === 'menunggu' ? 'Tolak reservasi?' : 'Batalkan reservasi?'],
    ];
@endphp

@section('actions')
    <a class="btn btn-quiet btn-sm" href="{{ route('admin.jadwal', ['tanggal' => $r->ymd()]) }}"><x-icon name="calendar" class="ic-sm" />Lihat di jadwal</a>
@endsection

@section('content')
<div class="grid gap-6 xl:grid-cols-[3fr_2fr] items-start">
    <section class="card flex flex-col gap-5">
        <div class="flex flex-wrap items-center gap-3"><x-status-badge :status="$r->status" /><span class="caption">{{ $r->source === 'online' ? 'Reservasi online' : 'Dibuat admin' }} · {{ tgl($r->created_at, 'datetime') }}</span></div>
        <dl class="kv">
            <dt>Nama</dt><dd>{{ $r->name }}</dd>
            <dt>WhatsApp</dt><dd>@if($r->phone)<span class="select-all">{{ $r->phone }}</span>@else — @endif</dd>
            <dt>Jumlah orang</dt><dd>{{ $r->pax }} orang</dd>
            <dt>Ruangan</dt><dd>{{ $r->roomType->name }} · {{ $r->room->name }}</dd>
            <dt>Pesanan</dt><dd>{{ $r->orderLabel() }}</dd>
            <dt>Tanggal</dt><dd>{{ tgl($r->date) }}</dd>
            <dt>Jam</dt><dd>{{ $r->timeLabel() }} ({{ $r->hours }} jam)</dd>
            @if($r->note)<dt>Catatan</dt><dd>{{ $r->note }}</dd>@endif
            @if($r->ip)<dt>IP</dt><dd class="caption !text-sm">{{ $r->ip }}</dd>@endif
        </dl>
        <x-price-summary :r="$r" />
        <div class="flex flex-wrap items-center justify-between gap-3 pt-2 border-t border-line">
            @if($waLink)
                <a class="btn btn-outline btn-sm" href="{{ $waLink }}" target="_blank" rel="noopener"><x-icon name="chat" class="ic-sm" />{{ ['dikonfirmasi' => 'Kabari tamu: dikonfirmasi', 'dibatalkan' => 'Kabari tamu: dibatalkan'][$r->status] ?? 'Hubungi via WhatsApp' }}</a>
            @else
                <span class="caption">Tidak ada nomor WhatsApp</span>
            @endif
            <div class="flex flex-wrap gap-2">
                @foreach($r->nextStatuses() as $to)
                    @php([$label, $cls, $confirm] = $actions[$to])
                    <form method="POST" action="{{ route('admin.reservasi.status', $r) }}"
                          @if($confirm) data-confirm="{{ $confirm }}" data-confirm-text="{{ $r->code }} atas nama {{ $r->name }} ({{ tgl($r->date, 'mid') }}, {{ $r->timeLabel() }}). Jam ini akan kembali tersedia untuk tamu lain." data-confirm-label="{{ $label }}" @endif>
                        @csrf<input type="hidden" name="status" value="{{ $to }}"><button class="btn {{ $cls }} btn-sm" type="submit">{{ $label }}</button>
                    </form>
                @endforeach
            </div>
        </div>
    </section>

    <div class="flex flex-col gap-6">
        @if($r->isOccupying())
        {{-- FR-ADM-09: move unit or time with conflict validation --}}
        <section class="card">
            <h2>Pindah unit atau jam</h2>
            <form method="POST" action="{{ route('admin.reservasi.move', $r) }}" class="grid gap-4 sm:grid-cols-2">
                @csrf
                <div class="field"><label class="label" for="m-unit">Unit</label>
                    <select class="input" id="m-unit" name="room_id">@foreach($units as $u)<option value="{{ $u->id }}" @selected($u->id === $r->room_id)>{{ $u->name }}{{ $u->active ? '' : ' (nonaktif)' }}</option>@endforeach</select></div>
                <div class="field"><label class="label" for="m-date">Tanggal</label><input class="input" type="date" id="m-date" name="date" value="{{ $r->ymd() }}"></div>
                <div class="field"><label class="label" for="m-hour">Jam mulai</label>
                    <select class="input" id="m-hour" name="start_hour">@foreach($hours as $h)<option value="{{ $h }}" @selected($h === $r->start_hour)>{{ hl($h) }}</option>@endforeach</select></div>
                <div class="field"><span class="label">Durasi</span><span class="input grid items-center text-ink-muted">{{ $r->hours }} jam</span></div>
                <label class="switch sm:col-span-2"><input type="checkbox" name="reprice" value="1" checked> Hitung ulang harga bila tanggal atau jam berubah</label>
                @error('move')<p class="notice sm:col-span-2">{{ $message }}</p>@enderror
                <div class="sm:col-span-2 flex justify-end"><button class="btn btn-primary btn-sm" type="submit">Simpan perubahan</button></div>
            </form>
        </section>
        @endif

        <section class="card">
            <h2>Riwayat</h2>
            <ul class="m-0 p-0 list-none flex flex-col gap-2 text-[13px] leading-5 text-ink-muted">
                @foreach($r->logs as $log)
                    <li>
                        <b class="text-ink font-medium">{{ $log->from_status === $log->to_status && $log->from_status ? 'Diubah' : config("dmk.statuses.{$log->to_status}.label") }}</b>
                        · {{ tgl($log->created_at, 'datetime') }} · {{ $log->user?->name ?? 'Tamu (online)' }}
                        @if($log->note)<div class="text-ink-subtle">{{ $log->note }}</div>@endif
                    </li>
                @endforeach
            </ul>
        </section>
    </div>
</div>
@endsection
