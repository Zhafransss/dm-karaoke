<?php

/*
 * Business constants for DM Karaoke & Cafe.
 * Values that admins can change at runtime live in the `settings` table
 * (App\Support\Settings); this file only holds fixed rules and defaults.
 */
return [

    'business' => [
        'name' => 'DM Karaoke & Cafe',
        'address' => 'Jl. Raya Margasana, Rahayu, Menganti, Kec. Rawalo, Kabupaten Banyumas, Jawa Tengah 53174',
        'street' => 'Jl. Raya Margasana, Rahayu, Menganti',
        'locality' => 'Rawalo',
        'region' => 'Jawa Tengah',
        'postal_code' => '53174',
        'plus_code' => 'F46X+9HJ',
        'lat' => -7.539056,
        'lng' => 109.148889,
        'maps_link' => 'https://www.google.com/maps/search/?api=1&query=-7.539056,109.148889',
        'maps_embed' => 'https://www.google.com/maps?q=-7.539056,109.148889&z=17&output=embed',
        'instagram' => 'DmKaraokeofficial',
        'tiktok' => 'dm.karaoke.cafe4',
        'facebook_url' => 'https://www.facebook.com/share/14vSn3oSKq6/',
    ],

    // Search engine verification codes (the content="" of the meta tag from Search Console / Bing Webmaster), set in .env.
    'seo' => [
        'google_verification' => env('GOOGLE_SITE_VERIFICATION'),
        'bing_verification' => env('BING_SITE_VERIFICATION'),
    ],

    // Permissions per role (SRS §2 and §6.2). Every role can open the dashboard;
    // its widgets are filtered by the remaining permissions.
    'roles' => [
        'super_admin' => [
            'label' => 'Super Admin',
            'perms' => ['dashboard', 'reservasi', 'promo', 'event', 'galeri', 'ruangan', 'pesan', 'pengguna', 'pengaturan'],
        ],
        'admin_reservasi' => [
            'label' => 'Admin Reservasi',
            'perms' => ['dashboard', 'reservasi'],
        ],
        'admin_konten' => [
            'label' => 'Admin Konten',
            'perms' => ['dashboard', 'promo', 'event', 'galeri', 'ruangan'],
        ],
        'admin_pesan' => [
            'label' => 'Admin Pesan',
            'perms' => ['dashboard', 'pesan'],
        ],
    ],

    'perm_labels' => [
        'dashboard' => 'Dashboard', 'reservasi' => 'Jadwal & reservasi', 'promo' => 'Promo', 'event' => 'Event',
        'galeri' => 'Galeri', 'ruangan' => 'Ruangan & paket', 'pesan' => 'Pesan', 'pengguna' => 'Pengguna', 'pengaturan' => 'Pengaturan',
    ],

    'statuses' => [
        'menunggu' => ['label' => 'Menunggu', 'badge' => 'badge-gold'],
        'dikonfirmasi' => ['label' => 'Dikonfirmasi', 'badge' => 'badge-success'],
        'selesai' => ['label' => 'Selesai', 'badge' => ''],
        'dibatalkan' => ['label' => 'Dibatalkan', 'badge' => 'badge-danger'],
        'tidak_datang' => ['label' => 'Tidak datang', 'badge' => 'badge-velvet'],
    ],

    // Statuses that occupy a unit (SRS §4.7).
    'active_statuses' => ['menunggu', 'dikonfirmasi'],

    // Allowed status transitions (SRS §4.8).
    'transitions' => [
        'menunggu' => ['dikonfirmasi', 'dibatalkan', 'tidak_datang'],
        'dikonfirmasi' => ['selesai', 'dibatalkan', 'tidak_datang'],
    ],

    'promo_kinds' => [
        'persen' => 'Persen',
        'nominal' => 'Nominal per transaksi',
        'bayar_x' => 'Bayar sebagian jam (contoh: sewa 3 jam bayar 2 jam)',
    ],

    'gallery_categories' => ['Ruangan', 'Area', 'Event', 'Cafe'],

    'message_kinds' => ['Saran', 'Kritik', 'Pertanyaan', 'Lainnya'],

    'wa_gateways' => ['' => 'Belum dipilih (simulasi)', 'fonnte' => 'Fonnte'],

    'template_vars' => ['nama', 'hp', 'kode', 'ruangan', 'unit', 'paket', 'lc', 'tanggal', 'jam', 'durasi', 'total', 'orang', 'pembayaran'],

    'rate_limit' => [
        'reservations_per_hour' => 3,
        'status_checks_per_minute' => 10,
        'messages_per_hour' => 5,
        'login_per_minute' => 5,
    ],

    // Technical guest limit when a room type has no displayed capacity (FR-RES-05).
    'max_pax_fallback' => 30,

    // Longest rental the form offers (operational day is at most 24 h).
    'max_rent_hours' => 12,

    'wa_max_attempts' => 3,

    // Largest video upload in MB (gallery and room detail). The server's upload_max_filesize / post_max_size must be larger.
    'max_video_mb' => 10,

    // Defaults written to `settings` by the seeder and used when a key is missing.
    'settings_defaults' => [
        'open' => 14,
        'close' => 28,
        'day_end' => 19,
        'max_days' => 30,
        'wa_display' => '0822-2041-3723',
        'wa_number' => '6282220413723',
        'wa_admin' => '082220413723',
        'wa_gateway' => '',
        'wa_token' => '',
        'wa_auto_status' => true,
        'tpl_admin' => "Reservasi baru masuk\nKode: {kode}\nNama: {nama} ({hp})\nRuangan: {ruangan}, {unit}\nPesanan: {paket}\nTanggal: {tanggal}\nJam: {jam}\nJumlah orang: {orang}\nTotal: {total}\n\nBuka panel admin untuk konfirmasi.",
        'tpl_guest' => "Halo {nama}, reservasi kamu di DM Karaoke & Cafe sudah kami terima.\n\nKode: {kode}\nRuangan: {ruangan}\nPesanan: {paket}\nTanggal: {tanggal}\nJam: {jam}\nEstimasi total: {total} ({pembayaran})\n\nStatus: menunggu konfirmasi admin. Kami akan segera menghubungi kamu.",
        'tpl_confirm' => "Halo {nama}, reservasi {kode} di DM Karaoke & Cafe sudah DIKONFIRMASI.\n\nRuangan: {ruangan}\nPesanan: {paket}\nTanggal: {tanggal}\nJam: {jam}\nTotal: {total} ({pembayaran})\n\nSampai jumpa!",
        'tpl_reject' => 'Halo {nama}, mohon maaf reservasi {kode} untuk {tanggal} pukul {jam} belum bisa kami terima. Silakan pilih jadwal lain di website atau balas pesan ini. Terima kasih.',
        'analytics_id' => '',
        'meta_pixel_id' => '',
    ],
];
