{{-- Crop dialog for <input type="file" data-crop data-crop-ratio="4:3"> (see resources/js/cropper.js). --}}
<div class="modal-back" x-data="imageCropper" x-show="open" x-cloak @dmk-crop.window="start($event.detail)" @keydown.escape.window="open && cancel()">
    <div class="modal !max-w-[640px]" role="dialog" aria-modal="true" aria-labelledby="crop-title">
        <div>
            <h2 id="crop-title">Atur foto</h2>
            <p class="mt-1 mb-0 text-sm leading-[22px] text-ink-subtle">Foto <span x-text="idx + 1"></span> dari <span x-text="queue.length"></span>. Geser foto dan atur zoom, bagian di dalam bingkai yang akan tampil di website.</p>
        </div>

        <div class="flex flex-wrap gap-2" role="group" aria-label="Rasio">
            <template x-for="r in ratios" :key="r.key">
                <button type="button" class="chip" :aria-current="ratioKey === r.key ? 'page' : null" @click="setRatio(r.key)" x-text="r.label"></button>
            </template>
        </div>

        <div class="relative mx-auto overflow-hidden bg-black touch-none select-none rounded-sm border border-line cursor-grab active:cursor-grabbing"
             :style="`width:${stage.w}px;height:${stage.h}px`"
             @pointerdown="pointerDown($event)" @pointermove="pointerMove($event)" @pointerup="pointerUp()" @pointercancel="pointerUp()" @wheel.prevent="wheel($event)">
            <img :src="url" alt="Pratinjau foto" draggable="false" class="absolute max-w-none pointer-events-none" :style="imgStyle">
            {{-- The frame; its huge shadow darkens everything outside the crop area --}}
            <div class="absolute border border-gold pointer-events-none" :style="frameStyle + ';box-shadow:0 0 0 9999px rgba(0,0,0,.62)'">
                <span class="absolute inset-y-0 left-1/3 border-l border-white/25"></span><span class="absolute inset-y-0 left-2/3 border-l border-white/25"></span>
                <span class="absolute inset-x-0 top-1/3 border-t border-white/25"></span><span class="absolute inset-x-0 top-2/3 border-t border-white/25"></span>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <label class="label m-0" for="crop-zoom">Zoom</label>
            <input id="crop-zoom" class="flex-1 accent-[#c9a45c]" type="range" min="1" max="4" step="0.01" :value="zoom" @input="setZoom(+$event.target.value)">
            <span class="caption whitespace-nowrap" x-text="outSize"></span>
        </div>

        <div class="flex flex-wrap justify-between gap-3">
            <button class="btn btn-quiet btn-sm" type="button" @click="cancel()">Batal</button>
            <div class="flex flex-wrap gap-3">
                <button class="btn btn-quiet btn-sm" type="button" @click="keepOriginal()">Pakai asli</button>
                <button class="btn btn-primary btn-sm" type="button" @click="apply()" x-text="idx + 1 < queue.length ? 'Pakai & lanjut' : 'Pakai crop ini'">Pakai crop ini</button>
            </div>
        </div>
        <button class="icon-btn absolute top-4 right-4" type="button" @click="cancel()" aria-label="Tutup"><x-icon name="close" /></button>
    </div>
</div>
