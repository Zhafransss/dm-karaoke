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
     * $forced prices one given promo (admin preview). $chosen is the promo the guest picked;
     * it is used only while it still discounts this booking.
     *
     * @return array{mode:string, package_id:?int, package_name:?string, lc:bool, hours:int,
     *               lines:array, base:int, promo_id:?int, promo_name:?string, promo_note:string,
     *               eligible_hours:int, discount:int, total:int,
     *               promo_options:array<int, array{id:int, name:string, discount:int, note:string}>}
     */
    public function calculate(RoomType $type, ?Package $pkg, string $date, int $hour, int $hours, bool $lc, ?Promo $forced = null, ?int $chosen = null): array
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

        // One promo per transaction: every promo that discounts this booking is an option;
        // the guest's choice wins, otherwise the biggest discount (ties keep the candidates' order).
        $candidates = $forced ? collect([$forced]) : $this->promos->candidates($pkg ? $pkg->pkgKey() : $type->rentKey(), $date, $hour, $hours);
        $options = $candidates
            ->map(fn (Promo $p) => ['promo' => $p, ...array_combine(['discount', 'eligible', 'note'], $this->discount($p, $type, $pkg, $perHour, $hour, $hours))])
            ->filter(fn ($o) => $o['discount'] > 0)
            ->sortByDesc('discount')
            ->values();
        $pick = $options->first(fn ($o) => $o['promo']->id === $chosen) ?? $options->first();
        [$promo, $discount, $eligible, $note] = $pick ? [$pick['promo'], $pick['discount'], $pick['eligible'], $pick['note']] : [null, 0, 0, ''];
        $used = $promo !== null;

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
            'promo_options' => $options->map(fn ($o) => ['id' => $o['promo']->id, 'name' => $o['promo']->name, 'discount' => $o['discount'], 'note' => $o['note']])->all(),
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
    private function discount(Promo $p, RoomType $type, ?Package $pkg, array $perHour, int $hour, int $hours): array
    {
        if ($pkg) {
            if (! $p->appliesToPackage($pkg, $hour)) {
                return [0, 0, ''];
            }
            if ($p->kind === 'bayar_x') {
                // Free package hours are valued at the room's hourly rate without LC; drinks and LC keep their price.
                $roomHours = [];
                for ($i = 0; $i < $pkg->hours; $i++) {
                    if ($p->inWindow($hour + $i)) {
                        $roomHours[] = ['h' => $hour + $i, ...$this->hourRate($type, $hour + $i, false)];
                    }
                }
                [$d, $n, $note] = $this->payX($p, $roomHours);

                return [min($d, $pkg->price), $n, $note];
            }
            $d = match ($p->kind) {
                'persen' => (int) round($pkg->price * $p->value / 100),
                'nominal' => min($p->value, $pkg->price),
                default => 0,
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
