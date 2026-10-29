<?php

namespace Database\Seeders;

use App\Models\Block;
use App\Models\Event;
use App\Models\GalleryItem;
use App\Models\Message;
use App\Models\Package;
use App\Models\Promo;
use App\Models\Reservation;
use App\Models\ReservationLog;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use App\Models\WaLog;
use App\Services\ImageService;
use App\Services\PricingService;
use App\Services\PromoService;
use App\Services\WhatsApp\WhatsAppService;
use App\Support\OpHours;
use App\Support\Phone;
use App\Support\Settings;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

/** Demo content mirroring prototype v3. Not meant for production. */
class DemoSeeder extends Seeder
{
    public function run(ImageService $images, PricingService $pricing, PromoService $promos): void
    {
        $T = OpHours::today();
        $d = fn (int $n) => CarbonImmutable::parse($T)->addDays($n)->toDateString();
        $img = fn (string $p, string $dir) => $images->store(database_path("seeders/assets/{$p}.jpg"), $dir);
        $pkg = fn (string $name) => Package::where('name', $name)->value('id');
        $std = RoomType::where('slug', 'standard-room')->first();
        $vip = RoomType::where('slug', 'vip-room')->first();
        $master = RoomType::where('slug', 'master-room')->first();

        foreach ([
            ['kasir', 'Kasir DM', 'admin_reservasi', 'kasir123', true],
            ['dewi', 'Dewi Maharani', 'admin_konten', 'konten123', true],
            ['bayu', 'Bayu Saputra', 'admin_pesan', 'pesan123', false],
        ] as [$u, $n, $role, $pw, $active]) {
            User::create(['username' => $u, 'name' => $n, 'role' => $role, 'password' => $pw, 'active' => $active]);
        }

        $promo = function (array $data, array $targets) {
            $p = Promo::create($data);
            $p->syncTargets($targets);
        };
        $promo(['name' => 'Sewa 3 Jam Bayar 2 Jam', 'description' => 'Sewa ruangan 3 jam, cukup bayar 2 jam. Berlaku untuk Standard dan VIP Room.',
            'kind' => 'bayar_x', 'x' => 3, 'y' => 2, 'date_start' => $d(-3), 'date_end' => $d(20)], [$std->rentKey(), $vip->rentKey()]);
        $promo(['name' => 'Diskon Paket Siang', 'description' => 'Diskon 10% untuk paket yang mulai di jam 14.00–17.00.',
            'kind' => 'persen', 'value' => 10, 'date_start' => $T, 'date_end' => $d(14), 'time_start' => 14, 'time_end' => 17],
            ['pkg:'.$pkg('Paket Kawai DM II'), 'pkg:'.$pkg('Kawai VIP DM II'), 'pkg:'.$pkg('Paket Anggur DM 2')]);
        $promo(['name' => 'Potongan Master Room', 'description' => 'Potongan Rp 100.000 per transaksi untuk paket Vibe dan Kawa Kawa.',
            'kind' => 'nominal', 'value' => 100000, 'date_start' => $T, 'date_end' => $d(30)], ['pkg:'.$pkg('Vibe'), 'pkg:'.$pkg('Kawa Kawa')]);
        $promo(['name' => 'Promo Ulang Tahun', 'description' => 'Diskon untuk tamu yang berulang tahun (draf, belum diaktifkan).',
            'kind' => 'persen', 'value' => 15, 'date_start' => $T, 'date_end' => $d(30), 'active' => false], [$vip->rentKey()]);
        $promos->flush();

        foreach ([
            ['Malam Akustik Jumat', $d(3), '20.00', '23.00', 'p5', true, true, "Nyanyi diiringi band akustik di lounge DM Karaoke. Tamu yang reservasi ruangan malam itu bisa naik panggung lounge dan duet bersama band.\n\nGratis untuk semua tamu yang booking ruangan pada hari acara."],
            ['Anniversary DM Karaoke ke-2', $d(12), '19.00', '00.00', 'p7', true, true, "Rayakan ulang tahun kedua DM Karaoke & Cafe bersama kami. Ada doorprize, potong tumpeng, dan hiburan sepanjang malam.\n\nSlot ruangan terbatas, reservasi lebih awal ya."],
            ['Lomba Karaoke Antar Teman', $d(21), '15.00', '20.00', null, false, false, 'Draf event, belum ditampilkan di website.'],
            ['Perayaan Agustusan', '2026-08-17', '13.00', '22.00', 'p0', true, false, 'Perayaan Hari Kemerdekaan bersama tim dan tamu DM Karaoke, lengkap dengan tumpeng dan dekorasi merah putih.'],
            ['Anniversary DM Karaoke ke-1', $d(-60), '19.00', '00.00', 'p9', true, false, 'Syukuran satu tahun DM Karaoke & Cafe bersama seluruh tim.'],
        ] as [$title, $date, $s, $e, $photo, $pub, $feat, $body]) {
            Event::create(['title' => $title, 'slug' => Event::uniqueSlug($title), 'date' => $date, 'start_time' => $s, 'end_time' => $e,
                'image' => $photo ? $img($photo, 'events') : null, 'published' => $pub, 'featured' => $feat, 'body' => $body]);
        }

        $sort = 0;
        foreach ([
            ['Ruang karaoke', 'Ruangan', 'p6'], ['Resepsionis DM Karaoke', 'Area', 'p1'], ['Anniversary DM Karaoke', 'Event', 'p7'],
            ['Ruang sofa merah', 'Ruangan', 'p4'], ['Lorong ruang karaoke', 'Area', 'p3'], ['Perayaan Agustusan', 'Event', 'p0'],
            ['Lobby utama', 'Area', 'p2'], ['Tim DM Karaoke', 'Event', 'p8'], ['Tampak depan di malam hari', 'Area', 'p5'],
            ['Syukuran anniversary', 'Event', 'p9'],
        ] as [$cap, $cat, $p]) {
            GalleryItem::create(['caption' => $cap, 'category' => $cat, 'image' => $img($p, 'gallery'), 'sort' => ++$sort, 'published' => true]);
        }

        foreach ([
            ['Rina Putri', '081234567890', 'Saran', 'Tolong tambah lagu dangdut dan pop Jawa terbaru ya, kemarin beberapa lagu viral belum ada.', 3, false],
            ['Sari W.', 'sari.w@email.com', 'Kritik', 'AC di ruang Standard kurang dingin waktu Sabtu malam. Selebihnya pelayanan ramah.', 29, false],
            ['Ahmad Fauzi', '085700112233', 'Pertanyaan', 'Apakah bisa booking untuk acara kantor sekitar 20 orang? Ada paket makanannya?', 96, true],
        ] as [$n, $c, $k, $m, $hAgo, $read]) {
            $at = now()->subHours($hAgo);
            Message::create(['name' => $n, 'contact' => $c, 'kind' => $k, 'message' => $m, 'read_at' => $read ? $at : null])
                ->forceFill(['created_at' => $at])->save();
        }

        $admin = User::where('username', 'admin')->first();
        $kasir = User::where('username', 'kasir')->first();
        $res = function (RoomType $type, string $unit, int $off, int $hour, int $hours, string $name, string $phone, int $pax, string $status, array $o = []) use ($d, $pricing, $admin, $kasir) {
            $p = isset($o['pkg']) ? Package::where('name', $o['pkg'])->first() : null;
            $date = $d($off);
            $q = $pricing->calculate($type, $p, $date, $hour, $hours, $o['lc'] ?? false);
            $online = ($o['source'] ?? 'online') === 'online';
            $r = Reservation::create([
                'code' => 'DM-'.substr($date, 8, 2).substr($date, 5, 2).'-'.strtoupper(substr(md5($name.$date), 0, 4)),
                'room_type_id' => $type->id, 'room_id' => Room::where('name', $unit)->value('id'), 'package_id' => $p?->id,
                'package_name' => $q['package_name'], 'lc' => $q['lc'], 'date' => $date, 'start_hour' => $hour, 'hours' => $q['hours'],
                'pax' => $pax, 'name' => $name, 'phone' => $phone ?: null, 'note' => $o['note'] ?? null, 'status' => $status,
                'base' => $q['base'], 'discount' => $q['discount'], 'total' => $q['total'], 'promo_id' => $q['promo_id'],
                'promo_name' => $q['promo_name'], 'promo_note' => $q['promo_note'] ?: null, 'price_lines' => $q['lines'],
                'source' => $online ? 'online' : 'admin', 'created_by' => $online ? null : $admin->id,
            ]);
            $log = fn ($from, $to, $user, $note = null) => ReservationLog::create(['reservation_id' => $r->id, 'user_id' => $user?->id, 'from_status' => $from, 'to_status' => $to, 'note' => $note]);
            $log(null, $online ? 'menunggu' : $status, $online ? null : $admin, $online ? 'Reservasi online' : 'Dibuat admin');
            if ($online && $status !== 'menunggu') {
                $mid = in_array($status, ['selesai', 'tidak_datang']) ? 'dikonfirmasi' : $status;
                $log('menunggu', $mid, $admin);
                if ($mid !== $status) {
                    $log($mid, $status, $kasir);
                }
            }
            if ($online) {
                $vars = $r->load('roomType', 'room')->templateVars();
                foreach ([['admin', Settings::get('wa_admin'), 'tpl_admin'], ['guest', $phone, 'tpl_guest']] as [$who, $to, $tpl]) {
                    WaLog::create(['reservation_id' => $r->id, 'recipient' => $who, 'to' => Phone::toWa($to), 'template' => $tpl,
                        'body' => WhatsAppService::render(Settings::get($tpl), $vars), 'status' => 'simulated', 'attempts' => 1, 'sent_at' => now()]);
                }
            }
        };

        $res($vip, 'VIP 1', -2, 20, 3, 'Fajar Nugroho', '081390012233', 9, 'dibatalkan');
        $res($std, 'Standard 1', -1, 20, 2, 'Lina Kartika', '082134455667', 4, 'selesai');
        $res($vip, 'VIP 2', -1, 19, 3, 'Hendra W.', '085612340987', 8, 'tidak_datang', ['pkg' => 'Kawai VIP DM I']);
        $res($vip, 'VIP 1', 0, 14, 3, 'Keluarga Hartono', '081227788990', 9, 'dikonfirmasi', ['note' => 'Arisan keluarga']);
        $res($std, 'Standard 1', 0, 19, 2, 'Rina Putri', '081234567890', 5, 'dikonfirmasi');
        $res($master, 'Master 1', 0, 20, 3, 'Sinta Amelia', '087812312312', 12, 'dikonfirmasi', ['pkg' => 'Vibe', 'note' => 'Ulang tahun, minta dekorasi balon']);
        $res($std, 'Standard 2', 0, 20, 3, 'Dimas Aditya', '085712345678', 6, 'menunggu', ['lc' => true]);
        $res($std, 'Standard 3', 0, 21, 2, 'Walk-in', '', 3, 'dikonfirmasi', ['source' => 'admin']);
        $res($std, 'Standard 1', 1, 15, 3, 'Nadia Salsabila', '082255556666', 4, 'menunggu', ['pkg' => 'Fun Package']);
        $res($vip, 'VIP 1', 1, 21, 3, 'Tim Marketing CV Maju', '081355557777', 10, 'menunggu', ['note' => 'Acara kantor']);
        $res($std, 'Standard 2', 1, 23, 4, 'Bagas Pratama', '089912340000', 5, 'menunggu');

        Block::create(['room_id' => Room::where('name', 'VIP 3')->value('id'), 'date' => $d(1), 'start_hour' => 14, 'end_hour' => 17,
            'reason' => 'Perawatan sound system', 'user_id' => $admin->id]);
    }
}
