@props(['eyebrow', 'title', 'lead' => null, 'center' => true, 'tag' => 'h2', 'id' => null])
<header class="heading {{ $center ? 'center' : '' }}">
    <p class="eyebrow">{{ $eyebrow }}</p>
    <{{ $tag }} @if($id) id="{{ $id }}" @endif>{!! $title !!}</{{ $tag }}>
    @if($center)<div class="ornament" role="separator"><span></span></div>@endif
    @if($lead)<p>{{ $lead }}</p>@endif
</header>
