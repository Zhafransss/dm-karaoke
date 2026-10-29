# SRS – Website DM Karaoke & Cafe

## 1. Pendahuluan

Dokumen ini menetapkan kebutuhan perangkat lunak website DM Karaoke & Cafe sebagai acuan implementasi dan pengujian. Kebutuhan bisnis dan alasan produk ada di PRD yang menyertainya; prototype v3 (satu file HTML) menjadi acuan perilaku.

**Cakupan.** Website publik dan panel admin untuk reservasi ruangan, daftar harga dan paket, promo, event, galeri, kontak, dan notifikasi WhatsApp. Implementasi memakai Laravel di shared hosting.

**Istilah**

| Istilah | Arti |
| --- | --- |
| Tipe ruangan | Kategori ruangan: Standard, VIP, Master |
| Unit | Satu ruangan fisik dalam sebuah tipe (Standard 1 sampai 3, VIP 1 sampai 5, Master 1) |
| Hari operasional | Satu hari buka 14.00 sampai 04.00 keesokan hari; jam 00.00–04.00 masuk hari sebelumnya |
| Jam operasional | Jam dalam hari operasional: 14 sampai 27 (24 ke atas = lewat tengah malam) |
| Sewa saja | Reservasi ruangan per jam tanpa paket |
| Paket | Harga tetap berisi ruangan beberapa jam dan item lain |
| LC | Pemandu lagu; hanya berupa pilihan centang, tanpa tarif di sistem |
| Target promo | Sewa ruangan satu tipe, atau satu paket tertentu |

**Referensi.** PRD DM Karaoke; prototype dm-karaoke-v3.html; design system Noir & Gold.

## 2. Deskripsi umum

**Perspektif produk.** Aplikasi web Laravel tunggal dengan dua area: situs publik (server-rendered Blade agar SEO baik) dan panel admin berbasis peran. Database MySQL. Notifikasi WhatsApp lewat gateway pihak ketiga.

**Tech stack.** Laravel (PHP 8.2+), MySQL 8 atau MariaDB 10.6, Blade dengan Tailwind CSS 3.4, Alpine.js untuk interaksi, Vite untuk membangun aset di komputer pengembang (hasil `public/build` ikut diunggah), auth sesi bawaan Laravel, Intervention Image (GD) untuk WebP, driver cache dan sesi `file` atau `database`, Pest atau PHPUnit untuk pengujian. Rinciannya ada di PRD bagian 10.

**Pengguna dan peran**

| Peran | Akses |
| --- | --- |
| Pengunjung | Melihat konten, harga, promo; membuat reservasi; cek status; mengirim pesan |
| Super Admin | Semua modul, termasuk pengguna dan pengaturan |
| Admin Reservasi | Dashboard, jadwal, reservasi, blokir jadwal |
| Admin Konten | Event, galeri, promo, ruangan, paket |
| Admin Pesan | Kotak masuk pesan pengunjung |

**Lingkungan operasi.** Shared hosting Rumahweb (PHP 8.2 atau lebih baru, MySQL, cron, tanpa worker permanen). Browser modern di ponsel dan desktop; ponsel menjadi target utama.

**Batasan**

- Semua pembayaran di tempat; tidak ada payment gateway dan tidak ada DP.
- Tidak ada akun pengunjung, OTP, atau pengingat otomatis.
- Tidak ada queue worker; pekerjaan tertunda memakai `dispatchAfterResponse` dan cron.
- Status reservasi hanya diubah admin.
- Konten LC dan minuman beralkohol dibatasi usia 21 tahun ke atas dan dipromosikan secara terbatas di iklan berbayar.

**Asumsi**

- Semua paket berdurasi room 3 jam.
- Isi paket Standard dan VIP masih placeholder sampai pemilik memberi daftar final.
- Nominal deposit belum ditetapkan; tersedia pengaturan `deposit` yang bisa dikosongkan.
- Promo "bayar X dari Y jam" berlaku sekali per transaksi dan menggratiskan jam berbayar termurah.

