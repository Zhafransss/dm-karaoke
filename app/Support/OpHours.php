<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Operational day helpers (SRS §4.1).
 *
 * The venue opens 14.00 and closes 04.00 the next morning. An operational day
 * is addressed by its calendar date plus "operational hours" 14..27, where
 * 24..27 mean 00.00..03.00 of the following calendar day. Because one
 * operational day never spans more than 24 hours, two bookings can only
 * overlap when they share the same operational date.
 */
class OpHours
{
    public static function open(): int
    {
        return (int) Settings::get('open');
    }

    public static function close(): int
    {
        $open = self::open();
        $close = (int) Settings::get('close');

        return $close <= $open ? $close + 24 : $close;
    }

    /** First operational hour that uses the night rate. */
    public static function dayEnd(): int
    {
        return (int) Settings::get('day_end');
    }

    /** @return int[] every bookable starting hour of an operational day */
    public static function hours(): array
    {
        return range(self::open(), self::close() - 1);
    }

    /** Operational date for "now": before closing time after midnight it is still yesterday. */
    public static function today(?CarbonInterface $now = null): string
    {
        $now = CarbonImmutable::instance($now ?? now());
        $close = self::close();
        if ($close > 24 && $now->hour < $close - 24) {
            return $now->subDay()->toDateString();
        }

        return $now->toDateString();
    }

    /** Wall-clock moment of an operational hour. */
    public static function at(string $date, int $hour): CarbonImmutable
    {
        return CarbonImmutable::parse($date)->startOfDay()->addHours($hour);
    }

    public static function isPast(string $date, int $hour): bool
    {
        return self::at($date, $hour)->lessThanOrEqualTo(now());
    }

    /** 26 -> "02.00" */
    public static function label(int $hour): string
    {
        return str_pad((string) ((($hour % 24) + 24) % 24), 2, '0', STR_PAD_LEFT).'.00';
    }

    public static function range(int $start, int $hours): string
    {
        return self::label($start).'–'.self::label($start + $hours);
    }

    public static function isEarlyMorning(int $hour): bool
    {
        return $hour >= 24;
    }
}
