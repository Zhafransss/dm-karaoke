<?php

namespace App\Services;

use App\Models\Block;
use App\Models\Package;
use App\Models\Promo;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomType;
use App\Support\OpHours;
use App\Support\Settings;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Free/busy logic per unit (SRS §4.7). A unit is busy at hour h when an
 * occupying reservation (menunggu/dikonfirmasi) or a block covers h.
 * Bookings are whole hours with no gap between them.
 */
class AvailabilityService
{
    /** @var array<string, array<int, array<int, bool>>> cache of busy maps */
    private array $maps = [];

    public function flush(): void
    {
        $this->maps = [];
    }

    /** @return Collection<int, Room> active units of a type */
    public function units(int $typeId): Collection
    {
        return Room::where('room_type_id', $typeId)->where('active', true)->orderBy('sort')->orderBy('id')->get();
    }

    /**
     * Busy hours per unit for one operational date.
     *
     * @return array<int, array<int, bool>> room_id => [hour => true]
     */
    public function busyMap(int $typeId, string $date, ?int $ignoreReservationId = null): array
    {
        $key = "{$typeId}|{$date}|{$ignoreReservationId}";
        if (isset($this->maps[$key])) {
            return $this->maps[$key];
        }

        $roomIds = Room::where('room_type_id', $typeId)->pluck('id');
        $map = $roomIds->mapWithKeys(fn ($id) => [$id => []])->all();

        Reservation::occupying()->whereDate('date', $date)->whereIn('room_id', $roomIds)
            ->when($ignoreReservationId, fn ($q) => $q->where('id', '!=', $ignoreReservationId))
            ->get(['room_id', 'start_hour', 'hours'])
            ->each(function ($r) use (&$map) {
                for ($h = $r->start_hour; $h < $r->start_hour + $r->hours; $h++) {
                    $map[$r->room_id][$h] = true;
                }
            });

        Block::whereDate('date', $date)->whereIn('room_id', $roomIds)->get(['room_id', 'start_hour', 'end_hour'])
            ->each(function ($b) use (&$map) {
                for ($h = $b->start_hour; $h < $b->end_hour; $h++) {
                    $map[$b->room_id][$h] = true;
                }
            });

        return $this->maps[$key] = $map;
    }

    public function roomFree(array $map, int $roomId, int $hour, int $hours): bool
    {
        for ($h = $hour; $h < $hour + $hours; $h++) {
            if (! empty($map[$roomId][$h])) {
                return false;
            }
        }

        return true;
    }

    public function freeCount(int $typeId, string $date, int $hour): int
    {
        $map = $this->busyMap($typeId, $date);

        return $this->units($typeId)->filter(fn (Room $u) => empty($map[$u->id][$hour]))->count();
    }

    /** Longest run of consecutive hours from $hour on any single unit, capped by closing time. */
    public function maxDuration(int $typeId, string $date, int $hour, ?int $ignoreReservationId = null): int
    {
        $map = $this->busyMap($typeId, $date, $ignoreReservationId);
        $close = OpHours::close();
        $best = 0;
        foreach ($this->units($typeId) as $u) {
            $n = 0;
            while ($hour + $n < $close && empty($map[$u->id][$hour + $n])) {
                $n++;
            }
            $best = max($best, $n);
        }

        return $best;
    }

    /** First free active unit for the whole range (optionally a preferred unit). */
    public function freeRoom(int $typeId, string $date, int $hour, int $hours, ?int $preferredRoomId = null, ?int $ignoreReservationId = null): ?Room
    {
        $map = $this->busyMap($typeId, $date, $ignoreReservationId);
        $units = $this->units($typeId);
        if ($preferredRoomId) {
            $units = $units->where('id', $preferredRoomId);
        }

        return $units->first(fn (Room $u) => $this->roomFree($map, $u->id, $hour, $hours));
    }

