import Alpine from 'alpinejs';

import '@fontsource/jost/400.css';
import '@fontsource/jost/500.css';
import '@fontsource/jost/600.css';
import '@fontsource/cormorant-garamond/400.css';
import '@fontsource/cormorant-garamond/500.css';
import '@fontsource/cormorant-garamond/600.css';
import '@fontsource/cormorant-garamond/400-italic.css';

const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content;

async function getJson(url, params) {
    const res = await fetch(url + '?' + new URLSearchParams(params), { headers: { Accept: 'application/json' } });
    if (!res.ok) throw new Error('HTTP ' + res.status);
    return res.json();
}

async function postJson(url, body) {
    const res = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf() },
        body: JSON.stringify(body),
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok && !data.message) data.message = 'Terjadi kesalahan. Coba lagi.';
    return { ok: res.ok, data };
}

/* Public reservation form (FR-RES-01..07). Prices always come from the server. */
Alpine.data('reservationForm', (cfg) => ({
    ...cfg.state,
    days: cfg.days,
    types: cfg.types,
    grid: [],
    starts: [],
    promos: [],
    pendingPromo: null,
    quote: null,
    loading: false,
    error: cfg.error || '',

    init() {
        this.load();
        ['date', 'typeId', 'mode'].forEach((k) => this.$watch(k, () => this.load()));
        ['hour', 'hours', 'lc'].forEach((k) => this.$watch(k, () => this.price()));
    },
    get type() { return this.types.find((t) => t.id === +this.typeId); },
    get pkg() { return this.mode === 'sewa' ? null : this.type?.packages.find((p) => p.id === +this.mode); },
    get startOpt() { return this.starts.find((s) => s.hour === +this.hour); },
    get durations() {
        if (this.pkg) return [this.pkg.hours];
        const max = Math.min(this.startOpt?.max || 0, cfg.maxHours);
        return Array.from({ length: max }, (_, i) => i + 1);
    },
    pickType(id) {
        if (+this.typeId === +id) return;
        this.typeId = +id;
        const t = this.type;
        this.mode = t.rentable ? 'sewa' : String(t.packages[0]?.id ?? 'sewa');
    },
    pickSlot(typeId, hour) {
        if (+this.typeId !== +typeId) { this.typeId = +typeId; this.mode = this.type.rentable ? 'sewa' : String(this.type.packages[0]?.id); }
        this.hour = hour;
        this.load();
        const panel = document.getElementById('res-panel');
        if (window.innerWidth < 1024) panel?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    },
    isSel(typeId, hour) {
        return +this.typeId === +typeId && this.hour !== null && hour >= +this.hour && hour < +this.hour + this.effHours();
    },
    effHours() { return this.pkg ? this.pkg.hours : +this.hours; },
    promoFits(p) { return p.targets.includes(this.pkg ? 'pkg:' + this.pkg.id : 'rent:' + this.typeId); },
    /* Closest bookable choice for a promo: the current room first (current mode first), then rooms in order. */
    promoTarget(p) {
        if (this.promoFits(p)) return { typeId: +this.typeId, mode: String(this.mode) };
        const rooms = [this.type, ...this.types.filter((t) => t.id !== +this.typeId)].filter(Boolean);
        for (const t of rooms) {
            const rent = t.rentable && p.targets.includes('rent:' + t.id) ? { typeId: t.id, mode: 'sewa' } : null;
            const pk = t.packages.find((k) => p.targets.includes('pkg:' + k.id));
            const pack = pk ? { typeId: t.id, mode: String(pk.id) } : null;
            const pick = t.id === +this.typeId && this.pkg ? pack || rent : rent || pack;
            if (pick) return pick;
        }
        return null;
    },
    /* Tapping a promo card switches room + choice to it; load() then jumps to its first promo hour. */
    applyPromo(p) {
        const t = this.promoTarget(p);
        if (!t) return;
        this.pendingPromo = p;
        const same = +this.typeId === t.typeId && String(this.mode) === t.mode;
        this.typeId = t.typeId;
        this.mode = t.mode;
        if (same) this.load();
        document.getElementById('st2')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    },
    async load() {
        this.loading = true;
        try {
            const d = await getJson(cfg.urls.availability, { date: this.date, tipe: this.typeId, mode: this.mode });
            this.grid = d.grid;
            this.starts = d.starts;
            this.promos = d.promos;
            const ok = this.starts.filter((s) => s.ok);
            if (this.hour === null || !ok.find((s) => s.hour === +this.hour)) this.hour = ok[0]?.hour ?? null;
            if (this.pendingPromo) {
                const p = this.pendingPromo;
                this.pendingPromo = null;
                const row = this.grid.find((r) => r.id === +this.typeId);
                const promoHours = new Set((row?.cells || []).filter((c) => c.promo).map((c) => c.hour));
                // "Sewa X bayar Y" on rental needs a start with room for X hours.
                const start = ok.find((s) => promoHours.has(s.hour) && (this.pkg || !p.x || s.max >= p.x));
                if (start) this.hour = start.hour;
                if (p.x && !this.pkg) this.hours = Math.max(+this.hours, p.x);
            }
            const max = this.startOpt?.max || 1;
            if (!this.pkg) this.hours = Math.max(1, Math.min(+this.hours || 2, max));
            await this.price();
        } catch (e) {
            this.error = 'Jadwal gagal dimuat. Periksa koneksi lalu muat ulang halaman.';
        } finally {
            this.loading = false;
        }
    },
    async price() {
        if (this.hour === null) { this.quote = null; return; }
        const { ok, data } = await postJson(cfg.urls.price, {
            room_type_id: this.typeId, package_id: this.pkg?.id ?? null, date: this.date,
            start_hour: this.hour, hours: this.effHours(), lc: !!this.lc,
        });
        this.quote = ok ? data : null;
        if (!ok) this.error = data.message;
        else this.error = '';
    },
}));

