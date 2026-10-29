@extends('layouts.admin', ['title' => 'Tambah reservasi', 'subtitle' => 'Untuk tamu walk-in atau yang memesan lewat telepon. Tidak mengirim notifikasi WhatsApp.'])

@section('actions')
    <a class="btn btn-quiet btn-sm" href="{{ route('admin.jadwal') }}"><x-icon name="left" class="ic-sm" />Kembali ke jadwal</a>
@endsection

@section('content')
{{-- FR-ADM-07: same modes, packages, LC and pricing as the public site --}}
<form method="POST" action="{{ route('admin.reservasi.store') }}" class="card grid gap-x-4 gap-y-6 md:grid-cols-2 max-w-[860px]" x-data="adminReservationForm(@js($config))" novalidate>
    @csrf
    <input type="hidden" name="package_id" :value="pkg ? pkg.id : ''">
    <div class="field">
        <label class="label" for="n-type">Ruangan</label>
        <select class="input" id="n-type" name="room_type_id" x-model.number="typeId">
            <template x-for="t in types" :key="t.id"><option :value="t.id" x-text="t.name" :selected="+typeId === t.id"></option></template>
        </select>
    </div>
    <div class="field">
        <label class="label" for="n-unit">Unit</label>
        <select class="input" id="n-unit" name="room_id" x-model="roomId">
            <option value="">Otomatis (unit kosong)</option>
            <template x-for="u in units" :key="u.id"><option :value="u.id" :disabled="u.busy" :selected="String(roomId) === String(u.id)" x-text="u.name + (u.busy ? ' (terisi)' : '')"></option></template>
        </select>
    </div>
    <div class="field md:col-span-2">
        <label class="label" for="n-mode">Pilihan</label>
        <select class="input" id="n-mode" x-model="mode">
            <template x-if="type?.rentable"><option value="sewa">Sewa ruangan saja (per jam)</option></template>
            <template x-for="p in type?.packages || []" :key="p.id"><option :value="String(p.id)" :selected="mode === String(p.id)" x-text="`${p.brand} · ${p.name} · ${p.price}`"></option></template>
        </select>
    </div>
    <div class="field"><label class="label" for="n-date">Tanggal</label><input class="input" type="date" id="n-date" name="date" x-model="date"></div>
    <div class="field">
        <label class="label" for="n-hour">Jam mulai</label>
        <select class="input" id="n-hour" name="start_hour" x-model.number="hour" :disabled="pkg && pkg.start_hour != null">
            @foreach($hours as $h)<option value="{{ $h }}">{{ hl($h) }}</option>@endforeach
        </select>
        <input type="hidden" name="start_hour" :value="hour" :disabled="!(pkg && pkg.start_hour != null)">
    </div>
    <div class="field">
        <label class="label" for="n-dur">Durasi</label>
        <select class="input" id="n-dur" name="hours" x-model.number="hours" x-show="!pkg">
            @for($n = 1; $n <= $maxHours; $n++)<option value="{{ $n }}">{{ $n }} jam</option>@endfor
        </select>
        <input class="input" x-show="pkg" x-cloak disabled :value="pkg ? pkg.hours + ' jam (paket)' : ''">
        <input type="hidden" name="hours" :value="pkg ? pkg.hours : hours" :disabled="!pkg">
    </div>
    <div class="field">
        <label class="label" for="n-status">Status</label>
        <select class="input" id="n-status" name="status"><option value="dikonfirmasi" @selected(old('status') !== 'menunggu')>Dikonfirmasi</option><option value="menunggu" @selected(old('status') === 'menunggu')>Menunggu</option></select>
    </div>
    <div class="field md:col-span-2" x-show="!pkg && type?.rentable">
        <label class="check self-start" for="n-lc"><input type="checkbox" id="n-lc" name="lc" value="1" x-model="lc"> <span>Dengan LC (pemandu lagu)</span></label>
    </div>
    <div class="field">
        <label class="label" for="n-name">Nama tamu</label>
        <input class="input" id="n-name" name="name" value="{{ old('name') }}" autocomplete="off" required maxlength="100">
        @error('name')<span class="err">{{ $message }}</span>@enderror
    </div>
    <div class="field">
        <label class="label" for="n-phone">WhatsApp (opsional)</label>
        <input class="input" id="n-phone" name="phone" type="tel" inputmode="tel" value="{{ old('phone') }}" autocomplete="off">
        @error('phone')<span class="err">{{ $message }}</span>@enderror
    </div>
    <div class="field">
        <label class="label" for="n-pax">Jumlah orang</label>
        <input class="input" type="number" id="n-pax" name="pax" value="{{ old('pax', 4) }}" min="1" max="30" required>
        @error('pax')<span class="err">{{ $message }}</span>@enderror
    </div>
    <div class="field"><label class="label" for="n-note">Catatan</label><input class="input" id="n-note" name="note" value="{{ old('note') }}" maxlength="500" autocomplete="off"></div>

    <div class="md:col-span-2" aria-live="polite">
        <p class="notice mb-3" x-show="message" x-text="message" x-cloak></p>
        <template x-if="quote">
            <div class="sum">
                <template x-for="(row, i) in quote.rows" :key="i">
                    <div class="sum-row" :class="row.discount && 'disc'"><span><span x-text="row.text"></span> <small class="text-xs text-ink-subtle ml-1" x-text="row.tag"></small></span><b x-text="row.amount"></b></div>
                </template>
                <div class="sum-total"><span>Total</span><b x-text="quote.total"></b></div>
                <span class="caption" x-text="quote.when"></span>
            </div>
        </template>
    </div>
    <div class="md:col-span-2 flex justify-end gap-3">
        <a class="btn btn-quiet btn-sm" href="{{ route('admin.jadwal') }}">Batal</a>
        <button class="btn btn-primary btn-sm" type="submit" :disabled="!quote || !!message">Simpan reservasi</button>
    </div>
</form>
@endsection
