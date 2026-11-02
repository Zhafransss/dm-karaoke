{{-- Still frame of an uploaded video (first frame via preload=metadata) with a play badge. --}}
@props(['path', 'play' => true])
<video src="{{ upload_url($path) }}#t=0.1" preload="metadata" muted playsinline tabindex="-1" aria-hidden="true" {{ $attributes }}></video>
@if($play)<span class="play-badge"><x-icon name="play" /></span>@endif
