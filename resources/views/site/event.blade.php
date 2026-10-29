@extends('layouts.public', [
    'nav' => 'event',
    'title' => $event->title.' | DM Karaoke & Cafe',
    'description' => $event->excerpt(150),
    'ogImage' => $event->image ? upload_url($event->image) : null,
])

@section('content')
<section class="band"><div class="wrap">
    <nav class="crumbs !justify-start mb-8" aria-label="Breadcrumb"><a href="{{ route('home') }}">Beranda</a><span aria-hidden="true">/</span><a href="{{ route('event') }}">Event</a><span aria-hidden="true">/</span><span>{{ $event->title }}</span></nav>
    <div class="grid gap-8 lg:gap-16 lg:grid-cols-[5fr_7fr] items-start">
        <div class="max-w-[520px] p-1.5 border border-gold-deep rounded-md bg-bg">
            <div class="relative aspect-[4/5] overflow-hidden rounded-sm"><x-img :path="$event->image" :alt="'Poster '.$event->title" class="absolute inset-0 w-full h-full object-cover" :eager="true" /></div>
        </div>
        <div>
            <span class="badge {{ $upcoming ? 'badge-gold' : '' }}">{{ $upcoming ? 'Mendatang' : 'Sudah berlangsung' }}</span>
            <h1 class="mt-3 mb-6 font-display text-[40px] md:text-[56px] leading-[1.02] font-medium" style="text-wrap: balance">{{ $event->title }}</h1>
            <ul class="flex flex-wrap gap-x-8 gap-y-3 m-0 mb-6 py-4 border-y border-line list-none text-[15px]">
                <li class="flex items-center gap-2"><x-icon name="calendar" class="text-gold" />{{ tgl($event->date) }}</li>
                <li class="flex items-center gap-2"><x-icon name="clock" class="text-gold" />{{ $event->start_time }}–{{ $event->end_time }}</li>
                <li class="flex items-center gap-2"><x-icon name="pin" class="text-gold" />DM Karaoke &amp; Cafe, Rawalo</li>
            </ul>
            <div class="text-[17px] leading-7 text-ink-muted max-w-[62ch] whitespace-pre-line">{{ $event->body }}</div>
            <div class="flex flex-wrap gap-3 mt-6">
                @if($upcoming)<a class="btn btn-primary" href="{{ route('reservasi', ['tanggal' => $event->date->toDateString()]) }}">Reservasi untuk tanggal ini</a>@endif
                <button class="btn btn-quiet" type="button" onclick="copyText(@js(route('event.show', $event->slug)), this)"><x-icon name="copy" class="ic-sm" />Salin link</button>
                <a class="btn btn-ghost" href="{{ route('event') }}">Semua event</a>
            </div>
        </div>
    </div>
</div></section>
@endsection
