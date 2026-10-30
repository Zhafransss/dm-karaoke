# RTM – Website DM Karaoke & Cafe

Requirement Traceability Matrix. Sumber: `docs/PRD-DM-Karaoke.md` (tujuan bisnis) dan `docs/SRS-DM-Karaoke.md` (requirement).
Terakhir diperbarui: 30 Oktober 2026 (nominal deposit Rp 500.000; isi & durasi paket dari price list client).

**Tujuan bisnis (PRD §1)**

| Kode | Tujuan |
| --- | --- |
| G1 | Reservasi masuk lewat website, bukan hanya chat WhatsApp |
| G2 | Tidak ada dua booking di ruangan dan jam yang sama |
| G3 | Harga, paket, dan promo selalu konsisten dengan price list |
| G4 | Bisnis mudah ditemukan di pencarian lokal |

**Status:** Done = selesai dan lulus uji otomatis · Done (UAT) = selesai, belum diuji manual di browser · In Progress · Not Started.
Uji otomatis ada di `tests/Feature/PricingServiceTest.php` (T-01–T-15) dan `tests/Feature/ReservationFlowTest.php` (T-20–T-30).

## Kebutuhan fungsional

| ID | Tujuan Bisnis | Requirement | Fitur/Modul | Prioritas | Status | Test Case |
|----|---------------|-------------|-------------|-----------|--------|-----------|
| FR-PUB-01 | G1, G4 | Beranda: hero, ruangan + harga mulai, promo, event, galeri, WhatsApp | `site.home` | W | Done (UAT) | T-30 |
| FR-PUB-02 | G3 | Halaman Harga: sewa siang/malam, paket per merek, Family, 21+, deposit | `site.prices` | W | Done (UAT) | T-30 |
| FR-PUB-03 | G1 | Promo berjalan & terjadwal | `site.promos` | W | Done (UAT) | – |
| FR-PUB-04 | G4 | Event daftar + detail per slug, hanya terbit | `site.events`, `site.event` | P | Done (UAT) | – |
| FR-PUB-05 | G4 | Galeri kategori + lightbox | `site.gallery` | P | Done (UAT) | – |
| FR-PUB-06 | G1, G4 | Kontak: alamat, peta, jam, WA, IG, form pesan | `site.contact` | W | Done (UAT) | – |
| FR-PUB-07 | G4 | Meta, Open Graph, JSON-LD bisnis + koordinat | `layouts.public` | W | Done | – |
| FR-PUB-08 | G3 | Kapasitas Master tidak tampil di publik | `RoomType::publicCap()` | W | Done | T-30 |
| FR-RES-01 | G1 | Pilih tipe, tanggal, mode, jam, durasi, jumlah orang | `site.reservation` + Alpine | W | Done (UAT) | – |
| FR-RES-02 | G1 | Mode default: sewa saja; Master langsung paket | `ReservationController@create` | W | Done (UAT) | – |
| FR-RES-03 | G1, G2 | Grid ketersediaan, label "dini hari"; publik hanya melihat Tersedia/Penuh tanpa jumlah unit; penanda promo mengikuti pilihan (paket → hanya promo paket itu) (keputusan client 30 Okt 2026) ; daftar promo aktif di tanggal terpilih tampil di bawah grid | `AvailabilityService::grid`, `ReservationController@availability`, `Promo::appliesToPackageAt` | W | Done | T-21, T-21b, T-21c, T-21d |
| FR-RES-04 | G2, G3 | Jam bulat, min 1 jam, ≤ jam tutup, aturan paket | `AvailabilityService::ruleError` | W | Done | T-08, T-21 |
| FR-RES-05 | G2 | Batas kapasitas; Master batas teknis 30 | `RoomType::maxPax()` | W | Done | – |
| FR-RES-06 | G3 | LC hanya centang, tidak mengubah total | `PricingService` | W | Done | T-02, T-04, T-05 |
| FR-RES-07 | G3 | Ringkasan harga langsung + promo + rincian jam | `/reservasi/harga` | W | Done | T-28 |
| FR-RES-08 | G1 | Nama, HP format Indonesia, catatan; validasi server | `ReservationController@store` | W | Done | T-22 |
| FR-RES-09 | G2 | Unit dipilih atomik, transaksi + `lockForUpdate` | `ReservationService::create` | W | Done | T-20 |
| FR-RES-10 | G3 | Harga & promo dikunci (snapshot) | kolom `base/discount/total/price_lines/promo_name/package_name` | W | Done | T-29 |
| FR-RES-11 | G1 | Kode unik, halaman sukses, status awal menunggu | `ReservationService::newCode` | W | Done | T-22 |
| FR-RES-12 | G2 | Maks 3 reservasi/jam/IP | `RateLimiter` `reserve:<ip>` | W | Done | T-22 |
| FR-RES-13 | G1 | Cek status kode + HP tanpa login | `StatusController` | W | Done | T-27 |
| FR-RES-14 | G1 | WA ke pemesan dan admin memakai template | `WhatsAppService::queueNewReservation` | W | Done | T-26 |
| FR-ADM-01 | – | Login username + sandi, throttle, logout | `AuthController` | W | Done (UAT) | – |
| FR-ADM-02 | – | Hak akses per peran, 403 di luar peran | Gate per permission | W | Done | T-25 |
| FR-ADM-03 | G1 | Dashboard: hari ini, menunggu, pesan, promo | `DashboardController` | W | Done (UAT) | – |
| FR-ADM-04 | G2 | Jadwal harian unit × jam termasuk blok & dini hari | `admin.schedule` | W | Done (UAT) | – |
| FR-ADM-05 | G1 | Daftar reservasi + filter status/tanggal/tipe/cari | `Admin\ReservationController@index` | W | Done (UAT) | – |
| FR-ADM-06 | G2 | Ubah status + log siapa/kapan | `ReservationService::changeStatus` | W | Done | T-24 |
| FR-ADM-07 | G1, G3 | Reservasi walk-in/telepon, harga sama | `Admin\ReservationController@store` | W | Done (UAT) | – |
| FR-ADM-08 | G2 | Blokir jadwal per unit | `ScheduleController@storeBlock` | W | Done (UAT) | – |
| FR-ADM-09 | G2 | Pindah unit / ubah jam + validasi bentrok | `ReservationService::reschedule` | P | Done (UAT) | – |
| FR-ADM-10 | G3 | CRUD tipe ruangan, tarif, unit | `RoomTypeController` | W | Done (UAT) | – |
| FR-ADM-11 | G3 | CRUD paket | `PackageController` | W | Done (UAT) | – |
| FR-ADM-12 | G1, G3 | CRUD promo + pratinjau perhitungan | `PromoController` | W | Done (UAT) | T-10–T-15 |
| FR-ADM-13 | G3 | Tolak promo beririsan; bayar X untuk sewa + paket ≥ X jam, potongan senilai tarif sewa per jam (revisi scope 30 Okt 2026) | `PromoService::conflicts`, `Promo::packageEligible`, `PricingService::discount` | W | Done | T-14, T-11b, T-11c |
| FR-ADM-14 | G4 | CRUD event & galeri, kompres gambar, status terbit | `EventController`, `GalleryController`, `ImageService` | P | Done (UAT) | – |
| FR-ADM-15 | – | Kotak masuk pesan: baca, balas WA, hapus | `MessageController` | P | Done (UAT) | – |
| FR-ADM-16 | – | Pengguna: tambah, peran, nonaktif, reset sandi | `UserController` | W | Done (UAT) | – |
| FR-ADM-17 | G3 | Pengaturan jam, batas siang, deposit, WA, template | `SettingController` | W | Done (UAT) | – |
| FR-WA-01 | G1 | Template dengan variabel | `WhatsAppService::render` | W | Done | – |
| FR-WA-02 | G1 | Kirim setelah respons; gagal tidak membatalkan reservasi | `SendWaMessages::dispatchAfterResponse` | W | Done | T-26 |
| FR-WA-03 | G1 | Log gagal + retry cron maks 3× | `wa:retry` tiap 5 menit | W | Done | T-26 |
| FR-WA-04 | G1 | Pesan saat dikonfirmasi/dibatalkan | `queueStatusChange` (bisa dimatikan di Pengaturan) | P | Done (UAT) | – |
| FR-WA-05 | G1 | Tombol wa.me cadangan di sukses & kontak | `Phone::waLink` | W | Done (UAT) | – |