## 3. Kebutuhan fungsional

Prioritas: **W** = wajib rilis 1, **P** = penting rilis 2, **O** = opsional.

### 3.1 Situs publik

| ID | Kebutuhan | Prio |
| --- | --- | --- |
| FR-PUB-01 | Beranda memuat hero, ringkasan ruangan dengan harga "mulai dari", promo berjalan, event terdekat, cuplikan galeri, dan tombol WhatsApp | W |
| FR-PUB-02 | Halaman Harga menampilkan tabel sewa saja (siang dengan LC dan deposit, siang tanpa, malam), paket per tipe ruangan dikelompokkan per merek, paket Family, catatan 21+, dan teks deposit | W |
| FR-PUB-03 | Halaman Promo menampilkan promo yang berjalan dan terjadwal beserta target, periode, dan jam berlaku | W |
| FR-PUB-04 | Event: daftar dan halaman detail per slug; hanya event terbit yang tampil | P |
| FR-PUB-05 | Galeri dengan kategori dan tampilan lightbox | P |
| FR-PUB-06 | Kontak: alamat, peta, jam buka, WhatsApp, Instagram, dan formulir pesan | W |
| FR-PUB-07 | Setiap halaman memuat meta title/description, Open Graph, dan JSON-LD tipe bisnis dengan koordinat | W |
| FR-PUB-08 | Kapasitas Master Room tidak ditampilkan di halaman publik mana pun | W |

### 3.2 Reservasi

| ID | Kebutuhan | Prio |
| --- | --- | --- |
| FR-RES-01 | Pengunjung memilih tipe ruangan, tanggal, mode (sewa saja atau salah satu paket), jam mulai, durasi, dan jumlah orang | W |
| FR-RES-02 | Mode default: tipe yang bisa disewa mulai di sewa saja; Master Room langsung ke daftar paket | W |
| FR-RES-03 | Grid ketersediaan per jam menampilkan jumlah unit kosong; jam lewat tengah malam berlabel "dini hari" | W |
| FR-RES-04 | Jam mulai dan durasi dibatasi: per jam penuh, minimal 1 jam, tidak melewati jam tutup, dan sesuai aturan paket (durasi tetap 3 jam, jam mulai paket Family 15.00) | W |
| FR-RES-05 | Jumlah orang dibatasi kapasitas tipe; bila kapasitas tidak ditampilkan (Master) batas teknis 30 | W |
| FR-RES-06 | Opsi "Dengan LC" berupa kotak centang; tidak memengaruhi total harga dan tidak melacak ketersediaan | W |
| FR-RES-07 | Ringkasan harga langsung dihitung dan diperbarui saat pilihan berubah, termasuk promo dan rincian jam | W |
| FR-RES-08 | Data pemesan: nama, nomor HP (format Indonesia), catatan opsional; validasi sisi server dan klien | W |
| FR-RES-09 | Saat simpan, sistem memilih unit kosong secara atomik dalam transaksi database dengan `lockForUpdate`; bila bentrok tampilkan pesan dan segarkan grid | W |
| FR-RES-10 | Harga dan promo dikunci pada saat reservasi dibuat (snapshot) | W |
| FR-RES-11 | Kode reservasi unik dibuat dan ditampilkan di halaman sukses; status awal `menunggu` | W |
| FR-RES-12 | Batas 3 reservasi per jam per IP; lewat batas, tolak dengan pesan ramah | W |
| FR-RES-13 | Halaman Cek Status: kode + nomor HP menampilkan status dan rincian; tanpa login | W |
| FR-RES-14 | Setelah reservasi dibuat, sistem mengirim WhatsApp ke pemesan dan ke admin memakai template | W |

### 3.3 Panel admin

