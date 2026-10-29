@extends('layouts.admin', ['title' => 'Reservasi', 'subtitle' => 'Reservasi online dan yang dibuat admin. Perubahan status hanya dilakukan admin.'])

@php
    $q_ = fn (array $over) => route('admin.reservasi.index', array_filter(array_merge(['waktu' => $when, 'status' => $status, 'tipe' => $typeId, 'q' => $q], $over), fn ($v) => $v !== null && $v !== ''));
    $total = $counts->sum();
@endphp

@section('actions')
    <a class="btn btn-primary btn-sm" href="{{ route('admin.reservasi.create') }}"><x-icon name="plus" class="ic-sm" />Tambah reservasi</a>
@endsection

@section('content')
{{-- FR-ADM-05: filters by status, date range, type, and search --}}
<div class="flex flex-wrap items-center justify-between gap-3">
    <nav class="flex flex-wrap gap-2" aria-label="Rentang waktu">
        @foreach(['mendatang' => 'Mendatang', 'hari-ini' => 'Hari ini', 'semua' => 'Semua'] as $k => $l)
            <a class="chip" href="{{ $q_(['waktu' => $k]) }}" @if($when === $k) aria-current="page" @endif>{{ $l }}</a>
        @endforeach
    </nav>
    <form method="GET" class="flex flex-wrap items-center gap-2 flex-1 justify-end">
        <input type="hidden" name="waktu" value="{{ $when }}">
        @if($status)<input type="hidden" name="status" value="{{ $status }}">@endif
        <select class="input input-sm !w-auto" name="tipe" onchange="this.form.submit()" aria-label="Filter ruangan">
            <option value="">Semua ruangan</option>@foreach($types as $t)<option value="{{ $t->id }}" @selected($typeId === $t->id)>{{ $t->name }}</option>@endforeach
        </select>
        <label class="relative flex-1 min-w-[200px] max-w-[360px]">
            <span class="sr-only">Cari reservasi</span>
            <x-icon name="search" class="ic-sm absolute left-3 top-2.5 text-ink-subtle" />
            <input class="input input-sm !h-10 pl-10" type="search" name="q" value="{{ $q }}" placeholder="Cari kode, nama, atau nomor">
        </label>
    </form>
</div>
<nav class="flex flex-wrap gap-2" aria-label="Filter status">
    <a class="chip" href="{{ $q_(['status' => null]) }}" @if(! $status) aria-current="page" @endif>Semua ({{ $total }})</a>
    @foreach(config('dmk.statuses') as $k => $s)
        <a class="chip" href="{{ $q_(['status' => $k]) }}" @if($status === $k) aria-current="page" @endif>{{ $s['label'] }} ({{ $counts[$k] ?? 0 }})</a>
    @endforeach
</nav>

@if($list->isEmpty())
    <p class="empty-sm">Tidak ada reservasi yang cocok dengan filter ini.</p>
@else
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th scope="col">Kode</th><th scope="col">Tamu</th><th scope="col">Ruangan</th><th scope="col">Jadwal</th><th scope="col">Total</th><th scope="col">Status</th><th scope="col"><span class="sr-only">Aksi</span></th></tr></thead>
            <tbody>
                @foreach($list as $r)
                    <tr>
                        <td class="tabular"><b>{{ $r->code }}</b><div class="caption">{{ $r->source === 'admin' ? 'Dibuat admin' : 'Online' }}</div></td>
                        <td><b class="font-medium">{{ $r->name }}</b><div class="caption">{{ $r->phone ?: '—' }} · {{ $r->pax }} orang</div></td>
                        <td>{{ $r->roomType->name }}<div class="caption">{{ $r->room->name }} · {{ $r->orderLabel() }}</div></td>
                        <td>{{ tgl($r->date, 'mid') }}<div class="caption">{{ $r->timeLabel() }} · {{ $r->hours }} jam</div></td>
                        <td class="tabular">{{ rp($r->total) }}@if($r->discount)<div class="caption !text-gold">Promo −{{ rp($r->discount) }}</div>@endif</td>
                        <td><x-status-badge :status="$r->status" /></td>
                        <td class="text-right whitespace-nowrap"><a class="btn btn-quiet btn-sm" href="{{ route('admin.reservasi.show', $r) }}">Detail</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    {{ $list->links() }}
@endif
@endsection
