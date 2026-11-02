import Alpine from 'alpinejs';

/*
 * Photo preview + crop before upload. Any <input type="file" data-crop data-crop-ratio="4:3">
 * is intercepted: each image is shown in a modal where the admin picks an aspect ratio and
 * moves/zooms the photo under a fixed frame. The cropped result replaces the file in the input,
 * then a normal "change" event is re-dispatched so the page's own preview handlers still run.
 * Videos pass through untouched. No dependencies; the server still converts to WebP.
 */

const RATIOS = [
    { key: 'orig', label: 'Asli', value: null },
    { key: '4:3', label: '4:3', value: 4 / 3 },
    { key: '16:9', label: '16:9', value: 16 / 9 },
    { key: '1:1', label: '1:1', value: 1 },
    { key: '3:4', label: '3:4', value: 3 / 4 },
    { key: '4:5', label: '4:5', value: 4 / 5 },
];

const MAX_SIDE = 1600; // same ceiling as ImageService, so nothing is upscaled for nothing
const isImage = (f) => f.type.startsWith('image/');

/* Intercept the original change event (capture phase) before the page's handlers see it. */
document.addEventListener('change', (e) => {
    const input = e.target;
    if (!(input instanceof HTMLInputElement) || input.type !== 'file' || !input.hasAttribute('data-crop')) return;
    if (input.dataset.cropDone) { delete input.dataset.cropDone; return; }

    const files = [...input.files];
    if (!files.some(isImage)) return;
    e.stopImmediatePropagation();

    const finish = (result) => {
        if (result && result.length) {
            const dt = new DataTransfer();
            result.forEach((f) => dt.items.add(f));
            input.files = dt.files;
        } else {
            input.value = '';
        }
        input.dataset.cropDone = '1';
        input.dispatchEvent(new Event('change', { bubbles: true }));
    };
    window.dispatchEvent(new CustomEvent('dmk-crop', { detail: { files, ratio: input.dataset.cropRatio || 'orig', finish } }));
}, true);