    /** Can a guest start this option at $hour? (drives the start-hour list) */
    public function startOk(RoomType $type, ?Package $pkg, string $date, int $hour): bool
    {
        if (OpHours::isPast($date, $hour)) {
            return false;
        }
        if (! $pkg) {
            return $this->freeCount($type->id, $date, $hour) > 0;
        }
        if ($pkg->start_hour !== null && $hour !== $pkg->start_hour) {
            return false;
        }
        if ($hour + $pkg->hours > OpHours::close()) {
            return false;
        }

        return $this->maxDuration($type->id, $date, $hour) >= $pkg->hours;
    }

    /**
     * Business-rule validation of a booking request, independent of free units.
     * Returns an Indonesian error message, or null when the request is valid.
     */
    public function ruleError(RoomType $type, ?Package $pkg, string $date, int $hour, int $hours, bool $asAdmin = false): ?string
    {
        if (! $type->active) {
            return 'Ruangan ini sedang tidak tersedia.';
        }
        if (! $pkg && ! $type->rentable) {
            return "{$type->name} hanya tersedia dalam bentuk paket.";
        }
        if ($pkg && ($pkg->room_type_id !== $type->id || ! $pkg->active)) {
            return 'Paket ini tidak tersedia untuk ruangan yang dipilih.';
        }
        $hours = $pkg ? $pkg->hours : $hours;
        if ($hours < 1 || $hours > (int) config('dmk.max_rent_hours')) {
            return 'Durasi minimal 1 jam dan maksimal '.config('dmk.max_rent_hours').' jam.';
        }
        if ($hour < OpHours::open() || $hour + $hours > OpHours::close()) {
            return 'Jam booking harus di dalam jam buka ('.OpHours::label(OpHours::open()).'–'.OpHours::label(OpHours::close()).').';
        }
        if ($pkg && $pkg->start_hour !== null && $hour !== $pkg->start_hour) {
            return "{$pkg->name} hanya bisa dimulai pukul ".OpHours::label($pkg->start_hour).'.';
        }
        if (! $asAdmin) {
            $today = OpHours::today();
            $max = CarbonImmutable::parse($today)->addDays((int) Settings::get('max_days'))->toDateString();
            if ($date < $today || $date > $max) {
                return 'Tanggal reservasi di luar rentang yang bisa dipesan.';
            }
            if (OpHours::isPast($date, $hour)) {
                return 'Jam mulai ini sudah lewat. Pilih jam lain.';
            }
        }

        return null;
    }

    /**
     * Availability grid for the public page: every active type × operational hour.
     * Promo marks follow the guest's choice: with a package selected only that package's
     * row shows promos that would discount it at that start hour; otherwise rental promos.
     *
     * @return array<int, array{type: RoomType, total:int, cells: array}>
     */
    public function grid(string $date, PromoService $promos, ?Package $pkg = null): array
    {
        $rows = [];
        foreach (RoomType::active()->get() as $type) {
            $total = $this->units($type->id)->count();
            if (! $total) {
                continue;
            }
            if ($pkg) {
                $typePromos = $type->id === $pkg->room_type_id ? $promos->forTarget($pkg->pkgKey(), $date) : collect();
                $applies = fn (Promo $p, int $h) => $p->appliesToPackage($pkg, $h);
            } else {
                $typePromos = $type->rentable ? $promos->forTarget($type->rentKey(), $date) : collect();
                $applies = fn (Promo $p, int $h) => $p->inWindow($h);
            }
            $cells = [];
            foreach (OpHours::hours() as $h) {
                $past = OpHours::isPast($date, $h);
                $cells[] = [
                    'hour' => $h,
                    'label' => OpHours::label($h),
                    'early' => OpHours::isEarlyMorning($h),
                    'past' => $past,
                    'free' => $past ? 0 : $this->freeCount($type->id, $date, $h),
                    'promo' => $typePromos->contains(fn ($p) => $applies($p, $h)),
                ];
            }
            $rows[] = ['type' => $type, 'total' => $total, 'cells' => $cells];
        }

        return $rows;
    }
}
