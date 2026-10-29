{{-- Flash message and confirmation dialog shared by every layout. --}}
@if(session('ok'))
    <div class="flash" role="status" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)" x-transition>
        <x-icon name="check" class="text-gold" /><span>{{ session('ok') }}</span>
    </div>
@endif

<div class="modal-back" x-data x-show="$store.confirm.open" x-cloak @keydown.escape.window="$store.confirm.open = false" @click.self="$store.confirm.open = false">
    <div class="modal" role="alertdialog" aria-modal="true" aria-labelledby="confirm-title">
        <h2 id="confirm-title" x-text="$store.confirm.title"></h2>
        <p class="m-0 muted" x-show="$store.confirm.text" x-text="$store.confirm.text"></p>
        <div class="flex flex-wrap justify-end gap-3">
            <button class="btn btn-quiet btn-sm" type="button" @click="$store.confirm.open = false">Batal</button>
            <button class="btn btn-danger btn-sm" type="button" x-text="$store.confirm.label" onclick="confirmYes()"></button>
        </div>
        <button class="icon-btn absolute top-4 right-4" type="button" @click="$store.confirm.open = false" aria-label="Tutup"><x-icon name="close" /></button>
    </div>
</div>
