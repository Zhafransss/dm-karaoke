<?php

namespace App\Services;

use App\Models\Promo;
use App\Models\RoomType;
use Illuminate\Support\Collection;

/** Promo lookup and overlap information (SRS §4.6). */
class PromoService
{
    private ?Collection $active = null;

    /** All active promos with their targets, loaded once per request. */
    public function active(): Collection
    {
        return $this->active ??= Promo::with('targetRows')->where('active', true)->get();
    }

    public function flush(): void
    {
        $this->active = null;
    }

    /** Active promos for a target key whose date range covers $date. */
    public function forTarget(string $key, string $date): Collection
    {
        return $this->active()->filter(fn (Promo $p) => in_array($key, $p->targets(), true)
            && $p->ds() <= $date && $date <= $p->de())->values();
    }

    /** First running promo among several keys (used for "Promo" badges). */
    public function firstFor(array $keys, string $date): ?Promo
    {
        foreach ($keys as $k) {
            if ($p = $this->forTarget($k, $date)->first()) {
                return $p;
            }
        }

        return null;
    }

    /**
     * Promos that may discount a booking: they must touch at least one booked hour.
     * Ordered for ties between equal discounts: window containing the start hour first, then the earliest window.
     */
    public function candidates(string $key, string $date, int $hour, int $hours): Collection
    {
        return $this->forTarget($key, $date)
            ->filter(function (Promo $p) use ($hour, $hours) {
                for ($i = 0; $i < $hours; $i++) {
                    if ($p->inWindow($hour + $i)) {
                        return true;
                    }
                }

                return false;
            })
            ->sort(function (Promo $a, Promo $b) use ($hour) {
                $byStart = (int) $b->inWindow($hour) <=> (int) $a->inWindow($hour);

                return $byStart ?: (($a->time_start ?? 0) <=> ($b->time_start ?? 0)) ?: ($a->id <=> $b->id);
            })
            ->values();
    }

    /**
     * Other active promos that share a target and overlap in dates and hours (FR-ADM-13).
     * Overlaps are allowed (tiered promos); the admin form only shows them as information.
     * $promo may be unsaved; its targets come from pendingTargets.
     */
    public function overlaps(Promo $promo): Collection
    {
        if (! $promo->active) {
            return collect();
        }
        $targets = $promo->targets();

        return Promo::with('targetRows')->where('active', true)
            ->when($promo->exists, fn ($q) => $q->where('id', '!=', $promo->id))
            ->whereDate('date_start', '<=', $promo->de())
            ->whereDate('date_end', '>=', $promo->ds())
            ->get()
            ->filter(fn (Promo $q) => array_intersect($targets, $q->targets()) && $this->windowsOverlap($promo, $q))
            ->values();
    }

    private ?array $labels = null;

    /** @return array<string,string> target key => human label ("Sewa VIP Room", "Vibe") */
    public function targetLabels(): array
    {
        if ($this->labels !== null) {
            return $this->labels;
        }
        $labels = [];
        foreach (RoomType::with('packages')->orderBy('sort')->get() as $t) {
            $labels[$t->rentKey()] = 'Sewa '.$t->name;
            foreach ($t->packages as $pk) {
                $labels[$pk->pkgKey()] = $pk->name;
            }
        }

        return $this->labels = $labels;
    }

    private function windowsOverlap(Promo $a, Promo $b): bool
    {
        [$a0, $a1] = $a->hasWindow() ? [$a->time_start, $a->time_end] : [0, 99];
        [$b0, $b1] = $b->hasWindow() ? [$b->time_start, $b->time_end] : [0, 99];

        return $a0 < $b1 && $b0 < $a1;
    }
}