Alpine.data('imageCropper', () => ({
    ratios: RATIOS,
    open: false,
    queue: [],
    results: [],
    idx: 0,
    finish: null,
    ratioKey: 'orig',
    zoom: 1,
    img: null, // HTMLImageElement
    url: '',
    stage: { w: 560, h: 420 },
    cx: 0, // image centre inside the stage
    cy: 0,
    drag: null,

    start(detail) {
        this.queue = detail.files;
        this.results = [];
        this.idx = -1;
        this.finish = detail.finish;
        this.ratioKey = RATIOS.some((r) => r.key === detail.ratio) ? detail.ratio : 'orig';
        this.next();
    },

    next() {
        this.release();
        this.idx++;
        if (this.idx >= this.queue.length) return this.done(this.results);
        const file = this.queue[this.idx];
        if (!isImage(file)) { this.results.push(file); return this.next(); }

        const url = URL.createObjectURL(file);
        const img = new Image();
        img.onload = () => {
            this.url = url;
            this.img = img;
            this.open = true;
            this.$nextTick(() => this.layout());
        };
        // Unreadable in the browser (e.g. HEIC): keep the original and let the server decide.
        img.onerror = () => { URL.revokeObjectURL(url); this.results.push(file); this.next(); };
        img.src = url;
    },

    release() {
        if (this.url) URL.revokeObjectURL(this.url);
        this.url = '';
        this.img = null;
    },

    done(result) {
        this.open = false;
        this.release();
        const finish = this.finish;
        this.finish = null;
        if (finish) finish(result);
    },

    layout() {
        const w = Math.min(560, window.innerWidth - 64);
        this.stage = { w, h: Math.round(w * 0.78) };
        this.zoom = 1;
        this.cx = this.stage.w / 2;
        this.cy = this.stage.h / 2;
        this.clamp();
    },

    setRatio(key) {
        this.ratioKey = key;
        this.zoom = 1;
        this.cx = this.stage.w / 2;
        this.cy = this.stage.h / 2;
        this.clamp();
    },

    /* The crop frame: chosen ratio (or the photo's own), as large as fits the stage with a margin. */
    get frame() {
        if (!this.img) return { x: 0, y: 0, w: 0, h: 0 };
        const r = RATIOS.find((x) => x.key === this.ratioKey).value ?? this.img.naturalWidth / this.img.naturalHeight;
        const maxW = this.stage.w - 32;
        const maxH = this.stage.h - 32;
        let w = maxW;
        let h = w / r;
        if (h > maxH) { h = maxH; w = h * r; }
        return { x: (this.stage.w - w) / 2, y: (this.stage.h - h) / 2, w, h };
    },

    /* Displayed pixels per source pixel. At zoom 1 the photo exactly covers the frame. */
    get scale() {
        if (!this.img) return 1;
        const f = this.frame;
        return Math.max(f.w / this.img.naturalWidth, f.h / this.img.naturalHeight) * this.zoom;
    },

    get imgStyle() {
        if (!this.img) return '';
        const w = this.img.naturalWidth * this.scale;
        const h = this.img.naturalHeight * this.scale;
        return `width:${w}px;height:${h}px;left:${this.cx - w / 2}px;top:${this.cy - h / 2}px`;
    },

    get frameStyle() {
        const f = this.frame;
        return `left:${f.x}px;top:${f.y}px;width:${f.w}px;height:${f.h}px`;
    },

    /* Keep the photo covering the whole frame. */
    clamp() {
        if (!this.img) return;
        const f = this.frame;
        const w = this.img.naturalWidth * this.scale;
        const h = this.img.naturalHeight * this.scale;
        this.cx = Math.min(f.x + w / 2, Math.max(f.x + f.w - w / 2, this.cx));
        this.cy = Math.min(f.y + h / 2, Math.max(f.y + f.h - h / 2, this.cy));
    },

    /* Region of the source photo inside the frame, in source pixels. */
    get region() {
        if (!this.img) return { sx: 0, sy: 0, sw: 0, sh: 0 };
        const f = this.frame;
        const s = this.scale;
        const left = this.cx - (this.img.naturalWidth * s) / 2;
        const top = this.cy - (this.img.naturalHeight * s) / 2;
        return { sx: (f.x - left) / s, sy: (f.y - top) / s, sw: f.w / s, sh: f.h / s };
    },

    get outSize() {
        const { sw, sh } = this.region;
        if (!sw) return '';
        const k = Math.min(1, MAX_SIDE / Math.max(sw, sh));
        return `${Math.round(sw * k)} × ${Math.round(sh * k)} px`;
    },

    setZoom(z) {
        this.zoom = Math.min(4, Math.max(1, z));
        this.clamp();
    },

    pointerDown(e) {
        this.drag = { x: e.clientX, y: e.clientY, cx: this.cx, cy: this.cy };
        e.currentTarget.setPointerCapture(e.pointerId);
    },
    pointerMove(e) {
        if (!this.drag) return;
        this.cx = this.drag.cx + (e.clientX - this.drag.x);
        this.cy = this.drag.cy + (e.clientY - this.drag.y);
        this.clamp();
    },
    pointerUp() { this.drag = null; },
    wheel(e) { this.setZoom(this.zoom * (e.deltaY < 0 ? 1.08 : 1 / 1.08)); },

    /* Apply the crop to the current photo and move on. */
    apply() {
        const file = this.queue[this.idx];
        const { sx, sy, sw, sh } = this.region;
        const full = this.img.naturalWidth;
        // Whole photo, untouched framing: keep the original file (no re-encode, keeps PNG/WebP).
        if (this.ratioKey === 'orig' && Math.abs(sw - full) < 1.5 && Math.abs(sh - this.img.naturalHeight) < 1.5) {
            this.results.push(file);
            return this.next();
        }
        const k = Math.min(1, MAX_SIDE / Math.max(sw, sh));
        const canvas = document.createElement('canvas');
        canvas.width = Math.max(1, Math.round(sw * k));
        canvas.height = Math.max(1, Math.round(sh * k));
        canvas.getContext('2d').drawImage(this.img, sx, sy, sw, sh, 0, 0, canvas.width, canvas.height);
        canvas.toBlob((blob) => {
            if (!blob) { this.results.push(file); return this.next(); }
            const base = file.name.replace(/\.[^.]+$/, '') || 'foto';
            this.results.push(new File([blob], `${base}.jpg`, { type: 'image/jpeg', lastModified: Date.now() }));
            this.next();
        }, 'image/jpeg', 0.92);
    },

    keepOriginal() {
        this.results.push(this.queue[this.idx]);
        this.next();
    },

    cancel() { this.done(null); },
}));
