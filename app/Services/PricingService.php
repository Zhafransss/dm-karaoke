<?php

namespace App\Services;

use App\Models\Package;
use App\Models\Promo;
use App\Models\RoomType;
use App\Support\OpHours;
use InvalidArgumentException;

/**
 * Single source of truth for prices (SRS §4.3–4.6, §6.3). Used by the public
 * price endpoint, reservation storage and the admin promo preview, so the
 * number a guest sees is the number that gets saved (T-28).
 */
class PricingService
{
    public function __construct(private PromoService $promos) {}

    /**
     * @return array{mode:string, package_id:?int, package_name:?string, lc:bool, hours:int,
     *               lines:array, base:int, promo_id:?int, promo_name:?string, promo_note:string,
     *               eligible_hours:int, discount:int, total:int}
     */
    public function calculate(RoomType $type, ?Package $pkg, string $date, int $hour, int $hours, bool $lc, ?Promo $forced = null): array
    {
        if (! $pkg && ! $type->rentable) {
            throw new InvalidArgumentException("{$type->name} hanya tersedia dalam bentuk paket.");
        }

        $lines = [];
        $perHour = [];
        if ($pkg) {
            $hours = $pkg->hours;
            $lc = false; // LC in a package is part of the package, never priced separately
            $base = $pkg->price;
            $lines[] = ['label' => $pkg->name, 'hours' => $pkg->hours, 'rate' => $pkg->price, 'sum' => $pkg->price, 'from' => $hour];
        } else {
            for ($i = 0; $i < $hours; $i++) {
                $perHour[] = ['h' => $hour + $i, ...$this->hourRate($type, $hour + $i, $lc)];
            }
            foreach ($perHour as $x) {
                $last = array_key_last($lines);
                if ($last !== null && $lines[$last]['label'] === $x['label'] && $lines[$last]['rate'] === $x['rate']) {
                    $lines[$last]['hours']++;
                    $lines[$last]['sum'] += $x['rate'];
                } else {
                    $lines[] = ['label' => $x['label'], 'hours' => 1, 'rate' => $x['rate'], 'sum' => $x['rate'], 'from' => $x['h']];
                }
            }
            $base = array_sum(array_column($perHour, 'rate'));
        }

        $promo = $forced ?? $this->promos->pick($pkg ? $pkg->pkgKey() : $type->rentKey(), $date, $hour, $hours);
        [$discount, $eligible, $note] = $promo ? $this->discount($promo, $pkg, $perHour, $hour, $hours) : [0, 0, ''];
        $used = $promo && $discount > 0;

        return [
            'mode' => $pkg ? 'paket' : 'sewa',
            'package_id' => $pkg?->id,
            'package_name' => $pkg?->name,
            'lc' => ! $pkg && $lc,
            'hours' => $hours,
            'lines' => $lines,
            'base' => $base,
            'promo_id' => $used ? $promo->id : null,
            'promo_name' => $used ? $promo->name : null,
            'promo_note' => $used ? $note : '',
            'eligible_hours' => $used ? $eligible : 0,
            'discount' => $used ? $discount : 0,
            'total' => max(0, $base - ($used ? $discount : 0)),
        ];
    }

    /** Daytime: free with LC + deposit, day rate without. Night: full rate either way. */
    public function hourRate(RoomType $type, int $h, bool $lc): array
    {
        if ($h < OpHours::dayEnd()) {
            return $lc ? ['rate' => 0, 'label' => 'Siang, dengan LC'] : ['rate' => $type->rate_day, 'label' => 'Siang, tanpa LC'];
        }

        return ['rate' => $type->rate_night, 'label' => 'Malam'];
    }

    /** @return array{0:int,1:int,2:string} discount, eligible hours, note */
    private function discount(Promo $p, ?Package $pkg, array $perHour, int $hour, int $hours): array
    {
        if ($pkg) {
            // Package promos apply when the package starts inside the promo window.
            if (! $p->inWindow($hour)) {
                return [0, 0, ''];
            }
            $d = match ($p->kind) {
                'persen' => (int) round($pkg->price * $p->value / 100),
                'nominal' => min($p->value, $pkg->price),
                default => 0, // bayar_x is rental-only
            };

            return [$d, $pkg->hours, ''];
        }

        // Rental: only hours inside the window are discounted.
        $eligible = array_values(array_filter($perHour, fn ($x) => $p->inWindow($x['h'])));
        $n = count($eligible);
        $sum = array_sum(array_column($eligible, 'rate'));
        $partial = $n < $hours ? "{$n} dari {$hours} jam" : '';

        return match ($p->kind) {
            'persen' => [(int) round($sum * $p->value / 100), $n, $partial],
            'nominal' => [min($p->value, $sum), $n, $partial],
            'bayar_x' => $this->payX($p, $eligible),
            default => [0, 0, ''],
        };
    }

    /**
     * "Rent X hours, pay Y": once per transaction, the (X−Y) cheapest *paid* hours
     * are free. Hours made free by LC do not count as rental hours (SRS §4.6.7).
     */
    private function payX(Promo $p, array $eligible): array
    {
        $paid = array_values(array_filter(array_column($eligible, 'rate'), fn ($r) => $r > 0));
        if (count($paid) < $p->x) {
            return [0, 0, ''];
        }
        sort($paid);

        return [array_sum(array_slice($paid, 0, max(0, $p->x - $p->y))), count($paid), "{$p->x} jam bayar {$p->y} jam"];
    }
}
