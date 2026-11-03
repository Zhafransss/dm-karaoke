@extends('layouts.public', ['nav' => 'reservasi', 'title' => 'Cek Status Reservasi | DM Karaoke & Cafe', 'description' => 'Cek status reservasi karaoke kamu di DM Karaoke & Cafe dengan kode reservasi.', 'noindex' => true])

@section('content')
<x-page-head crumb="Cek status" eyebrow="Cek status" title="Status <em>reservasimu</em>" lead="Masukkan kode reservasi dan nomor WhatsApp yang dipakai saat reservasi." />

<section class="band"><div class="wrap"><div class="narrow">
    <div class="panel">
        <form method="POST" action="{{ route('cek-status.check') }}" class="grid gap-4 sm:grid-cols-2" novalidate>
            @csrf
            <div class="field">
                <label class="label" for="c-code">Kode reservasi</label>
                <input class="input uppercase" id="c-code" name="code" value="{{ old('code', $code) }}" placeholder="DM-0000-XXXX" autocapitalize="characters" required>
                @error('code')<span class="err">{{ $message }}</span>@enderror
            </div>
            <div class="field">
                <label class="label" for="c-phone">Nomor WhatsApp</label>
                <input class="input" id="c-phone" name="phone" type="tel" inputmode="tel" value="{{ old('phone', $phone) }}" placeholder="08xx xxxx xxxx" required>
                @error('phone')<span class="err">{{ $message }}</span>@enderror
            </div>
            <div class="sm:col-span-2 flex justify-end"><button class="btn btn-primary" type="submit"><x-icon name="search" class="ic-sm" />Cek status</button></div>
        </form>

        @if($searched)
            <div class="mt-6" role="status">
                @if($result)
                    @php($r = $result)
                    @php($msg = [
                        'menunggu' => 'Admin sedang mengecek reservasimu. Kamu akan dihubungi lewat WhatsApp.',
                        'dikonfirmasi' => 'Reservasimu sudah dikonfirmasi. Datang sesuai jadwal ya'.($r->roomType->prepaid ? '.' : ', pembayaran di tempat.'),
                        'selesai' => 'Terima kasih sudah bernyanyi di DM Karaoke & Cafe.',
                        'dibatalkan' => 'Reservasi ini dibatalkan. Silakan pilih jadwal lain atau hubungi kami lewat WhatsApp.',
                        'tidak_datang' => 'Reservasi ini tercatat tidak datang. Hubungi kami bila ada kekeliruan.',
                    ][$r->status])
                    <div class="sum">
                        <div class="flex flex-wrap items-center justify-between gap-3"><b class="code-big !text-[32px]">{{ $r->code }}</b><x-status-badge :status="$r->status" /></div>
                        <dl class="kv">
                            <dt>Nama</dt><dd>{{ $r->name }}</dd><dt>Ruangan</dt><dd>{{ $r->roomType->name }}</dd>
                            <dt>Pesanan</dt><dd>{{ $r->orderLabel() }}</dd><dt>Tanggal</dt><dd>{{ tgl($r->date) }}</dd>
                            <dt>Jam</dt><dd>{{ $r->timeLabel() }} ({{ $r->hours }} jam)</dd><dt>Total</dt><dd>{{ rp($r->total) }}</dd>
                        </dl>
                        <p class="notice notice-info mt-2">{{ $msg }}</p>
                    </div>
                @else
                    <p class="notice" role="alert">Reservasi tidak ditemukan. Periksa lagi kode dan nomor WhatsApp yang kamu pakai saat reservasi.</p>
                @endif
            </div>
        @endif
    </div>
    <p class="caption text-center mt-8">Belum punya reservasi? <a href="{{ route('reservasi') }}">Cek jadwal kosong</a></p>
</div></div></section>
@endsection
