@props(['event', 'past' => false])
@php($d = $event->date->locale('id'))
<a class="ev {{ $past ? 'past' : '' }}" href="{{ route('event.show', $event->slug) }}">
    <div class="media">
        <x-img :path="$event->image" :alt="$event->title" />
        <div class="ev-date"><b>{{ $d->day }}</b><span>{{ $d->translatedFormat('M') }}</span></div>
    </div>
    <div class="flex flex-col flex-1 gap-2 p-6">
        <span class="text-xs leading-[18px] tracking-[.14em] uppercase text-gold">{{ $d->translatedFormat('l') }} · {{ $event->start_time }}–{{ $event->end_time }}</span>
        <h3 class="m-0 font-display text-[26px] leading-[30px] font-semibold">{{ $event->title }}</h3>
        <p class="m-0 text-sm leading-[22px] text-ink-muted">{{ $event->excerpt() }}</p>
    </div>
</a>
