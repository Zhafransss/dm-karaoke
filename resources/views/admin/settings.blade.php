@extends('layouts.admin', ['title' => 'Pengaturan', 'subtitle' => 'Jam operasional, reservasi, dan notifikasi WhatsApp.'])

@php
    $closeLabel = fn ($h) => $h === 24 ? '00.00 (tengah malam)' : ($h > 24 ? hl($h).' (esok hari)' : hl($h));
    $statusBadge = ['sent' => ['badge-success', 'Terkirim'], 'simulated' => ['badge-gold', 'Simulasi'], 'failed' => ['badge-danger', 'Gagal'], 'pending' => ['', 'Antre']];
@endphp

@section('content')
{{-- FR-ADM-17 --}}
<form method="POST" action="{{ route('admin.pengaturan.update') }}" class="flex flex-col gap-6">
    @csrf @method('PUT')
    <section class="card">
        <h2>Jam operasional &amp; reservasi</h2>
        <div class="grid gap-x-4 gap-y-6 md:grid-cols-2">
            <div class="field"><label class="label" for="s-open">Jam buka</label><select class="input" id="s-open" name="open">@for($h = 0; $h < 24; $h++)<option value="{{ $h }}" @selected(old('open', $s['open']) == $h)>{{ hl($h) }}</option>@endfor</select></div>
            <div class="field"><label class="label" for="s-close">Jam tutup</label><select class="input" id="s-close" name="close">@for($h = 1; $h <= 31; $h++)<option value="{{ $h }}" @selected(old('close', $s['close']) == $h)>{{ $closeLabel($h) }}</option>@endfor</select></div>
            <div class="field"><label class="label" for="s-dayend">Batas tarif siang</label><select class="input" id="s-dayend" name="day_end">@for($h = 1; $h <= 31; $h++)<option value="{{ $h }}" @selected(old('day_end', $s['day_end']) == $h)>{{ $closeLabel($h) }}</option>@endfor</select>
                <span class="help">Sebelum jam ini berlaku tarif siang: gratis dengan LC dan deposit, atau tarif siang tanpa LC.</span></div>
            <div class="field"><label class="label" for="s-max">Reservasi paling jauh (hari ke depan)</label><input class="input" type="number" id="s-max" name="max_days" min="1" max="120" value="{{ old('max_days', $s['max_days']) }}"></div>
            <div class="field md:col-span-2"><label class="label" for="s-deposit">Besaran deposit (tampil di website)</label><input class="input" id="s-deposit" name="deposit" value="{{ old('deposit', $s['deposit']) }}" placeholder="Contoh: Rp 200.000" maxlength="60">
                <span class="help">Deposit berupa uang, dibayar di tempat. Kosongkan bila belum ditentukan; website akan menulis "besarnya diinformasikan admin saat konfirmasi".</span></div>
            <p class="help md:col-span-2 m-0">Anti-spam: maksimal {{ config('dmk.rate_limit.reservations_per_hour') }} reservasi per jam dari IP yang sama. Reservasi selalu mulai di jam bulat, minimal 1 jam, tanpa jeda antar booking.</p>
        </div>
    </section>

    <section class="card">
        <h2>Kontak bisnis</h2>
        <div class="grid gap-x-4 gap-y-6 md:grid-cols-2">
            <div class="field"><label class="label" for="s-wad">Nomor WhatsApp (tampil di website)</label><input class="input" id="s-wad" name="wa_display" value="{{ old('wa_display', $s['wa_display']) }}"></div>
            <div class="field"><label class="label" for="s-wan">Nomor untuk tombol chat (wa.me)</label><input class="input" id="s-wan" name="wa_number" value="{{ old('wa_number', $s['wa_number']) }}" inputmode="tel"></div>
        </div>
    </section>

    <section class="card">
        <h2>WhatsApp gateway</h2>
        <p class="-mt-2 mb-4 text-sm leading-[22px] text-ink-subtle">Notifikasi dikirim saat reservasi online masuk: ke nomor admin dan ke tamu. Pakai nomor khusus untuk gateway, bukan nomor utama bisnis, agar risiko blokir tidak mengenai nomor utama.</p>
        <div class="grid gap-x-4 gap-y-6 md:grid-cols-2">
            <div class="field"><label class="label" for="s-gw">Penyedia gateway</label><select class="input" id="s-gw" name="wa_gateway">@foreach(config('dmk.wa_gateways') as $k => $l)<option value="{{ $k }}" @selected(old('wa_gateway', $s['wa_gateway']) === $k)>{{ $l }}</option>@endforeach</select></div>
            <div class="field"><label class="label" for="s-token">Token API</label><input class="input" id="s-token" name="wa_token" type="password" autocomplete="off" placeholder="{{ $s['wa_token'] ? 'Tersimpan. Isi untuk mengganti.' : 'Dari dashboard gateway' }}"><span class="help">Disimpan terenkripsi.</span></div>
            <div class="field md:col-span-2"><label class="label" for="s-wa">Nomor WhatsApp admin (penerima notifikasi)</label><input class="input" id="s-wa" name="wa_admin" type="tel" value="{{ old('wa_admin', $s['wa_admin']) }}"></div>
            <label class="switch md:col-span-2"><input type="checkbox" name="wa_auto_status" value="1" @checked(old('wa_auto_status', $s['wa_auto_status']))> Kirim WhatsApp otomatis ke tamu saat reservasi dikonfirmasi atau dibatalkan</label>
        </div>
    </section>

    <section class="card">
        <h2>Template pesan</h2>
        <p class="-mt-2 mb-2 text-sm leading-[22px] text-ink-subtle">Variabel yang bisa dipakai:</p>
        <div class="vars flex flex-wrap gap-1.5 mb-4">@foreach(config('dmk.template_vars') as $v)<code>{{ '{'.$v.'}' }}</code>@endforeach</div>
        <div class="grid gap-x-4 gap-y-6 md:grid-cols-2">
            <div class="field"><label class="label" for="s-tadmin">Ke admin saat reservasi masuk</label><textarea class="input" id="s-tadmin" name="tpl_admin" rows="9">{{ old('tpl_admin', $s['tpl_admin']) }}</textarea></div>
            <div class="field"><label class="label" for="s-tguest">Ke tamu saat reservasi masuk</label><textarea class="input" id="s-tguest" name="tpl_guest" rows="9">{{ old('tpl_guest', $s['tpl_guest']) }}</textarea></div>
            <div class="field"><label class="label" for="s-tconf">Saat dikonfirmasi</label><textarea class="input" id="s-tconf" name="tpl_confirm" rows="7">{{ old('tpl_confirm', $s['tpl_confirm']) }}</textarea></div>
            <div class="field"><label class="label" for="s-trej">Saat ditolak atau dibatalkan</label><textarea class="input" id="s-trej" name="tpl_reject" rows="7">{{ old('tpl_reject', $s['tpl_reject']) }}</textarea></div>
        </div>
        <p class="help mt-3 mb-0">Template konfirmasi dan pembatalan dipakai untuk pesan otomatis (bila diaktifkan) dan untuk tombol "Kabari tamu" di detail reservasi.</p>
    </section>

    <section class="card">
        <h2>Analitik (opsional)</h2>
        <div class="grid gap-x-4 gap-y-6 md:grid-cols-2">
            <div class="field"><label class="label" for="s-ga">ID Google Analytics 4</label><input class="input" id="s-ga" name="analytics_id" value="{{ old('analytics_id', $s['analytics_id']) }}" placeholder="G-XXXXXXX"></div>
            <div class="field"><label class="label" for="s-px">ID Meta Pixel</label><input class="input" id="s-px" name="meta_pixel_id" value="{{ old('meta_pixel_id', $s['meta_pixel_id']) }}" inputmode="numeric"></div>
        </div>
    </section>

    <div class="flex flex-wrap justify-end gap-3">
        <button class="btn btn-quiet btn-sm" type="submit" form="wa-test"><x-icon name="chat" class="ic-sm" />Kirim pesan uji</button>
        <button class="btn btn-primary btn-sm" type="submit">Simpan pengaturan</button>
    </div>
