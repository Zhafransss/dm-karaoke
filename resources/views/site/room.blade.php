@extends('layouts.public', [
    'nav' => 'harga',
    'title' => $t->name.' – Ruang Karaoke Rawalo | DM Karaoke & Cafe',
    'description' => \App\Support\Seo::roomDescription($t),
    'breadcrumbs' => [[$t->name, route('ruangan.show', $t->slug)]],
    'schema' => \App\Support\Seo::roomSchema($t),
    'ogImage' => $t->image ? upload_url($t->image) : null,
])

@php
    use App\Support\OpHours;
    $items = $t->galleryItems();
    $ps = $t->activePackages;
    $bookUrl = route('reservasi', ['tipe' => $t->slug]);
@endphp

@section('content')
<x-page-head :crumb="$t->name" eyebrow="Detail ruangan" :title="e($t->name)" :lead="$t->description" />

<section class="band"><div class="wrap">
    <div class="grid gap-8 lg:grid-cols-[7fr_5fr] items-start">
        {{-- Photos and videos: main viewer plus thumbnails --}}
        <div x-data="mediaViewer(@js($items->values()))" @keydown.arrow-left="step(-1)" @keydown.arrow-right="step(1)" class="flex flex-col gap-3">
            @if($items->isEmpty())
                <div class="media rounded-md border border-line"><x-img :path="null" /></div>
            @else
                <div class="relative aspect-[4/3] overflow-hidden rounded-md border border-gold-deep bg-black">
                    <template x-if="cur.type === 'video'"><video :src="cur.src" controls playsinline preload="metadata" class="absolute inset-0 w-full h-full object-contain"></video></template>
                    <template x-if="cur.type !== 'video'"><img :src="cur.src" alt="Suasana {{ $t->name }} DM Karaoke" class="absolute inset-0 w-full h-full object-cover"></template>
                    <template x-if="items.length > 1">
                        <div>
                            <button class="lb-btn left-3 top-1/2 -translate-y-1/2" type="button" @click="step(-1)" aria-label="Sebelumnya"><x-icon name="left" /></button>
                            <button class="lb-btn right-3 top-1/2 -translate-y-1/2" type="button" @click="step(1)" aria-label="Berikutnya"><x-icon name="right" /></button>
                        </div>
                    </template>
                </div>
                <template x-if="items.length > 1">
                    <div class="grid grid-cols-[repeat(auto-fill,minmax(88px,1fr))] gap-2">
                        <template x-for="(it, i) in items" :key="i">
                            <button type="button" @click="go(i)" class="relative aspect-[4/3] overflow-hidden rounded-sm border bg-surface-overlay p-0 cursor-pointer transition"
                                    :class="i === idx ? 'border-gold' : 'border-line opacity-70 hover:opacity-100'" :aria-label="(it.type === 'video' ? 'Video ' : 'Foto ') + (i + 1)" :aria-current="i === idx ? 'true' : null">
                                <img x-show="it.type !== 'video'" :src="it.thumb || it.src" alt="" loading="lazy" class="absolute inset-0 w-full h-full object-cover">
                                <template x-if="it.type === 'video'"><span><video :src="it.src + '#t=0.1'" preload="metadata" muted playsinline tabindex="-1" aria-hidden="true" class="absolute inset-0 w-full h-full object-cover"></video><span class="play-badge !w-8 !h-8"><x-icon name="play" /></span></span></template>
                            </button>
                        </template>
                    </div>
                </template>
            @endif
        </div>

        {{-- Summary --}}
        <aside class="info-card gap-5">
            <div class="flex flex-wrap gap-2">
                @if($promo)<span class="badge badge-gold">Promo</span>@endif
                @if($t->publicCap())<span class="badge">Maks. {{ $t->publicCap() }} orang</span>@endif
            </div>
            @if(array_filter($t->facilities ?? []))
                <ul class="ticks">@foreach(array_filter($t->facilities) as $f)<li>{{ $f }}</li>@endforeach</ul>
            @endif
            <div class="pt-4 border-t border-line">
                @if($t->rentable)
                    <span class="price-from">Sewa per jam</span><span class="price-val">{{ rp($t->rate_night) }}</span>
                    <span class="price-sub">Siang {{ hl(OpHours::open()) }}–{{ hl(OpHours::dayEnd()) }} mulai {{ rp($t->rate_day) }}@if($t->minPackagePrice()) · Paket mulai {{ rp($t->minPackagePrice()) }}@endif</span>
                @else
                    <span class="price-from">Paket mulai dari</span><span class="price-val">{{ rp($t->minPackagePrice()) }}</span>
                @endif
                @if($promo)<span class="block mt-1.5 text-xs leading-[18px] text-gold">Promo: {{ $promo->name }}</span>@endif
            </div>
            @if($t->rentable || $t->prepaid)<p class="m-0 text-sm leading-[22px] text-ink-muted">{{ $t->depositText() }}</p>@endif
            <a class="btn btn-primary" href="{{ $bookUrl }}">Pilih jadwal</a>
        </aside>
    </div>
</div></section>

@if($ps->isNotEmpty())
<section class="band band-alt" aria-labelledby="rm-pkg"><div class="wrap">
    <x-heading eyebrow="Paket" title="Paket untuk <em>{{ e($t->name) }}</em>" id="rm-pkg" />
    @foreach($ps->groupBy('brand') as $brand => $list)
        <div class="{{ $loop->first ? '' : 'mt-12' }}">
            <h3 class="m-0 mb-4 font-display text-[28px] leading-8 font-semibold text-gold">{{ $brand }}</h3>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($list as $p)<x-pkg-card :pkg="$p" :type-slug="$t->slug" :promo="$promoSvc->firstFor([$p->pkgKey()], $today)" />@endforeach
            </div>
        </div>
    @endforeach
</div></section>
@endif
@endsection
