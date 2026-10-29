<?php

use App\Support\OpHours;
use Carbon\Carbon;

if (! function_exists('rp')) {
    function rp(int|float|null $amount): string
    {
        return 'Rp '.number_format((int) round($amount ?? 0), 0, ',', '.');
    }
}

if (! function_exists('rpk')) {
    /** 120000 -> "120K" (compact price for tight grids) */
    function rpk(int $amount): string
    {
        return ($amount >= 1000 && $amount % 1000 === 0) ? ($amount / 1000).'K' : rp($amount);
    }
}

if (! function_exists('hl')) {
    function hl(int $hour): string
    {
        return OpHours::label($hour);
    }
}

if (! function_exists('tgl')) {
    /**
     * Indonesian date formatting.
     * Styles: long "Kamis, 29 Oktober 2026", mid "Kam, 29 Okt", short "29 Okt", full "29 Okt 2026".
     */
    function tgl($date, string $style = 'long'): string
    {
        $d = $date instanceof DateTimeInterface ? Carbon::instance($date) : Carbon::parse($date);
        $d->locale('id');

        return match ($style) {
            'mid' => $d->translatedFormat('D, j M'),
            'short' => $d->translatedFormat('j M'),
            'full' => $d->translatedFormat('j M Y'),
            'day' => $d->translatedFormat('l, j F'),
            'datetime' => $d->translatedFormat('j M Y, H.i'),
            default => $d->translatedFormat('l, j F Y'),
        };
    }
}

if (! function_exists('upload_url')) {
    /** Public URL for an uploaded image path, optionally its small variant. */
    function upload_url(?string $path, string $size = 'lg'): ?string
    {
        if (! $path) {
            return null;
        }
        if ($size === 'sm') {
            $path = preg_replace('/\.webp$/', '-sm.webp', $path);
        }

        return asset('uploads/'.$path);
    }
}