</form>
<form method="POST" action="{{ route('admin.pengaturan.tes-wa') }}" id="wa-test">@csrf</form>

<section class="card scroll-mt-28" id="log">
    <h2>Log notifikasi WhatsApp <span class="caption">{{ $logs->total() }} pesan</span></h2>
    @if($logs->isEmpty())
        <p class="empty-sm">Belum ada notifikasi.</p>
    @else
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th scope="col">Waktu</th><th scope="col">Tujuan</th><th scope="col">Pesan</th><th scope="col">Status</th></tr></thead>
                <tbody>
                    @foreach($logs as $l)
                        <tr>
                            <td class="text-ink-muted whitespace-nowrap">{{ tgl($l->created_at, 'datetime') }}</td>
                            <td><b class="font-medium">{{ $l->recipient === 'admin' ? 'Admin' : 'Tamu' }}</b><div class="caption">{{ $l->to }}</div></td>
                            <td><p class="m-0 whitespace-pre-wrap text-[13px] leading-5 text-ink-muted max-w-[56ch]">{{ $l->body }}</p></td>
                            <td><span class="badge {{ $statusBadge[$l->status][0] ?? '' }}">{{ $statusBadge[$l->status][1] ?? $l->status }}</span>
                                <div class="caption mt-1">{{ $l->attempts }}× percobaan</div>
                                @if($l->status === 'failed' && $l->response)<div class="caption mt-1 max-w-[220px] truncate" title="{{ $l->response }}">{{ $l->response }}</div>@endif</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $logs->fragment('log')->links() }}
    @endif
</section>
@endsection
