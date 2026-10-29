@extends('layouts.public', ['nav' => 'event', 'title' => 'Event | DM Karaoke & Cafe', 'description' => 'Jadwal event dan acara seru di DM Karaoke & Cafe Rawalo, Banyumas.'])

@section('content')
<x-page-head crumb="Event" eyebrow="Event" title="Acara di <em>DM Karaoke</em>" lead="Malam akustik, perayaan, dan acara spesial bersama tamu kami." />

<section class="band"><div class="wrap">
    <h2 class="sub-h">Mendatang</h2>
    @if($upcoming->isNotEmpty())
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">@foreach($upcoming as $e)<x-event-card :event="$e" />@endforeach</div>
    @else
        <div class="empty"><x-icon name="calendar" class="!w-8 !h-8 text-gold mx-auto mb-3" /><p class="m-0">Belum ada event terjadwal. Pantau terus halaman ini ya.</p></div>
    @endif
</div></section>

@if($past->isNotEmpty())
<section class="band band-alt"><div class="wrap">
    <h2 class="sub-h">Sudah berlangsung</h2>
    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">@foreach($past as $e)<x-event-card :event="$e" :past="true" />@endforeach</div>
</div></section>
@endif
@endsection