| ID | Kebutuhan | Prio |
| --- | --- | --- |
| FR-ADM-01 | Login dengan nama pengguna dan kata sandi; sesi aman, logout, throttle percobaan login | W |
| FR-ADM-02 | Hak akses per peran sesuai tabel di bagian 2; aksi di luar peran ditolak (403) | W |
| FR-ADM-03 | Dashboard: reservasi hari ini, menunggu konfirmasi, pesan belum dibaca, promo berjalan | W |
| FR-ADM-04 | Jadwal harian: grid unit × jam, termasuk blok manual dan jam dini hari hari operasional | W |
| FR-ADM-05 | Daftar reservasi dengan filter status, tanggal, tipe, dan pencarian nama/kode/HP | W |
| FR-ADM-06 | Ubah status: menunggu, dikonfirmasi, selesai, dibatalkan, tidak datang; perubahan dicatat (siapa, kapan) | W |
| FR-ADM-07 | Reservasi baru oleh admin (walk-in atau telepon) dengan mode, paket, LC, dan harga yang sama dengan situs publik | W |
| FR-ADM-08 | Blokir jadwal per unit dan rentang jam (perawatan, acara tertutup) | W |
| FR-ADM-09 | Pindah unit atau ubah jam reservasi dengan validasi bentrok | P |
| FR-ADM-10 | CRUD tipe ruangan: nama, deskripsi, fasilitas, foto, kapasitas, tampil-kapasitas, bisa-disewa, tarif siang, tarif malam, jumlah unit | W |
| FR-ADM-11 | CRUD paket: tipe, merek, nama, harga, durasi, isi, jam mulai khusus, tanda family, aktif | W |
| FR-ADM-12 | CRUD promo: nama, deskripsi, jenis (persen, nominal, bayar X dari Y jam), target (sewa per tipe dan/atau paket tertentu), periode, jam berlaku opsional, sakelar aktif; pratinjau perhitungan | W |
| FR-ADM-13 | Validasi promo: tidak boleh ada dua promo aktif yang beririsan (periode dan jam) pada target yang sama; jenis bayar X dari Y hanya untuk sewa saja | W |
| FR-ADM-14 | CRUD event dan galeri dengan unggah gambar, kompresi, dan status terbit | P |
| FR-ADM-15 | Kotak masuk pesan: tandai dibaca, balas lewat tautan WhatsApp, hapus | P |
| FR-ADM-16 | Pengguna: tambah, ubah peran, nonaktifkan, atur ulang kata sandi (Super Admin) | W |
| FR-ADM-17 | Pengaturan: nama usaha, alamat, kontak, jam buka/tutup, batas siang-malam (`dayEnd`), nominal deposit, template WhatsApp, kredensial gateway | W |

### 3.4 Notifikasi WhatsApp

| ID | Kebutuhan | Prio |
| --- | --- | --- |
| FR-WA-01 | Template dapat diubah admin dengan variabel `{nama} {hp} {kode} {ruangan} {unit} {paket} {lc} {tanggal} {jam} {durasi} {total} {orang}` | W |
| FR-WA-02 | Pengiriman dipicu setelah respons dikirim ke pengunjung (`dispatchAfterResponse`); kegagalan tidak membatalkan reservasi | W |
| FR-WA-03 | Pengiriman gagal dicatat di tabel log dan dicoba ulang oleh cron maksimal 3 kali | W |
| FR-WA-04 | Pesan konfirmasi dikirim saat admin mengubah status menjadi dikonfirmasi; pembatalan memakai template terpisah | P |
| FR-WA-05 | Tombol "Hubungi via WhatsApp" (tautan wa.me) sebagai cadangan di halaman sukses dan kontak | W |

## 4. Aturan bisnis dan algoritma

### 4.1 Hari dan jam operasional