/* Admin "Tambah reservasi" (FR-ADM-07): same pricing endpoint plus unit availability. */
Alpine.data('adminReservationForm', (cfg) => ({
    ...cfg.state,
    types: cfg.types,
    units: [],
    quote: null,
    message: '',
    init() {
        this.refresh();
        ['typeId', 'mode', 'date', 'hour', 'hours', 'lc', 'roomId'].forEach((k) => this.$watch(k, () => this.refresh()));
        this.$watch('typeId', () => {
            const t = this.type;
            this.mode = t.rentable ? 'sewa' : String(t.packages[0]?.id ?? 'sewa');
            this.roomId = '';
        });
    },
    get type() { return this.types.find((t) => t.id === +this.typeId); },
    get pkg() { return this.mode === 'sewa' ? null : this.type?.packages.find((p) => p.id === +this.mode); },
    async refresh() {
        if (this.pkg?.start_hour != null) this.hour = this.pkg.start_hour;
        const { ok, data } = await postJson(cfg.urls.preview, {
            room_type_id: this.typeId, package_id: this.pkg?.id ?? null, date: this.date, start_hour: +this.hour,
            hours: this.pkg ? this.pkg.hours : +this.hours, lc: !!this.lc, room_id: this.roomId || null, ignore: cfg.ignore || null,
        });
        this.units = data.units || [];
        this.quote = ok ? data.quote : null;
        this.message = ok ? (data.message || '') : data.message;
    },
}));

/* Admin "Pindah unit atau jam" (FR-ADM-09): marks units already taken at the chosen date/time as "(Dipakai)". */
Alpine.data('moveForm', (cfg) => ({
    ...cfg.state,
    units: cfg.units,
    init() {
        this.refresh();
        ['date', 'hour'].forEach((k) => this.$watch(k, () => this.refresh()));
    },
    async refresh() {
        const r = cfg.reservation;
        const { data } = await postJson(cfg.url, {
            room_type_id: r.type, package_id: r.package, date: this.date, start_hour: +this.hour,
            hours: r.hours, lc: r.lc, ignore: r.id,
        });
        const busy = Object.fromEntries((data.units || []).map((u) => [u.id, u.busy]));
        this.units = this.units.map((u) => ({ ...u, busy: !!busy[u.id] }));
    },
}));

