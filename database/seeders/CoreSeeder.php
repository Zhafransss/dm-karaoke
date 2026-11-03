<?php

namespace Database\Seeders;

use App\Models\Package;
use App\Models\RoomType;
use App\Models\User;
use App\Services\ImageService;
use App\Support\Settings;
use Illuminate\Database\Seeder;

/**
 * Production seed (SRS §5 "Seed awal"): settings, 3 room types, 9 units,
 * packages from the DM Karaoke price list, and one Super Admin.
 */
class CoreSeeder extends Seeder
{
    public function run(ImageService $images): void
    {
        Settings::set(config('dmk.settings_defaults'));

        $asset = fn (string $name) => $images->store(database_path("seeders/assets/{$name}.jpg"), 'rooms');

        $std = RoomType::create([
            'name' => 'Standard Room', 'slug' => 'standard-room', 'description' => 'Pas untuk kumpul keluarga kecil atau teman dekat.',
            'facilities' => ['Layar TV besar', 'Mic nirkabel', 'Sofa nyaman'], 'image' => $asset('p6c'),
            'cap' => 6, 'show_cap' => true, 'rentable' => true, 'rate_day' => 40000, 'rate_night' => 80000, 'deposit' => 500000, 'sort' => 1,
        ]);
        $vip = RoomType::create([
            'name' => 'VIP Room', 'slug' => 'vip-room', 'description' => 'Ruang lebih luas untuk rombongan dan perayaan.',
            'facilities' => ['Ruang lebih lega', 'Sound system premium', 'Cocok untuk rombongan'], 'image' => $asset('p6'),
            'cap' => 10, 'show_cap' => true, 'rentable' => true, 'rate_day' => 60000, 'rate_night' => 120000, 'deposit' => 500000, 'featured' => true, 'sort' => 2,
        ]);
        $master = RoomType::create([
            'name' => 'Master Room', 'slug' => 'master-room', 'description' => 'Pilihan paling lengkap untuk perayaan istimewa. Hanya tersedia dalam bentuk paket.',
            'facilities' => ['Ruang terbaik kami', 'Paket lengkap dengan LC', 'Food platter'], 'image' => $asset('p4'),
            'cap' => null, 'show_cap' => false, 'rentable' => false, 'rate_day' => 0, 'rate_night' => 0, 'prepaid' => true, 'sort' => 3,
        ]);

        foreach ([[$std, 'Standard', 3], [$vip, 'VIP', 5], [$master, 'Master', 1]] as [$type, $label, $n]) {
            for ($i = 1; $i <= $n; $i++) {
                $type->rooms()->create(['name' => "{$label} {$i}", 'sort' => $i]);
            }
        }

        $types = ['standard-room' => $std, 'vip-room' => $vip, 'master-room' => $master];
        $sort = 0;
        foreach (self::packages() as [$slug, $brand, $name, $priceK, $hours, $items, $extra]) {
            Package::create(array_merge([
                'room_type_id' => $types[$slug]->id, 'brand' => $brand, 'name' => $name, 'price' => $priceK * 1000,
                'hours' => $hours, 'items' => $items, 'family' => false, 'start_hour' => null, 'active' => true, 'sort' => ++$sort,
            ], $extra));
        }

        User::create([
            'name' => 'Administrator', 'username' => 'admin', 'role' => 'super_admin', 'active' => true,
            'password' => env('SEED_ADMIN_PASSWORD', 'dmkaraoke'),
        ]);
    }