- Buka 14.00, tutup 04.00 (nilai `close` = 28). Bila `close <= open`, sistem menambah 24 pada `close`.
- Jam 24 sampai 27 berarti 00.00 sampai 03.00 dan milik hari operasional sebelumnya.
- "Hari ini" sebelum 04.00 dihitung sebagai kemarin.

### 4.2 Kapasitas dan unit

| Tipe | Unit | Maks. orang | Dapat disewa saja |
| --- | --- | --- | --- |
| Standard | 3 | 6 | Ya |
| VIP | 5 | 10 | Ya |
| Master | 1 | tidak ditampilkan | Tidak, hanya paket |

### 4.3 Tarif sewa saja

| Jam | Dengan LC dan deposit | Tanpa LC dan deposit |
| --- | --- | --- |
| 14.00 sampai 19.00 | Gratis | Standard 40.000/jam, VIP 60.000/jam |
| 19.00 sampai 04.00 | Standard 80.000/jam, VIP 120.000/jam (dengan atau tanpa LC) | sama |

Pseudocode `calcPrice`:

```
for each jam h in [mulai, mulai + durasi):
    rate = (h < dayEnd) ? (lc ? 0 : rateDay) : rateNight
    lines.push({jam: h, rate})
base = sum(rate)
```

Contoh: VIP 18.00 sampai 21.00 tanpa LC = 60.000 + 2 × 120.000 = **300.000**. Dengan LC = 0 + 240.000 = 240.000.

### 4.4 Paket

- Harga tetap per paket; `base = price`. Durasi = `hours` (3 jam).
- Paket Family (Fun Package 115.000 di Standard, Baddas Package 165.000 di VIP) hanya boleh mulai 15.00 (`startHour`).
- Reservasi paket menyimpan `pkgId`; LC pada paket bersifat bawaan paket dan tidak mengubah harga.

### 4.5 LC dan deposit

- LC hanya kotak centang; tidak ada tarif LC dan tidak ada pelacakan ketersediaan.
- Deposit berupa uang, dibayar di tempat. Situs hanya menampilkan informasi. Bila pengaturan `deposit` kosong, teks: "besarnya diinformasikan admin saat konfirmasi".

### 4.6 Promo

1. **Satu promo per transaksi.** `pickPromo` memilih promo yang cocok target dan tanggalnya; bila beberapa, utamakan yang jendela jamnya memuat jam mulai.
2. **Jenis.** `persen` (1 sampai 100), `nominal` (rupiah per transaksi), `bayar_x` (bayar Y dari X jam; hanya sewa saja).
3. **Target.** Kunci `rent:<typeId>` atau `pkg:<pkgId>`.
4. **Jadwal.** `dateStart` sampai `dateEnd` dan opsional `timeStart` sampai `timeEnd` (jam operasional), ditambah sakelar `active`.
5. **Jam yang didiskon.** Untuk sewa saja hanya jam di dalam jendela yang terkena diskon. Untuk paket, promo berlaku bila jam mulai berada di dalam jendela.
6. **Basis.** Diskon dihitung dari total ruangan setelah tarif siang atau malam.
7. **bayar_x.** Hanya jam berbayar (`rate > 0`) yang dihitung; butuh minimal X jam berbayar. Diskon = jumlah (X − Y) jam berbayar termurah. Jam gratis karena LC tidak dihitung, sehingga VIP 18.00 sampai 21.00 dengan LC tidak mendapat promo ini.
8. **Validasi simpan.** Tolak promo aktif bila beririsan (tanggal dan jam) dengan promo aktif lain pada target yang sama.
9. `total = max(0, base - discount)`.

### 4.7 Ketersediaan dan bentrok

- Satu unit sibuk pada jam h bila ada reservasi berstatus `menunggu` atau `dikonfirmasi`, atau blokir, yang menutup h.
- Booking per jam penuh, minimal 1 jam, tanpa jeda antar booking.
- Jam bebas tipe T = jumlah unit T yang tidak sibuk. Durasi maksimum dari jam mulai = jam berturut-turut yang masih punya minimal satu unit kosong, dibatasi jam tutup.
- Status `selesai`, `dibatalkan`, `tidak_datang` membebaskan unit (selesai dan tidak datang tetap dihitung di laporan).