## Kebutuhan non-fungsional

| ID | Tujuan Bisnis | Requirement | Implementasi | Status | Catatan |
|----|---------------|-------------|--------------|--------|---------|
| NFR-01 | G4 | LCP < 2,5 s di 4G | HTML server, WebP 2 ukuran, `loading=lazy` | In Progress | Perlu diukur Lighthouse di staging |
| NFR-02 | G1 | Endpoint < 500 ms | Peta jadwal di-cache per request | In Progress | Perlu diukur di hosting |
| NFR-03 | G2 | Konkurensi aman | Transaksi + `lockForUpdate` unit per tipe | Done | T-20 (sekuensial; uji paralel di staging) |
| NFR-04 | – | HTTPS, bcrypt, CSRF, escaping, throttle | Bawaan Laravel + throttle login/reservasi/cek status | Done | HTTPS dipasang di hosting |
| NFR-05 | – | Unggahan dibatasi, nama acak, token terenkripsi | `ImageService`, `Settings` | Done | – |
| NFR-06 | – | Privasi nama/HP | Halaman sukses hanya untuk sesi pemesan; cek status butuh HP | Done | T-27 |
| NFR-07 | G1 | 99%/bulan, WA gagal tidak ganggu | – | Done | Tergantung paket hosting |
| NFR-08 | G1 | ≤ 4 langkah di ponsel | 3 langkah | Done (UAT) | – |
| NFR-09 | – | WCAG 2.1 AA | Label, fokus, skip link, aria | In Progress | Perlu audit |
| NFR-10 | G4 | SEO server-rendered, sitemap, canonical | `sitemap.xml`, `robots.txt`, JSON-LD | Done | – |
| NFR-11 | G3 | Logika di service + uji unit, PSR-12 | Pint dijalankan | Done | – |
| NFR-12 | – | Browser & iOS 15+ | Tailwind 3.4, target build es2019/safari14 | In Progress | Uji Chrome Android & Safari iOS |
| NFR-13 | G2 | Asia/Jakarta, tanggal hari operasional | `OpHours` | Done | T-23 |
| NFR-14 | – | Backup DB harian 7 hari | – | Not Started | Diset di cron cPanel saat deploy |
| NFR-15 | – | Konten 21+ | Catatan di Harga & checkbox LC | Done | – |

