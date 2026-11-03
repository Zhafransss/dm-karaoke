<?php

namespace Tests\Concerns;

use App\Models\Package;
use App\Models\Promo;
use App\Models\RoomType;
use App\Support\Settings;

/** Minimal venue fixture matching the price list, without image processing. */
trait SeedsVenue
{
    protected RoomType $std;

    protected RoomType $vip;

    protected RoomType $master;

    protected function seedVenue(): void
    {
        Settings::set(config('dmk.settings_defaults'));

        $this->std = RoomType::create(['name' => 'Standard Room', 'slug' => 'standard-room', 'cap' => 6, 'show_cap' => true, 'rentable' => true, 'rate_day' => 40000, 'rate_night' => 80000, 'deposit' => 500000, 'sort' => 1, 'active' => true]);
        $this->vip = RoomType::create(['name' => 'VIP Room', 'slug' => 'vip-room', 'cap' => 10, 'show_cap' => true, 'rentable' => true, 'rate_day' => 60000, 'rate_night' => 120000, 'deposit' => 750000, 'sort' => 2, 'active' => true]);
        $this->master = RoomType::create(['name' => 'Master Room', 'slug' => 'master-room', 'cap' => null, 'show_cap' => false, 'rentable' => false, 'rate_day' => 0, 'rate_night' => 0, 'prepaid' => true, 'sort' => 3, 'active' => true]);

        foreach ([[$this->std, 'Standard', 3], [$this->vip, 'VIP', 5], [$this->master, 'Master', 1]] as [$t, $label, $n]) {
            for ($i = 1; $i <= $n; $i++) {
                $t->rooms()->create(['name' => "{$label} {$i}", 'sort' => $i]);
            }
        }

        $this->pkg($this->std, 'Fun Package', 115000, ['family' => true, 'start_hour' => 15, 'brand' => 'Paket Family (siang)']);
        $this->pkg($this->vip, 'Baddas Package', 165000, ['family' => true, 'start_hour' => 15, 'brand' => 'Paket Family (siang)']);
        $this->pkg($this->vip, 'Kawai VIP DM II', 1030000);
        $this->pkg($this->master, 'Vibe', 2600000, ['brand' => 'Master Room']);
    }

    protected function pkg(RoomType $type, string $name, int $price, array $extra = []): Package
    {
        return Package::create(array_merge([
            'room_type_id' => $type->id, 'brand' => 'Paket', 'name' => $name, 'price' => $price, 'hours' => 3,
            'items' => ['Sewa ruangan 3 jam'], 'family' => false, 'start_hour' => null, 'active' => true,
        ], $extra));
    }

    protected function pkgNamed(string $name): Package
    {
        return Package::where('name', $name)->firstOrFail();
    }

    protected function promo(array $data, array $targets): Promo
    {
        $p = Promo::create(array_merge(['description' => null, 'value' => 0, 'active' => true], $data));
        $p->syncTargets($targets);

        return $p;
    }
}