### 4.8 Status reservasi

`menunggu` → `dikonfirmasi` → `selesai`; dari `menunggu` atau `dikonfirmasi` dapat ke `dibatalkan` atau `tidak_datang`. Hanya admin yang boleh mengubah status. Perpanjangan jam ditangani manual oleh admin.

## 5. Model data

MySQL 8 / MariaDB 10.6, `utf8mb4`, mesin InnoDB. Uang disimpan sebagai integer rupiah. Semua tabel memiliki `created_at` dan `updated_at` kecuali dicatat lain.

| Tabel | Kolom utama | Catatan |
| --- | --- | --- |
| `users` | id, name, username (unik), password, role, active, last_login_at | Peran: super_admin, admin_reservasi, admin_konten, admin_pesan |
| `settings` | key (unik), value (json) | open, close, dayEnd, deposit, kontak, template WA, kredensial gateway (terenkripsi) |
| `room_types` | id, name, slug, description, facilities (json), image, cap, show_cap, rentable, rate_day, rate_night, featured, sort, active | Master: rentable = 0, show_cap = 0 |
| `rooms` | id, room_type_id, name, active | Unit fisik; 3 + 5 + 1 |
| `packages` | id, room_type_id, brand, name, price, hours, items (json), family, start_hour (null), active, sort | Harga tetap |
| `reservations` | id, code (unik), room_type_id, room_id, package_id (null), lc, date, start_hour, hours, pax, name, phone, note, status, base, discount, total, promo_id (null), promo_name, price_lines (json), ip, created_by (null) | Harga dan promo disalin (snapshot) |
| `reservation_logs` | id, reservation_id, user_id, from_status, to_status, created_at | Jejak perubahan status |
| `blocks` | id, room_id, date, start_hour, end_hour, reason, user_id | Blokir jadwal |
| `promos` | id, name, description, kind, value, x, y, date_start, date_end, time_start, time_end, active | |
| `promo_targets` | promo_id, target_key | Contoh `rent:rt-vip`, `pkg:pk-vibe`; indeks pada target_key |
| `events` | id, title, slug (unik), body, image, date, published | |
| `gallery` | id, caption, category, image, sort, published | |
| `messages` | id, name, phone, message, read_at | |
| `wa_logs` | id, reservation_id (null), to, template, body, status, response, attempts, next_try_at | Dipakai cron untuk retry |
| `rate_limits` | ditangani cache Laravel (`RateLimiter`) | Kunci per IP, 3 per jam |

**Indeks penting.** `reservations (date, room_id, status)` untuk uji bentrok; `reservations (code)` unik; `reservations (phone, code)` untuk cek status; `blocks (room_id, date)`; `promo_targets (target_key)`.

**Integritas.** Kunci asing dengan `restrict` untuk riwayat reservasi; tipe, unit, dan paket yang sudah dipakai reservasi tidak dihapus, hanya dinonaktifkan. Pembuatan reservasi berjalan dalam `DB::transaction` dengan `lockForUpdate` pada baris unit-unit tipe tersebut sebelum uji bentrok.

**Seed awal.** Tiga tipe, sembilan unit, paket dari daftar harga (termasuk Fun Package dan Baddas Package), empat promo contoh, pengguna Super Admin.

## 6. Antarmuka dan rute

### 6.1 Rute publik

