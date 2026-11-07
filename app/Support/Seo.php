<?php

namespace App\Support;

use App\Models\Event;
use App\Models\RoomType;
use Illuminate\Support\Str;

/** Page descriptions and schema.org data that are built from content (rooms, events). */
class Seo
{
    /** One line for <meta name="description">: single spaces, cut on a word at ~160 characters. */
    public static function clip(string $text, int $max = 158): string
    {
        return Str::limit(trim(preg_replace('/\s+/', ' ', $text)), $max, '…', preserveWords: true);
    }

    public static function roomDescription(RoomType $t): string
    {
        $parts = [$t->name.' di DM Karaoke & Cafe, Rawalo, Banyumas.'];
        if ($t->description) {
            $parts[] = rtrim(trim($t->description), '.').'.';
        }
        if ($t->publicCap()) {
            $parts[] = "Kapasitas hingga {$t->publicCap()} orang.";
        }
        $from = $t->rentable ? rp($t->rate_day ?: $t->rate_night).' per jam' : ($t->minPackagePrice() ? rp($t->minPackagePrice()).' per paket' : '');
        if ($from) {
            $parts[] = ($t->rentable ? 'Sewa mulai ' : 'Paket mulai ').$from.'.';
        }
        $parts[] = 'Reservasi online.';

        // Keep whole sentences: stop adding once the next one would not fit in the snippet.
        $text = array_shift($parts);
        foreach ($parts as $part) {
            if (mb_strlen($text.' '.$part) > 158) {
                break;
            }
            $text .= ' '.$part;
        }

        return self::clip($text);
    }

    /** Event body first, then when and where; whole sentences only, so even a one-line event gets a useful snippet. */
    public static function eventDescription(Event $e): string
    {
        $when = tgl($e->date).($e->start_time ? ' pukul '.$e->start_time.($e->end_time ? '–'.$e->end_time : '') : '');
        $parts = array_filter([trim($e->excerpt(110)), "{$e->title}: {$when} di DM Karaoke & Cafe, Rawalo, Banyumas."]);
        $text = array_shift($parts);
        foreach ($parts as $part) {
            if (mb_strlen($text.' '.$part) > 158) {
                break;
            }
            $text .= ' '.$part;
        }

        return self::clip($text);
    }

    /** Event rich result: needs a start date; times are "19.00" and an end before the start means past midnight. */
    public static function eventSchema(Event $e): array
    {
        $biz = config('dmk.business');
        $time = fn (?string $t) => preg_match('/^(\d{1,2})[.:](\d{2})/', (string) $t, $m) ? sprintf('%02d:%s:00', $m[1], $m[2]) : '00:00:00';
        $day = $e->date->copy();
        $start = $day->toDateString().'T'.$time($e->start_time).'+07:00';
        $end = null;
        if ($e->end_time) {
            $endDay = $time($e->end_time) <= $time($e->start_time) ? $day->copy()->addDay() : $day;
            $end = $endDay->toDateString().'T'.$time($e->end_time).'+07:00';
        }

        return array_filter([
            '@context' => 'https://schema.org', '@type' => 'Event', 'name' => $e->title, 'url' => route('event.show', $e->slug),
            'startDate' => $start, 'endDate' => $end,
            'eventStatus' => 'https://schema.org/EventScheduled', 'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
            'location' => [
                '@type' => 'Place', 'name' => $biz['name'],
                'address' => ['@type' => 'PostalAddress', 'streetAddress' => $biz['street'], 'addressLocality' => $biz['locality'], 'addressRegion' => $biz['region'], 'postalCode' => $biz['postal_code'], 'addressCountry' => 'ID'],
                'geo' => ['@type' => 'GeoCoordinates', 'latitude' => $biz['lat'], 'longitude' => $biz['lng']],
            ],
            'image' => $e->image ? [upload_url($e->image)] : null,
            'description' => self::clip($e->excerpt(300) ?: $e->title, 300),
            'organizer' => ['@type' => 'Organization', 'name' => $biz['name'], 'url' => route('home')],
        ]);
    }

    /** A room as a service the business offers, priced from the lowest public price. */
    public static function roomSchema(RoomType $t): array
    {
        $price = $t->rentable ? ($t->rate_day ?: $t->rate_night) : $t->minPackagePrice();

        return array_filter([
            '@context' => 'https://schema.org', '@type' => 'Service', 'name' => $t->name, 'serviceType' => 'Sewa ruang karaoke',
            'description' => self::roomDescription($t), 'url' => route('ruangan.show', $t->slug),
            'image' => $t->galleryItems()->where('type', 'image')->pluck('src')->take(5)->all() ?: null,
            'provider' => ['@id' => route('home').'#business'],
            'areaServed' => ['@type' => 'AdministrativeArea', 'name' => 'Kabupaten Banyumas'],
            'offers' => $price ? ['@type' => 'Offer', 'priceCurrency' => 'IDR', 'price' => (int) $price, 'availability' => 'https://schema.org/InStock', 'url' => route('reservasi', ['tipe' => $t->slug])] : null,
        ]);
    }
}
