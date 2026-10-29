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
            'cap' => 6, 'show_cap' => true, 'rentable' => true, 'rate_day' => 40000, 'rate_night' => 80000, 'sort' => 1,
        ]);
        $vip = RoomType::create([
            'name' => 'VIP Room', 'slug' => 'vip-room', 'description' => 'Ruang lebih luas untuk rombongan dan perayaan.',
            'facilities' => ['Ruang lebih lega', 'Sound system premium', 'Cocok untuk rombongan'], 'image' => $asset('p6'),
            'cap' => 10, 'show_cap' => true, 'rentable' => true, 'rate_day' => 60000, 'rate_night' => 120000, 'featured' => true, 'sort' => 2,
        ]);
        $master = RoomType::create([
            'name' => 'Master Room', 'slug' => 'master-room', 'description' => 'Pilihan paling lengkap untuk perayaan istimewa. Hanya tersedia dalam bentuk paket.',
            'facilities' => ['Ruang terbaik kami', 'Paket lengkap dengan LC', 'Food platter'], 'image' => $asset('p4'),
            'cap' => null, 'show_cap' => false, 'rentable' => false, 'rate_day' => 0, 'rate_night' => 0, 'sort' => 3,
        ]);

        foreach ([[$std, 'Standard', 3], [$vip, 'VIP', 5], [$master, 'Master', 1]] as [$type, $label, $n]) {
            for ($i = 1; $i <= $n; $i++) {
                $type->rooms()->create(['name' => "{$label} {$i}", 'sort' => $i]);
            }
        }

        // Contents of Standard/VIP packages other than Family are placeholders until the owner
        // provides the final list (PRD §8 open question).
        $room = 'Sewa ruangan 3 jam';
        $sort = 0;
        $add = function (RoomType $type, string $brand, array $names, array $pricesK, array $extra = []) use ($room, &$sort) {
            foreach ($names as $i => $name) {
                Package::create(array_merge([
                    'room_type_id' => $type->id, 'brand' => $brand, 'name' => $name, 'price' => $pricesK[$i] * 1000,
                    'hours' => 3, 'items' => [$room], 'family' => false, 'start_hour' => null, 'active' => true, 'sort' => ++$sort,
                ], $extra));
            }
        };
        $add($std, 'Paket Anggur DM', ['Paket Anggur DM 1', 'Paket Anggur DM 2', 'Paket Anggur DM 3'], [525, 885, 1310]);
        $add($std, 'Paket Kawai DM', ['Paket Kawai DM I', 'Paket Kawai DM II', 'Paket Kawai DM III'], [540, 950, 1410]);
        $add($vip, 'Kawai VIP DM', ['Kawai VIP DM I', 'Kawai VIP DM II', 'Kawai VIP DM III'], [640, 1030, 1510]);
        $add($vip, 'Paket VIP Beer Anker', ['Paket VIP Beer Anker A', 'Paket VIP Beer Anker B', 'Paket VIP Beer Anker C'], [645, 985, 1450]);
        $add($vip, 'Anggur VIP DM', ['Anggur VIP DM 1', 'Anggur VIP DM 2', 'Anggur VIP DM 3'], [610, 960, 1410]);
        $add($master, 'Master Room', ['Iceland Vodka', 'Kawa Kawa', 'Anggur Beer', 'Vibe'], [2350, 2450, 2500, 2600],
            ['items' => [$room, '3 LC (pemandu lagu)', '3 Le Minerale', '2 Hexos', 'Food platter', 'Tissue']]);
        $add($std, 'Paket Family (siang)', ['Fun Package'], [115],
            ['family' => true, 'start_hour' => 15, 'items' => [$room, '1 pitcher Teh Javana', '1 French Fries']]);
        $add($vip, 'Paket Family (siang)', ['Baddas Package'], [165],
            ['family' => true, 'start_hour' => 15, 'items' => [$room, '1 pitcher Teh Javana', '1 French Fries', '1 permen Hexos']]);

        User::create([
            'name' => 'Administrator', 'username' => 'admin', 'role' => 'super_admin', 'active' => true,
            'password' => env('SEED_ADMIN_PASSWORD', 'dmkaraoke'),
        ]);
    }
}