| Rute | Metode | Fungsi |
| --- | --- | --- |
| `/` | GET | Beranda |
| `/harga` | GET | Daftar harga dan paket |
| `/reservasi` | GET | Formulir reservasi; parameter opsional `?tipe=` dan `?paket=` |
| `/reservasi/ketersediaan` | GET (JSON) | Grid jam kosong per tipe, tanggal, dan durasi |
| `/reservasi/harga` | POST (JSON) | Hitung harga (mode, paket, jam, durasi, LC) memakai kelas yang sama dengan penyimpanan |
| `/reservasi` | POST | Simpan reservasi (throttle 3 per jam per IP) |
| `/reservasi/{kode}/sukses` | GET | Halaman sukses |
| `/cek-status` | GET, POST | Cek status dengan kode dan nomor HP |
| `/promo` | GET | Promo berjalan dan terjadwal |
| `/event`, `/event/{slug}` | GET | Daftar dan detail event |
| `/galeri` | GET | Galeri |
| `/kontak` | GET, POST | Info kontak dan kirim pesan |
| `/sitemap.xml`, `/robots.txt` | GET | SEO |

### 6.2 Rute admin (prefiks `/admin`, middleware `auth` dan peran)

| Rute | Peran | Fungsi |
| --- | --- | --- |
| `/admin/login`, `/admin/logout` | publik / sesi | Autentikasi |
| `/admin` | semua | Dashboard |
| `/admin/jadwal` | Reservasi | Grid harian dan blokir |
| `/admin/reservasi` (+ `/baru`, `/{id}`, `/{id}/status`) | Reservasi | CRUD dan ubah status |
| `/admin/ruangan`, `/admin/paket` | Konten | Tipe, unit, dan paket |
| `/admin/promo` | Konten | CRUD promo |
| `/admin/event`, `/admin/galeri` | Konten | Konten |
| `/admin/pesan` | Pesan | Kotak masuk |
| `/admin/pengguna`, `/admin/pengaturan` | Super Admin | Pengguna dan pengaturan |

### 6.3 Layanan internal

- `PricingService::calculate(typeId, pkgId, date, hour, hours, lc, ?promo)` mengembalikan `{mode, lines[], base, promoId, promoName, promoNote, discount, total}`; dipakai oleh endpoint harga, penyimpanan, dan pratinjau promo admin sehingga angkanya selalu sama.
- `AvailabilityService` (jam bebas, unit bebas, durasi maksimum), `ReservationService::create` (transaksi + lock), `PromoService` (pilih promo dan cek bentrok), `WhatsAppService` (kirim, log, retry).

### 6.4 Antarmuka eksternal

- **Gateway WhatsApp** (Fonnte, Wablas, StarSender, atau Watzap; pilihan akhir menyusul) lewat HTTP API dengan token di pengaturan. Antarmuka dibungkus kontrak `WhatsAppGateway` agar penyedia bisa diganti.
- **Peta**: tautan atau embed Google Maps. **Analitik** dan Meta Pixel: opsional, dimuat bila diaktifkan di pengaturan.

### 6.5 Antarmuka pengguna

Mengikuti design system Noir & Gold dan prototype v3, dibangun dengan Blade, Tailwind CSS 3.4 (token warna, tipografi, jarak, dan radius di `tailwind.config.js`), dan Alpine.js untuk form reservasi, grid jam, modal, serta lightbox. Kontras teks memenuhi WCAG AA, target sentuh minimal 44 px, dan layout responsif dari lebar 360 px.

## 7. Kebutuhan non-fungsional