/*
 * Admin promo form: live example calculation and overlap warning (FR-ADM-12/13).
 * Uses $root, not $el: when a method runs from @click, $el is the clicked button, not the form.
 */
Alpine.data('promoForm', (cfg) => ({
    ...cfg.state,
    preview: '',
    conflicts: [],
    init() {
        this.$nextTick(() => this.refresh());
        this.$root.addEventListener('change', () => this.$nextTick(() => this.refresh()));
        this.$root.addEventListener('input', (e) => { if (e.target.type === 'number' || e.target.type === 'text') this.refresh(); });
    },
    setAll(typeId, on) {
        this.$root.querySelectorAll(`[data-type="${typeId}"] input[name="targets[]"]:not(:disabled)`).forEach((c) => { c.checked = on; });
        this.refresh();
    },
    async refresh() {
        const fd = new FormData(this.$root);
        const body = Object.fromEntries([...fd.entries()].filter(([k]) => k !== 'targets[]' && k !== '_token' && k !== '_method'));
        body.targets = fd.getAll('targets[]');
        body.window = this.window;
        body.active = this.$root.querySelector('[name="active"]')?.checked ?? false;
        const { data } = await postJson(cfg.urls.preview, body);
        this.preview = data.preview || '';
        this.conflicts = data.conflicts || [];
    },
}));

/* Lightbox for the public gallery (FR-PUB-05). */
Alpine.data('lightbox', (items) => ({
    items,
    idx: null,
    get cur() { return this.idx === null ? null : this.items[this.idx]; },
    open(i) { this.idx = i; this.$nextTick(() => this.$refs.close?.focus()); },
    close() { this.idx = null; },
    step(d) { this.idx = (this.idx + d + this.items.length) % this.items.length; },
}));

/* Open/close panel whose state survives reloads (e.g. after toggling a package). Closed by default. */
Alpine.data('collapsible', (key) => ({
    open: false,
    init() {
        try { this.open = localStorage.getItem('dmk.open.' + key) === '1'; } catch (e) { /* storage blocked: stay closed */ }
    },
    toggle() {
        this.open = !this.open;
        try { localStorage.setItem('dmk.open.' + key, this.open ? '1' : '0'); } catch (e) { /* not persisted */ }
    },
}));

/* Replace a file input's preview image. */
Alpine.data('imagePick', (src = '') => ({
    src,
    pick(e) { const f = e.target.files[0]; if (f) this.src = URL.createObjectURL(f); },
}));

/*
 * Confirmation dialog for destructive forms:
 * <form data-confirm="Hapus promo?" data-confirm-text="..." data-confirm-label="Hapus">.
 */
Alpine.store('confirm', { open: false, title: '', text: '', label: '', form: null });
document.addEventListener('submit', (e) => {
    const f = e.target;
    if (!f.dataset.confirm || f.dataset.confirmed) return;
    e.preventDefault();
    Object.assign(Alpine.store('confirm'), { open: true, title: f.dataset.confirm, text: f.dataset.confirmText || '', label: f.dataset.confirmLabel || 'Ya, lanjutkan', form: f });
});
window.confirmYes = () => {
    const s = Alpine.store('confirm');
    s.open = false;
    if (s.form) { s.form.dataset.confirmed = '1'; s.form.requestSubmit ? s.form.requestSubmit() : s.form.submit(); }
};

window.copyText = async (text, el) => {
    try { await navigator.clipboard.writeText(text); } catch (e) { /* older browsers: user can still select manually */ }
    if (el) { const old = el.innerHTML; el.textContent = 'Disalin'; setTimeout(() => { el.innerHTML = old; }, 1500); }
};

window.Alpine = Alpine;
Alpine.start();
