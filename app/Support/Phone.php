<?php

namespace App\Support;

/** Indonesian phone number helpers (FR-RES-08). */
class Phone
{
    /**
     * Strip separators. Numbers pasted from contacts or WhatsApp often carry invisible characters
     * (no-break spaces, left-to-right marks, non-breaking or en hyphens), so those go too.
     */
    public static function normalize(?string $value): string
    {
        $value = (string) $value;

        return preg_replace('/[\p{Z}\p{Cc}\p{Cf}.()\-\x{2010}-\x{2015}\x{2212}]/u', '', $value)
            ?? preg_replace('/[\s.()\-]/', '', $value); // not valid UTF-8: plain ASCII cleanup
    }

    public static function valid(?string $value): bool
    {
        return (bool) preg_match('/^(\+?62|0)8\d{7,12}$/', self::normalize($value));
    }

    /**
     * Split a list of numbers separated by commas, semicolons, slashes or new lines. Numbers separated
     * only by spaces are split too, but only when every part is a valid number on its own, so
     * "0822 2041 3723" stays one number.
     *
     * @return list<string>
     */
    public static function list(?string $value): array
    {
        $out = [];
        foreach (preg_split('/[,;\/\n\r]+/', (string) $value) as $chunk) {
            $chunk = trim($chunk);
            if ($chunk === '') {
                continue;
            }
            if (! self::valid($chunk)) {
                $parts = preg_split('/[\p{Z}\s]+/u', $chunk, -1, PREG_SPLIT_NO_EMPTY) ?: [];
                if (count($parts) > 1 && collect($parts)->every(fn ($p) => self::valid($p))) {
                    array_push($out, ...$parts);

                    continue;
                }
            }
            $out[] = $chunk;
        }

        return $out;
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