| ID | Kategori | Kebutuhan |
| --- | --- | --- |
| NFR-01 | Kinerja | Halaman publik LCP kurang dari 2,5 detik di jaringan 4G; HTML dari server, gambar WebP dengan `loading="lazy"` dan ukuran responsif |
| NFR-02 | Kinerja | Endpoint ketersediaan dan harga merespons kurang dari 500 ms pada beban normal |
| NFR-03 | Konkurensi | Dua reservasi bersamaan pada jam dan tipe yang sama tidak boleh menempati unit yang sama (transaksi + `lockForUpdate`) |
| NFR-04 | Keamanan | HTTPS wajib; kata sandi di-hash bcrypt/argon2; CSRF aktif; validasi server; escaping keluaran; throttle login dan reservasi |
| NFR-05 | Keamanan | Unggahan dibatasi jenis dan ukuran; nama file diacak; kredensial gateway disimpan terenkripsi |
| NFR-06 | Privasi | Nama dan nomor HP hanya terlihat oleh admin berwenang dan pemesan lewat cek status; tidak ada data kartu atau pembayaran |
| NFR-07 | Ketersediaan | Target 99% per bulan sesuai paket hosting; gagal kirim WhatsApp tidak mengganggu reservasi |
| NFR-08 | Keterpakaian | Alur reservasi selesai dalam 4 langkah atau kurang di ponsel; pesan galat dalam bahasa Indonesia yang jelas |
| NFR-09 | Aksesibilitas | WCAG 2.1 AA: label formulir, fokus terlihat, navigasi keyboard, teks alternatif |
| NFR-10 | SEO | Server-rendered, judul dan deskripsi unik per halaman, JSON-LD LocalBusiness dengan geo, sitemap, kanonikal |
| NFR-11 | Pemeliharaan | Aturan harga dan ketersediaan berada di kelas layanan dengan uji unit; kode mengikuti PSR-12 |
| NFR-12 | Kompatibilitas | Dua versi utama terakhir Chrome, Safari, Firefox, Edge; Android 9 ke atas dan iOS 15 ke atas |
| NFR-13 | Zona waktu | Seluruh logika memakai `Asia/Jakarta`; tanggal disimpan sebagai tanggal hari operasional |
| NFR-14 | Pencadangan | Cadangan database harian lewat cron hosting, simpan minimal 7 hari |
| NFR-15 | Kepatuhan | Konten 21+ diberi catatan jelas; materi iklan berbayar tidak memuat LC atau alkohol (kebijakan Meta) |

## 8. Deployment dan operasional

**Lingkungan.** Staging di dmkaraoke.veloradigitalworks.com; produksi di domain DM Karaoke pada Rumahweb. PHP 8.2+, ekstensi standar Laravel, MySQL, SSL aktif.

**Struktur di shared hosting.** Folder aplikasi di luar `public_html`; isi `public/` ditautkan ke `public_html` (atau document root diarahkan ke `public`). `.env` tidak dapat diakses publik. `storage` dan `bootstrap/cache` dapat ditulis. Jalankan `php artisan storage:link` bila diizinkan, atau simpan unggahan di `public/uploads`.

**Cron**

| Jadwal | Tugas |
| --- | --- |
| Setiap menit | `php artisan schedule:run` |
| Setiap 5 menit (via scheduler) | Ulangi pengiriman WhatsApp gagal (maks. 3 percobaan) |
| Harian 02.00 WIB | Cadangan database dan bersihkan log lama |

**Rilis.** Aset dibangun di komputer pengembang (`npm run build`) dan folder `public/build` ikut diunggah, karena hosting tanpa Node. Kode lewat Git atau unggah ZIP; langkah di server: `composer install --no-dev -o`, `php artisan migrate --force`, `config:cache`, `route:cache`, `view:cache`. Rilis dilakukan di luar jam ramai (bukan sekitar 19.00 sampai 02.00).

**Pemantauan.** Log Laravel harian, tabel `wa_logs`, dan pemantau uptime eksternal pada beranda.

**Risiko hosting.** Tidak ada queue worker permanen dan batas proses PHP; karena itu pengiriman WhatsApp dipicu setelah respons dan diulang lewat cron.

## 9. Kriteria penerimaan dan pengujian

Uji unit untuk `PricingService` memakai kasus berikut; semuanya harus lulus.

