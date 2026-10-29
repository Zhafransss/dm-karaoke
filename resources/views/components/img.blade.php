{{-- Responsive WebP with a small variant (NFR-01). Falls back to a branded placeholder. --}}
@props(['path' => null, 'alt' => '', 'sizes' => '(max-width: 640px) 100vw, 400px', 'eager' => false])
@if($path)
    <img src="{{ upload_url($path) }}" srcset="{{ upload_url($path, 'sm') }} 640w, {{ upload_url($path) }} 1600w" sizes="{{ $sizes }}"
         alt="{{ $alt }}" @unless($eager) loading="lazy" @endunless decoding="async" {{ $attributes }}>
@else
    <span class="placeholder-img"><img src="{{ asset('images/logo-mark.webp') }}" alt="" width="80" height="87"></span>
@endif
