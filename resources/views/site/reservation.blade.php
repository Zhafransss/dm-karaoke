@extends('layouts.public', [
    'nav' => 'reservasi',
    'title' => 'Reservasi Karaoke Online | DM Karaoke & Cafe Rawalo',
    'description' => 'Cek jadwal ruang karaoke yang kosong dan reservasi online di DM Karaoke & Cafe Rawalo, Banyumas. Pilih tanggal, ruangan, dan jam dalam beberapa langkah.',
    'breadcrumbs' => [['Reservasi', route('reservasi')]],
])

@php
    use App\Support\OpHours;
    $open = OpHours::open(); $dayEnd = OpHours::dayEnd(); $close = OpHours::close();
    $limit = config('dmk.rate_limit.reservations_per_hour');
@endphp

@section('content')
<x-page-head crumb="Reservasi" eyebrow="Jadwal & Reservasi" title="Pilih jam yang <em>masih kosong</em>" lead="Jadwal di bawah selalu terbaru. Jam yang sudah dipesan otomatis tertulis penuh." />

<section class="band" x-data="reservationForm(@js($config))"><div class="wrap">
    @if($types->isEmpty())
        <div class="empty">Belum ada ruangan yang bisa dipesan.</div>
    @else
    <div class="grid gap-8 xl:gap-12 xl:grid-cols-[7fr_5fr] items-start">
        <div class="flex flex-col gap-12 min-w-0">
            {{-- Step 1: date --}}
            <section aria-labelledby="st1">
                <h2 class="step-title" id="st1"><span class="step-num">1</span>Pilih tanggal</h2>
                <div class="flex gap-2 overflow-x-auto pb-1" role="group" aria-label="Pilih tanggal">
                    <template x-for="d in days" :key="d.date">
                        <button type="button" class="day" :aria-pressed="(date === d.date).toString()" @click="date = d.date">
                            <small x-text="d.top"></small><b x-text="d.d"></b><small x-text="d.mon"></small>
                        </button>
                    </template>
                </div>
                <div class="flex flex-wrap items-center gap-3 mt-3">
                    <label class="label" for="r-date">Tanggal lain</label>
                    <input class="input !h-10 max-w-[220px]" type="date" id="r-date" min="{{ $today }}" max="{{ $maxDate }}" x-model="date">
                    <span class="caption">Reservasi bisa sampai {{ \App\Support\Settings::get('max_days') }} hari ke depan.</span>
                </div>
            </section>

            {{-- Step 2: availability grid (FR-RES-03) --}}
            <section aria-labelledby="st2">
                <h2 class="step-title" id="st2"><span class="step-num">2</span>Pilih ruangan dan jam</h2>
                <p class="-mt-2 mb-4 text-sm leading-[22px] text-ink-muted">Ketuk jam yang tersedia. Jam yang penuh tidak bisa dipesan. Buka sampai {{ hl($close) }} (jam setelah tengah malam ditandai "dini hari").</p>
                <div class="avail-wrap" :class="loading && 'opacity-60'" aria-live="polite">
                    <div class="grid min-w-max" :style="`grid-template-columns: 148px repeat(${grid[0]?.cells.length || 1}, 78px)`" x-show="grid.length">
                        <div class="avail-h !text-left !pl-3.5 sticky left-0 z-[2] bg-surface">Ruangan</div>
                        <template x-for="c in grid[0]?.cells || []" :key="'h' + c.hour">
                            <div class="avail-h"><span x-text="c.label"></span><span x-show="c.early" class="block text-[10px] leading-3 tracking-[.04em] text-gold mt-0.5">dini hari</span></div>
                        </template>
                        <template x-for="row in grid" :key="'r' + row.id">
                            <div class="contents">
                                <div class="avail-label"><span x-text="row.name"></span><small class="text-ink-subtle font-normal text-xs mt-0.5" x-text="row.sub"></small></div>
                                <template x-for="c in row.cells" :key="row.id + '-' + c.hour">
                                    <button type="button" class="slot"
                                        :class="{ past: c.past, full: !c.past && !c.open, sel: isSel(row.id, c.hour), promo: c.promo }"
                                        :disabled="c.past || !c.open"
                                        :aria-pressed="isSel(row.id, c.hour).toString()"
                                        :aria-label="`${row.name} ${c.label}: ${c.past ? 'sudah lewat' : (!c.open ? 'penuh' : 'tersedia' + (c.promo ? ', ada promo' : ''))}`"
                                        @click="pickSlot(row.id, c.hour)">
                                        <template x-if="c.past"><span>Lewat</span></template>
                                        <template x-if="!c.past && !c.open"><span>Penuh</span></template>
                                        <template x-if="!c.past && c.open"><span>Tersedia<small class="block" x-show="c.promo">Promo</small></span></template>
                                    </button>
                                </template>
                            </div>
                        </template>
                    </div>
                    <p class="p-6 m-0 caption" x-show="!grid.length">Memuat jadwal…</p>
                </div>
                <div class="legend">
                    <span><i class="bg-surface-raised border-gold-deep"></i>Tersedia</span><span><i class="bg-gold border-gold"></i>Pilihanmu</span>
                    <span><i class="bg-danger-soft border-danger-soft"></i>Penuh</span><span><i class="border-dashed border-line-strong"></i>Sudah lewat</span>
                    <span class="text-gold">Teks emas: ada promo untuk pilihanmu</span>
                </div>

                <div class="mt-8" aria-live="polite" x-show="grid.length" x-cloak>
                    <h3 class="m-0 mb-1 font-display text-[24px] leading-7 font-semibold">Promo aktif di tanggal ini</h3>
                    <p class="m-0 mb-3 caption" x-show="promos.length">Ketuk promo untuk memakainya, lalu tinggal pilih jam. Satu reservasi memakai satu promo; bila kamu tidak memilih, yang paling hemat otomatis dipakai.</p>
                    <p class="m-0 caption" x-show="!promos.length">Tidak ada promo yang berlaku di tanggal ini.</p>
                    <div class="flex flex-col gap-3" x-show="promos.length">
                        <template x-for="p in promos" :key="p.id">
                            <article class="grid gap-3 md:gap-6 md:grid-cols-[112px_minmax(0,1fr)] items-center p-4 border rounded-md cursor-pointer transition-colors hover:border-gold-deep" :class="quote?.promo_id === p.id ? 'border-gold bg-gold-soft' : 'border-line bg-surface-raised'"
                                role="button" tabindex="0" :aria-label="`Pakai promo ${p.name}`"
                                @click="applyPromo(p)" @keydown.enter.prevent="applyPromo(p)" @keydown.space.prevent="applyPromo(p)">
                                <div class="flex md:flex-col items-center md:justify-center gap-3 md:gap-1 md:w-[112px] md:h-24 px-4 py-3 md:p-1.5 bg-gold-soft border border-gold-deep rounded-sm text-center">
                                    <b class="font-display text-[32px] leading-none text-gold font-semibold tabular" x-text="p.big"></b>
                                    <span class="text-[10px] leading-[14px] tracking-[.12em] uppercase text-gold" x-text="p.unit"></span>
                                </div>
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h4 class="m-0 font-display text-2xl leading-7 font-semibold text-ink" x-text="p.name"></h4>
                                        <span class="badge badge-dot" :class="p.state === 'berjalan' ? 'badge-success' : 'badge-gold'" x-text="p.state === 'berjalan' ? 'Berjalan' : 'Terjadwal'"></span>
                                        <span class="badge badge-success badge-dot" x-show="quote?.promo_id === p.id">Dipakai</span>
                                        <span class="badge badge-gold" x-show="promoFits(p) && quote?.promo_id !== p.id">Berlaku untuk pilihanmu</span>
                                    </div>
                                    <p class="mt-1.5 mb-0 text-[13px] leading-5 text-ink-subtle" x-text="`${p.for} · ${p.period}`"></p>
                                    <p class="mt-1 mb-0 text-sm leading-[22px] text-ink-muted" x-text="p.text"></p>
                                </div>
                            </article>
                        </template>
                    </div>
                </div>
            </section>
        </div>

        {{-- Step 3: form --}}
        <aside class="xl:sticky xl:top-24 min-w-0" aria-labelledby="st3">
            <div class="panel scroll-mt-24" id="res-panel">
                <h2 class="step-title" id="st3"><span class="step-num">3</span>Isi data reservasi</h2>
                <form method="POST" action="{{ route('reservasi.store') }}" class="grid gap-x-4 gap-y-6 sm:grid-cols-2" novalidate>
                    @csrf
                    <input type="hidden" name="date" :value="date">
                    <input type="hidden" name="room_type_id" :value="typeId">
                    <input type="hidden" name="package_id" :value="pkg ? pkg.id : ''">
                    <input type="hidden" name="start_hour" :value="hour ?? ''">
                    <input type="hidden" name="hours" :value="effHours()">
                    {{-- The promo shown in the summary is the one that gets saved. --}}
                    <input type="hidden" name="promo_id" :value="quote?.promo_id ?? ''">

                    <div class="field sm:col-span-2">
                        <label class="label" for="r-type">Ruangan</label>
                        <select class="input" id="r-type" :value="typeId" @change="pickType($event.target.value)">
                            <template x-for="t in types" :key="t.id"><option :value="t.id" x-text="t.name + (t.cap ? ` (maks. ${t.cap} orang)` : '')" :selected="+typeId === t.id"></option></template>
                        </select>
                    </div>
                    <div class="field sm:col-span-2">
                        <label class="label" for="r-mode">Pilihan</label>
                        <select class="input" id="r-mode" x-model="mode">
                            <template x-if="type?.rentable"><option value="sewa">Sewa ruangan saja (per jam)</option></template>
                            <template x-for="p in type?.packages || []" :key="p.id">
                                <option :value="String(p.id)" x-text="`${p.brand} · ${p.name} · ${p.price}` + (p.start_label ? ` · mulai ${p.start_label}` : '')" :selected="mode === String(p.id)"></option>
                            </template>
                        </select>
                        <span class="help" x-show="pkg" x-text="pkg ? pkg.items.join(', ') + '.' + (pkg.start_label ? ` Paket ini hanya bisa dimulai pukul ${pkg.start_label}.` : '') : ''"></span>
                        <span class="help" x-show="!pkg && type" x-text="type ? `Bayar per jam, minimal 1 jam. Siang {{ hl($open) }}–{{ hl($dayEnd) }} Rp ${type.rateDay.toLocaleString('id-ID')}/jam tanpa LC, malam Rp ${type.rateNight.toLocaleString('id-ID')}/jam.` : ''"></span>
                    </div>
                    <div class="field">
                        <label class="label" for="r-hour">Jam mulai</label>
                        <select class="input" id="r-hour" x-model.number="hour" :disabled="!starts.some(s => s.ok)">
                            <template x-for="s in starts" :key="s.hour">
                                <option :value="s.hour" :disabled="!s.ok" :selected="+hour === s.hour" x-text="s.label + (s.past ? ' · lewat' : (s.ok ? '' : ' · tidak tersedia'))"></option>
                            </template>
                        </select>
                    </div>
                    <div class="field">
                        <label class="label" for="r-dur">Durasi</label>
                        <select class="input" id="r-dur" x-model.number="hours" :disabled="!!pkg || !durations.length">
                            <template x-for="n in durations" :key="n"><option :value="n" :selected="effHours() === n" x-text="n + ' jam' + (pkg ? ' (paket)' : '')"></option></template>
                        </select>
                    </div>
                    <div class="field sm:col-span-2" x-show="!pkg && type?.rentable">
                        <label class="check items-start" for="r-lc"><input type="checkbox" id="r-lc" name="lc" value="1" x-model="lc" class="mt-0.5"> <span>Dengan LC (pemandu lagu, 21+)</span></label>
                        <span class="help" x-text="type ? `Siang {{ hl($open) }}–{{ hl($dayEnd) }}: dengan LC cukup deposit uang` + (type.deposit ? ` ${type.deposit}` : '') + `, atau Rp ${type.rateDay.toLocaleString('id-ID')}/jam tanpa LC.` : ''"></span>
                    </div>
                    <div class="field sm:col-span-2">
                        <label class="label" for="r-name">Nama pemesan</label>
                        <input class="input" id="r-name" name="name" value="{{ old('name') }}" autocomplete="name" placeholder="Nama lengkap" required maxlength="100" @error('name') aria-invalid="true" aria-describedby="r-name-err" @enderror>
                        @error('name')<span class="err" id="r-name-err">{{ $message }}</span>@enderror
                    </div>
                    <div class="field">
                        <label class="label" for="r-phone">Nomor WhatsApp</label>
                        <input class="input" id="r-phone" name="phone" type="tel" inputmode="tel" value="{{ old('phone') }}" autocomplete="tel" placeholder="08xx xxxx xxxx" required @error('phone') aria-invalid="true" aria-describedby="r-phone-err" @enderror>
                        @error('phone')<span class="err" id="r-phone-err">{{ $message }}</span>@enderror
                    </div>
                    <div class="field">
                        <label class="label" for="r-pax">Jumlah orang</label>
                        <input class="input" id="r-pax" name="pax" type="number" min="1" :max="type?.maxPax" value="{{ old('pax', 4) }}" inputmode="numeric" required @error('pax') aria-invalid="true" aria-describedby="r-pax-err" @enderror>
                        @error('pax')<span class="err" id="r-pax-err">{{ $message }}</span>@enderror
                    </div>
                    <div class="field sm:col-span-2">
                        <label class="label" for="r-note">Catatan (opsional)</label>
                        <textarea class="input !min-h-[72px]" id="r-note" name="note" rows="2" maxlength="500" placeholder="Contoh: acara ulang tahun">{{ old('note') }}</textarea>
                    </div>

                    <div class="sm:col-span-2" aria-live="polite">
                        <template x-if="quote">
                            <div class="sum">
                                <template x-for="(row, i) in quote.rows" :key="i">
                                    <div class="sum-row" :class="row.discount && 'disc'"><span><span x-text="row.text"></span> <small class="text-xs text-ink-subtle ml-1" x-text="row.tag"></small></span><b x-text="row.amount"></b></div>
                                </template>
                                <div class="sum-total"><span>Total</span><b x-text="quote.total"></b></div>
                                <span class="caption" x-text="quote.when"></span>
                                <span class="caption" x-show="quote.lc_note" x-text="quote.lc_note"></span>
                                <p class="notice notice-info mt-3 mb-0" x-show="quote.pay_note" x-text="quote.pay_note"></p>
                                <p class="notice notice-info mt-3 mb-0" x-show="quote.promo_id">Promo dapat diterapkan ketika pelanggan dapat menunjukkan KTP pada kasir ketika pembayaran. Pastikan datang ketika hari ulang tahun anda ya</p>
                            </div>
                        </template>
                        <p class="notice" x-show="hour === null && starts.length" x-cloak>Tidak ada jam yang tersedia untuk pilihan ini di tanggal tersebut. Pilih tanggal atau pilihan lain.</p>
                    </div>
                    <p class="notice sm:col-span-2" role="alert" x-show="error" x-text="error" x-cloak></p>
                    <div class="sm:col-span-2"><button class="btn btn-primary btn-block" type="submit" :disabled="hour === null || !quote">Kirim reservasi</button></div>
                    <p class="caption sm:col-span-2 m-0">Belum perlu bayar. Kami kirim detail reservasi ke WhatsApp-mu. Satu jaringan maksimal {{ $limit }} reservasi per jam. Lihat <a href="{{ route('harga') }}">daftar harga lengkap</a>.</p>
                </form>
            </div>
        </aside>
    </div>
    @endif
</div></section>
@endsection