| No | Skenario | Hasil yang diharapkan |
| --- | --- | --- |
| T-01 | VIP, sewa saja, 15.00 sampai 17.00, tanpa LC | 2 × 60.000 = 120.000 |
| T-02 | VIP, sewa saja, 15.00 sampai 17.00, dengan LC | 0 (gratis) |
| T-03 | VIP, sewa saja, 18.00 sampai 21.00, tanpa LC | 60.000 + 2 × 120.000 = 300.000 |
| T-04 | VIP, sewa saja, 18.00 sampai 21.00, dengan LC | 240.000 |
| T-05 | Standard, sewa saja, 20.00 sampai 22.00, dengan atau tanpa LC | 2 × 80.000 = 160.000 |
| T-06 | Standard, sewa saja, 23.00 sampai 02.00 (melewati tengah malam) | 3 × 80.000 = 240.000, jam 24 dan 25 berlabel dini hari |
| T-07 | Paket Family Fun Package, mulai 15.00 | 115.000 |
| T-08 | Paket Family mulai 14.00 atau 16.00 | Ditolak: hanya boleh mulai 15.00 |
| T-09 | Master Room tanpa paket | Ditolak: hanya tersedia paket |
| T-10 | Promo 10% berlaku pada target dan jam mulai | Diskon 10% dari total ruangan setelah tarif siang atau malam |
| T-11 | Promo bayar 3 dari 2 jam, VIP 20.00 sampai 23.00 tanpa LC | 360.000 − 120.000 = 240.000 |
| T-12 | Promo bayar 3 dari 2 jam, VIP 18.00 sampai 21.00 dengan LC | Tidak berlaku (hanya 2 jam berbayar); total 240.000 |
| T-13 | Dua promo berpotensi cocok | Hanya satu yang dipakai; yang jendela jamnya memuat jam mulai diutamakan |
| T-14 | Simpan promo aktif yang beririsan pada target yang sama | Ditolak dengan daftar promo yang bentrok |
| T-15 | Tanggal kunjungan di luar periode promo | Promo tidak berlaku |

**Uji fungsional dan integrasi**

| No | Skenario | Hasil yang diharapkan |
| --- | --- | --- |
| T-20 | Dua pengunjung memesan VIP jam yang sama saat unit tersisa satu | Satu berhasil, satu mendapat pesan bentrok dan grid dimuat ulang |
| T-21 | Semua unit Standard terisi di satu jam | Jam tersebut penuh di grid; durasi dibatasi sebelum jam itu |
| T-22 | Reservasi ke-4 dari IP yang sama dalam satu jam | Ditolak dengan pesan batas |
| T-23 | Reservasi dibuat sesudah tengah malam (misalnya 01.00) | Tersimpan pada hari operasional sebelumnya |
| T-24 | Admin mengubah status ke dibatalkan | Unit bebas kembali dan perubahan tercatat |
| T-25 | Admin Konten membuka `/admin/pengguna` | Ditolak (403) |
| T-26 | Gateway WhatsApp gagal | Reservasi tetap tersimpan; log gagal dibuat; cron mengulang hingga 3 kali |
| T-27 | Cek status dengan kode benar dan HP salah | Tidak menampilkan data |
| T-28 | Harga di ringkasan sama dengan harga tersimpan di reservasi | Identik (satu `PricingService`) |
| T-29 | Admin mengubah tarif setelah reservasi dibuat | Reservasi lama tidak berubah |
| T-30 | Master Room di halaman publik | Tidak ada tulisan kapasitas |

**Kriteria rilis.** Semua kebutuhan berprioritas W terpenuhi; semua uji T-01 sampai T-30 lulus; Lighthouse ponsel di atas 85 untuk kinerja, 95 untuk SEO; tidak ada galat konsol; uji di Chrome Android dan Safari iOS.

**Pertanyaan terbuka yang memengaruhi dokumen ini**

1. Isi final paket Standard dan VIP (kini placeholder).
2. Nominal deposit.
3. Konfirmasi semantik promo bayar X dari Y (sekali per transaksi, jam berbayar termurah, LC tidak dihitung).
4. Pilihan gateway WhatsApp.
