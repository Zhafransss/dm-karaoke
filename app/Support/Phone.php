<?php

namespace App\Support;

/** Indonesian phone number helpers (FR-RES-08). */
class Phone
{
    public static function normalize(?string $value): string
    {
        return preg_replace('/[\s.()\-]/', '', (string) $value);
    }

    public static function valid(?string $value): bool
    {
        return (bool) preg_match('/^(\+?62|0)8\d{7,12}$/', self::normalize($value));
    }

    /** Split a comma/semicolon/newline separated list into trimmed, non-empty numbers. */
    public static function list(?string $value): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/[,;\n]+/', (string) $value))));
    }

    /** "0812..." / "+62812..." -> "62812..." for wa.me and gateways. */
    public static function toWa(?string $value): string
    {
        $digits = preg_replace('/\D/', '', self::normalize($value));

        return str_starts_with($digits, '0') ? '62'.substr($digits, 1) : $digits;
    }

    public static function waLink(?string $text = null, ?string $number = null): string
    {
        $number = $number ? self::toWa($number) : Settings::get('wa_number');

        return 'https://wa.me/'.$number.($text ? '?text='.rawurlencode($text) : '');
    }
}
