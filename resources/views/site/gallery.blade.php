@extends('layouts.public', ['nav' => 'galeri', 'title' => 'Galeri | DM Karaoke & Cafe Rawalo', 'description' => 'Foto ruangan karaoke, suasana, event, dan cafe DM Karaoke & Cafe di Rawalo, Banyumas.'])

@php($lb = $items->map(fn ($g) => ['src' => upload_url($g->image), 'caption' => $g->caption, 'cat' => $g->category])->values())

@section('content')
<x-page-head crumb="Galeri" eyebrow="Galeri foto" title="Suasana <em>DM Karaoke</em> &amp; Cafe" lead="Lihat ruangan, suasana, acara, dan cafe sebelum kamu datang." />

{{-- FR-PUB-05 --}}
<section class="band" x-data="lightbox(@js($lb))" @keydown.escape.window="close()" @keydown.arrow-left.window="cur && step(-1)" @keydown.arrow-right.window="cur && step(1)"><div class="wrap">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-8">
        <nav class="chips flex flex-wrap gap-2" aria-label="Filter kategori">
            <a class="chip" href="{{ route('galeri') }}" @if(! $cat) aria-current="page" @endif>Semua</a>
            @foreach($cats as $c)<a class="chip" href="{{ route('galeri', ['kategori' => $c]) }}" @if($cat === $c) aria-current="page" @endif>{{ $c }}</a>@endforeach
        </nav>
        <span class="caption">{{ $items->count() }} foto</span>
    </div>
    @if($items->isNotEmpty())
        <div class="[column-width:280px] [column-gap:16px] columns-3">
            @foreach($items as $i => $g)
                <button class="gal-item" type="button" @click="open({{ $i }})" aria-label="Perbesar foto {{ $g->caption }}">
                    <x-img :path="$g->image" :alt="$g->caption.' di DM Karaoke & Cafe'" sizes="(max-width: 640px) 100vw, 380px" />
                    <span class="gal-cap"><span>{{ $g->category }}</span><b>{{ $g->caption }}</b></span>
                </button>
            @endforeach
        </div>
    @else
        <div class="empty"><x-icon name="image" class="!w-8 !h-8 text-gold mx-auto mb-3" /><p class="m-0">Belum ada foto di kategori ini.</p></div>
    @endif

    <div class="lightbox" x-show="cur" x-cloak role="dialog" aria-modal="true" :aria-label="cur && 'Foto ' + cur.caption" @click.self="close()">
        <button class="lb-btn top-4 right-4" type="button" x-ref="close" @click="close()" aria-label="Tutup"><x-icon name="close" /></button>
        <template x-if="items.length > 1">
            <div>
                <button class="lb-btn left-4 top-1/2 -translate-y-1/2" type="button" @click="step(-1)" aria-label="Foto sebelumnya"><x-icon name="left" /></button>
                <button class="lb-btn right-4 top-1/2 -translate-y-1/2" type="button" @click="step(1)" aria-label="Foto berikutnya"><x-icon name="right" /></button>
            </div>
        </template>
        <figure class="m-0 flex flex-col items-center gap-4 min-h-0 max-h-full flex-1 justify-center">
            <img :src="cur?.src" :alt="cur?.caption" class="max-h-[calc(100vh-180px)] max-w-[min(1100px,100%)] object-contain border border-gold-deep rounded-md">
            <figcaption class="text-center"><span class="eyebrow" x-text="cur && `${cur.cat} · ${idx + 1}/${items.length}`"></span><b class="block font-display text-[26px] leading-[30px] font-semibold" x-text="cur?.caption"></b></figcaption>
        </figure>
    </div>
</div></section>
@endsection