    /**
     * Package catalogue from the owner's price list (received 30 Oct 2026).
     * Rows: [room type slug, brand, name, price in thousands, hours, items, extra columns].
     *
     * @return array<int, array{0:string, 1:string, 2:string, 3:int, 4:int, 5:array<int, string>, 6:array<string, mixed>}>
     */
    public static function packages(): array
    {
        $water = fn (int $n) => "{$n} Botol Mineral Water + {$n} Hexos";
        $leMin = fn (int $n) => "{$n} Le Minerale + {$n} Hexos";
        $snack = '1 Porsi Snack (French Fries / Sosis / Nugget)';
        $box = '1 Tissue (1 box)';
        // Every Master Room package also includes these.
        $master = ['3 Jam Room', '3 LC', '3 Le Minerale', '2 Hexos', 'Food Platter', 'Tissue'];
        $family = ['family' => true, 'start_hour' => 15];

        return [
            ['standard-room', 'Paket Anggur DM', 'Anggur DM 1', 525, 2, ['1 Botol Minuman', $water(1), '1 LC (2 jam)', '2 Jam Room', $box], []],
            ['standard-room', 'Paket Anggur DM', 'Anggur DM 2', 885, 2, ['2 Botol Minuman', $water(2), '2 LC (2 jam)', '2 Jam Room', $box], []],
            ['standard-room', 'Paket Anggur DM', 'Anggur DM 3', 1310, 3, ['3 Botol Minuman', $water(2), '1 Porsi Makanan Ringan (Chicken Wing / Sosis / Dumpling)', '2 LC (3 jam)', '3 Jam Room', $box], []],
            ['standard-room', 'Paket Kawai DM', 'Kawai DM I', 540, 2, ['1 Botol Minuman', $leMin(1), '1 LC (2 jam)', '2 Jam Room', $box], []],
            ['standard-room', 'Paket Kawai DM', 'Kawai DM II', 950, 2, ['2 Botol Minuman', $leMin(2), $snack, '2 LC (2 jam)', '2 Jam Room', $box], []],
            ['standard-room', 'Paket Kawai DM', 'Kawai DM III', 1410, 3, ['3 Botol Minuman', $leMin(2), $snack, '2 LC (3 jam)', '3 Jam Room', $box], []],
            ['vip-room', 'Kawai VIP DM', 'Kawai VIP DM I', 640, 2, ['1 Botol Minuman', $leMin(1), '1 LC (2 jam)', '2 Jam Room', $box], []],
            ['vip-room', 'Kawai VIP DM', 'Kawai VIP DM II', 1030, 2, ['2 Botol Minuman', $leMin(2), $snack, '2 LC (2 jam)', '2 Jam Room', $box], []],
            ['vip-room', 'Kawai VIP DM', 'Kawai VIP DM III', 1510, 3, ['3 Botol Minuman', $leMin(2), $snack, '2 LC (3 jam)', '3 Jam Room', $box], []],
            ['vip-room', 'Paket VIP Beer Anker', 'VIP A - BEER ANKER', 645, 2, ['2 Botol Minuman', $leMin(1), '1 LC (2 jam)', '2 Jam Room'], []],
            ['vip-room', 'Paket VIP Beer Anker', 'VIP B - BEER ANKER', 985, 2, ['3 Botol Minuman', $leMin(2), $snack, '2 LC (2 jam)', '2 Jam Room'], []],
            ['vip-room', 'Paket VIP Beer Anker', 'VIP C - BEER ANKER', 1450, 3, ['4 Botol Minuman', $leMin(2), $snack, '2 LC (3 jam)', '3 Jam Room'], []],
            ['vip-room', 'Anggur VIP DM', 'Anggur VIP DM 1', 610, 2, ['1 Botol Minuman', $water(1), '1 LC (2 jam)', '2 Jam Room', '1 Tissue'], []],
            ['vip-room', 'Anggur VIP DM', 'Anggur VIP DM 2', 960, 2, ['2 Botol Minuman', $water(2), '1 Porsi Makanan Ringan (Chicken Wing / Sosis / Dumpling / French Fries)', '2 LC (2 jam)', '2 Jam Room', '1 Tissue'], []],
            ['vip-room', 'Anggur VIP DM', 'Anggur VIP DM 3', 1410, 3, ['3 Botol Minuman', '1 Porsi Makanan Ringan (Chicken Wing / Sosis / Dumpling / French Fries)', '2 LC (3 jam)', '3 Jam Room', '1 Tissue'], []],
            ['master-room', 'Master Room', 'Paket Iceland Vodka', 2350, 3, ['1 Botol Minuman', '2 Green Sand', '2 Miso', ...$master], []],
            ['master-room', 'Master Room', 'Paket Kawa Kawa', 2450, 3, ['4 Botol Minuman', ...$master], []],
            ['master-room', 'Master Room', 'Paket Anggur Beer', 2500, 3, ['6 Botol Minuman', ...$master], []],
            ['master-room', 'Master Room', 'Paket Vibe', 2600, 3, ['1 Botol Minuman', ...$master], []],
            ['standard-room', 'Paket Family (siang)', 'Fun Package', 115, 3, ['Free Room 3 jam', '1 Pitcher Teh Javana', '1 Porsi French Fries'], $family],
            ['vip-room', 'Paket Family (siang)', 'Baddas Package', 165, 3, ['Free Room 3 jam', '1 Pitcher Teh Javana', '1 Porsi French Fries', '1 Permen Hexos'], $family],
        ];
    }
}