## Keputusan implementasi saat PRD dan SRS berbeda

| Topik | PRD | SRS | Diputuskan |
| --- | --- | --- | --- |
| Akses Admin Konten | Promo, event, galeri | + ruangan & paket | Ikut SRS (lebih rinci, tabel rute §6.2) |
| Dashboard | – | Untuk semua peran | Semua peran, widget sesuai hak akses |
| WA saat konfirmasi | Kirim hanya saat reservasi masuk (mitigasi blokir) | FR-WA-04 otomatis | Otomatis, dengan sakelar di Pengaturan + tombol wa.me tetap ada |
| Menu cafe di beranda prototype | Pemesanan F&B di luar cakupan | Tidak ada FR | Tidak dibuat (harga contoh berisiko selisih) |
| Nominal deposit (30 Okt 2026) | Pertanyaan terbuka | Pertanyaan terbuka #2 | Rp 500.000, berlaku untuk sewa siang dengan LC; default di `config/dmk.php`, tetap bisa diubah di Pengaturan. Ringkasan harga menulis "Deposit Rp 500.000" (bukan "Gratis"), deposit tidak masuk total |
| Isi & durasi paket (30 Okt 2026) | Pertanyaan terbuka | Pertanyaan terbuka #1, §4.4 "3 jam" | Ikut price list client: paket level 1–2 = 2 jam, level 3 / Master / Family = 3 jam. Nama paket disamakan dengan price list (mis. "Anggur DM 1", "VIP A", "Paket Vibe"); promo mengacu ke ID paket sehingga tidak terdampak. Data di `CoreSeeder::packages()` |

## Tambahan model data di luar SRS §5 (perlu dimasukkan ke SRS)

- `events`: `start_time`, `end_time`, `featured`.
- `messages`: `contact` (WA atau email), `kind`.
- `reservations`: `package_name`, `promo_note`, `source` (snapshot dan laporan "sumber online").
- `reservation_logs.note`: mencatat pindah unit/jam.
- `wa_logs`: `recipient`, `sent_at`.
- `settings`: `max_days`, `wa_display`, `wa_number`, `wa_auto_status`, `analytics_id`, `meta_pixel_id`.
- Kode reservasi `DM-DDMM-XXXX` (4 karakter acak, tidak berurutan).
