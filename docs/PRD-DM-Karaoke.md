# PRD – Website DM Karaoke & Cafe

## 1. Ringkasan produk

Website DM Karaoke & Cafe (Jl. Raya Margasana, Menganti, Rawalo, Banyumas) membuat tamu bisa melihat harga, mengecek jam ruangan yang kosong, dan reservasi sendiri tanpa harus chat admin satu per satu. Admin mengelola reservasi, harga, paket, promo, event, dan galeri dari satu panel.

| Tujuan | Ukuran |
| --- | --- |
| Reservasi masuk lewat website, bukan hanya chat WhatsApp | Porsi reservasi online terhadap total reservasi |
| Tidak ada dua booking di ruangan dan jam yang sama | 0 bentrok jadwal |
| Harga, paket, dan promo selalu konsisten dengan price list | 0 selisih harga antara website dan kasir |
| Bisnis mudah ditemukan di pencarian lokal | Muncul di pencarian "karaoke Rawalo / Banyumas" |

Identitas visual: hitam dan gold yang elegan (design system Noir & Gold). Nomor WhatsApp bisnis 0822-2041-3723, Instagram @DmKaraokeofficial.

## 2. Latar belakang dan masalah

Saat ini reservasi dan tanya harga dilakukan lewat chat WhatsApp. Pola ini memunculkan beberapa masalah yang ingin diselesaikan website:

- Jadwal dicek manual, sehingga risiko dua tamu mendapat ruangan yang sama di jam yang sama.
- Price list (paket, tarif siang dan malam, syarat LC dan deposit) harus dijelaskan berulang ke setiap calon tamu.
- Promo tidak punya tempat tampil yang jelas dan sulit dijadwalkan atau dihentikan tepat waktu.
- Bisnis belum punya halaman yang bisa ditemukan di Google maupun dipakai sebagai tujuan iklan.

Website ini dibangun sebagai prototype HTML dahulu (v1 sampai v3), lalu diimplementasi di Laravel pada shared hosting (Rumahweb). Prototype aktif di dmkaraoke.veloradigitalworks.com.

## 3. Pengguna dan peran

| Pengguna | Kebutuhan utama | Akses |
| --- | --- | --- |
| Tamu (publik) | Lihat harga dan paket, cek jam kosong, reservasi, cek status, baca promo dan event, kirim pesan | Tanpa login |
| Super Admin | Semua fitur, termasuk ruangan, harga, pengguna, dan pengaturan | Login |
| Admin Reservasi | Dashboard, jadwal, konfirmasi dan ubah status reservasi, tambah reservasi walk-in | Login |
| Admin Konten | Promo, event, dan galeri | Login |
| Admin Pesan | Membaca dan mengelola pesan dari tamu | Login |

Persona tamu utama: kelompok teman, keluarga, atau rekan kantor di sekitar Banyumas yang ingin memastikan ruangan dan harga sebelum datang. Pemesan memakai ponsel.

## 4. Ruang lingkup

**Termasuk dalam rilis ini**

- Halaman publik: Beranda, Harga, Reservasi, Cek Status, Promo, Event (daftar dan detail), Galeri, Kontak.
- Reservasi berbasis jadwal per unit ruangan dengan pencegahan bentrok.
- Notifikasi WhatsApp otomatis ke admin dan tamu saat reservasi masuk, lewat gateway pihak ketiga.
- Panel admin: Dashboard, Jadwal, Reservasi, Promo, Event, Galeri, Pesan, Ruangan dan Harga, Paket, Pengguna, Pengaturan.
- SEO dasar: meta, Open Graph, data terstruktur lokal bisnis, sitemap.

**Di luar cakupan**

- Pembayaran online, DP, dan pengecekan deposit di sistem. Semua dibayar di tempat.
- Pencatatan ketersediaan atau tarif LC. LC hanya berupa pilihan centang pada reservasi.
- Penambahan jam saat sedang bermain: dilakukan manual bersama admin.
- OTP, pengingat otomatis, dan antrean (waiting list). Status reservasi hanya berubah oleh admin.
- Pemesanan makanan dan minuman cafe, kasir (POS), dan aplikasi mobile.

## 5. Kebutuhan produk per fitur

| Fitur | Yang dibutuhkan pengguna | Prioritas |
| --- | --- | --- |
| Daftar harga | Tarif sewa siang dan malam, semua paket per ruangan, Paket Family, catatan 21+, info LC dan deposit | Wajib |
| Cek jadwal | Grid jam per ruangan sampai jam 04.00, jumlah ruang kosong per jam, penanda jam promo | Wajib |
| Reservasi | Pilih tanggal, ruangan, paket atau sewa saja, jam, durasi, centang LC, data tamu, ringkasan harga sebelum kirim | Wajib |
| Anti-bentrok | Jam yang sudah dipesan atau ditutup tidak bisa dipilih lagi | Wajib |
| Anti-spam | Maksimal 3 reservasi per jam dari IP yang sama | Wajib |
| Notifikasi WhatsApp | Ke admin dan ke tamu saat reservasi masuk; template bisa diubah admin | Wajib |
| Cek status | Tamu cek status dengan kode reservasi dan nomor WhatsApp | Wajib |
| Promo | Persen, nominal per transaksi, dan sewa X jam bayar Y jam; target sewa ruangan atau paket tertentu; terjadwal dan sakelar manual | Wajib |
| Event | Daftar dan halaman detail event, CMS event, status draf dan terbit | Wajib |
| Galeri | Galeri foto dengan kategori dan lightbox, CMS unggah dan terbitkan | Wajib |
| Kontak | Peta, WhatsApp, tentang kami, form pesan dan saran | Wajib |
| Admin jadwal | Timeline per unit, tambah reservasi walk-in, tutup jam untuk perawatan | Wajib |
| CMS ruangan dan paket | Tarif siang dan malam, kapasitas, jumlah unit, harga dan isi paket | Wajib |
| Pengaturan | Jam buka, batas tarif siang, deposit, gateway WhatsApp, template pesan | Wajib |
| Pengguna dan peran | Empat peran dengan hak akses berbeda | Wajib |

## 6. Aturan bisnis

**Ruangan dan jam buka.** Buka setiap hari 14.00–04.00 (melewati tengah malam). Standard Room 3 unit, maksimal 6 orang. VIP Room 5 unit, maksimal 10 orang. Master Room 1 unit, hanya dalam paket, kapasitas tidak ditampilkan.

**Sewa ruangan saja (per jam).** Booking per jam bulat, minimal 1 jam, tanpa jeda antar booking.

| Waktu | Kondisi | Standard | VIP |
| --- | --- | --- | --- |
| 14.00–19.00 | Dengan LC dan deposit | Gratis | Gratis |
| 14.00–19.00 | Tanpa LC dan deposit | Rp 40.000/jam | Rp 60.000/jam |
| 19.00–04.00 | Dengan atau tanpa LC | Rp 80.000/jam | Rp 120.000/jam |

Booking yang melewati jam 19.00 dihitung per jam sesuai tarif jamnya. Contoh VIP 18.00–21.00 tanpa LC: 60.000 + 2 × 120.000 = Rp 300.000.

**Paket.** Harga tetap sesuai price list, tidak mengikuti tarif siang atau malam. Durasi room mengikuti paket (3 jam). Paket Family hanya mulai pukul 15.00 selama 3 jam: Fun Package (Standard, Rp 115.000) dan Baddas Package (VIP, Rp 165.000). Master Room memuat 3 LC, 3 Le Minerale, 2 Hexos, food platter, dan tissue.

**LC dan deposit.** LC tidak wajib dan hanya berupa pilihan centang; sistem tidak menghitung biaya LC. Deposit berupa uang, ditampilkan sebagai informasi di website, dan dibayar di tempat. Layanan LC dan paket beralkohol ditandai khusus usia 21+.

**Promo.**

- Satu promo per transaksi.
- Jenis: persen, nominal per transaksi, dan sewa X jam bayar Y jam (hanya sewa ruangan, contoh 3 jam bayar 2 jam).
- Target: sewa ruangan per tipe atau paket tertentu. Promo bisa terjadwal (rentang tanggal, jam opsional) dan diaktifkan atau dinonaktifkan manual.
- Pada target yang sama, tidak boleh ada dua promo aktif dengan periode dan jam yang beririsan; sistem menolak saat disimpan atau diaktifkan.
- Hanya jam di dalam rentang promo yang mendapat potongan. Untuk paket, promo berlaku bila jam mulai masuk rentang.
- Promo dihitung dari total sewa setelah tarif siang atau malam diterapkan. Jam gratis karena LC tidak dihitung sebagai jam sewa untuk promo bayar X jam.

**Pembayaran.** Semua dibayar di tempat. Harga dikunci saat reservasi dibuat, sehingga perubahan harga berikutnya tidak memengaruhi reservasi lama.

## 7. Metrik keberhasilan

Target angka belum ditetapkan pemilik; kolom target diisi setelah ada data awal.

| Metrik | Cara ukur | Target |
| --- | --- | --- |
| Reservasi online per minggu | Jumlah reservasi sumber "online" di database | Tentukan setelah 4 minggu data |
| Tingkat konfirmasi | Reservasi dikonfirmasi dibagi reservasi masuk | Tentukan |
| Tingkat tidak datang | Status "tidak datang" dibagi reservasi dikonfirmasi | Tentukan |
| Bentrok jadwal | Jumlah laporan dua tamu di slot sama | 0 |
| Kunjungan organik | Klik dari pencarian Google ke website | Tentukan |
| Penggunaan promo | Reservasi yang memakai promo dibagi total | Tentukan |

## 8. Asumsi, risiko, dan pertanyaan terbuka

**Asumsi**

- Hosting tetap shared hosting (Rumahweb), tanpa proses latar permanen; antrean pesan memakai cron.
- Gateway WhatsApp pihak ketiga dipakai dengan nomor khusus, bukan nomor utama bisnis.
- Isi minuman dan makanan paket Standard dan VIP dilengkapi pemilik sebelum rilis (di prototype masih sementara).

**Risiko**

| Risiko | Dampak | Mitigasi |
| --- | --- | --- |
| Nomor WhatsApp gateway diblokir | Notifikasi tidak terkirim | Nomor khusus, kirim hanya saat reservasi masuk, tombol WA manual sebagai cadangan |
| Dua tamu memesan slot sama bersamaan | Bentrok jadwal | Transaksi database dan penguncian baris sebelum cek bentrok |
| Spam reservasi | Jadwal terkunci palsu | Batas 3 per jam per IP; admin bisa membatalkan |
| Kebijakan iklan Meta untuk konten alkohol dan LC | Iklan ditolak | Iklankan ruangan dan Paket Family; konten 21+ hanya di halaman harga |
| Admin lupa mengkonfirmasi | Tamu menunggu lama | Notifikasi WhatsApp ke admin dan penanda jumlah menunggu di panel |

**Pertanyaan terbuka**

- [ ] Berapa nominal deposit yang ditampilkan di website?
- [ ] Isi lengkap setiap paket Standard dan VIP (minuman, makanan, durasi).
- [ ] Gateway WhatsApp mana yang dipakai (Fonnte, Wablas, StarSender, Watzap)?
- [ ] Target angka untuk metrik keberhasilan.
- [ ] Kebijakan pembatalan atau keterlambatan tamu (batas waktu tunggu).

## 9. Rencana rilis

Rilis dibagi tiga tahap agar reservasi bisa dipakai lebih cepat. Tanggal belum ditetapkan.

| Tahap | Isi | Syarat selesai |
| --- | --- | --- |
| 1. Fondasi | Halaman publik, galeri, kontak, login admin, pengguna, pesan, SEO dasar | Semua halaman tampil benar di ponsel dan desktop |
| 2. Reservasi | Jadwal, reservasi, anti-bentrok, anti-spam, cek status, notifikasi WhatsApp, CMS ruangan dan paket, halaman harga | Uji bentrok dan perhitungan harga lolos |
| 3. Pemasaran | Promo, event, penyempurnaan SEO, pelacakan kunjungan | Promo bisa dijadwalkan tanpa bentrok |

Prototype v3 sudah mencakup perilaku ketiga tahap sebagai acuan; implementasi Laravel mengikuti urutan di atas.

## 10. Tech stack

Pilihan mengikuti keputusan pemilik (Laravel, MySQL, Tailwind CSS) dan perilaku prototype v3, serta batas shared hosting Rumahweb: tanpa proses latar permanen dan tanpa Node di server.

| Lapisan | Pilihan | Alasan |
| --- | --- | --- |
| Backend | Laravel (versi stabil terbaru saat pengerjaan dimulai, dikunci di `composer.lock`), PHP 8.2 atau lebih baru | Pilihan pemilik; routing, validasi, auth, scheduler, dan rate limiter sudah tersedia |
| Database | MySQL 8 (atau MariaDB 10.6 bila itu yang disediakan hosting), `utf8mb4`, InnoDB | Pilihan pemilik; transaksi dan `lockForUpdate` dipakai untuk anti-bentrok |
| Tampilan | Blade (server-rendered) + Tailwind CSS 3.4 | Pilihan pemilik; HTML dari server baik untuk SEO lokal. Tailwind 3.4 dipilih agar sesuai target browser iOS 15; Tailwind 4 baru butuh Safari 16.4 ke atas |
| Desain | Token Noir & Gold dipindah ke `tailwind.config.js` (warna, tipografi, jarak, radius) | Tampilan prototype v3 tetap sama tanpa menyalin CSS lama |
| Interaksi | Alpine.js untuk form reservasi, grid jam, modal, dan lightbox; `fetch` ke endpoint JSON untuk ketersediaan dan harga | Ringan, tanpa langkah build tambahan; menggantikan JavaScript prototype |
| Build aset | Vite, dijalankan di komputer pengembang; hasil `public/build` ikut diunggah | Shared hosting tidak menjalankan Node |
| Autentikasi dan peran | Auth sesi bawaan Laravel, empat peran lewat kolom `role`, middleware, dan Gate | Cukup untuk panel admin; tanpa paket tambahan |
| Gambar | Intervention Image (GD) mengubah unggahan menjadi WebP berukuran responsif | Galeri dan foto ruangan tetap ringan |
| Notifikasi | HTTP client Laravel ke gateway WhatsApp, dibungkus kontrak `WhatsAppGateway`; `dispatchAfterResponse` dan scheduler untuk percobaan ulang | Tanpa queue worker permanen |
| Cache, sesi, rate limit | Driver `file` atau `database` | Tanpa Redis di shared hosting |
| SEO | Meta, Open Graph, JSON-LD, dan sitemap dibuat dari Blade | Mendukung pencarian lokal |
| Font dan ikon | Font di-host sendiri, ikon SVG inline | Tanpa permintaan ke pihak luar saat memuat halaman |
| Pengujian | Pest atau PHPUnit untuk `PricingService`, ketersediaan, dan anti-bentrok | Aturan harga dan jadwal harus terbukti benar |
| Pengembangan | Git, Composer, PSR-12 (Laravel Pint) | Rilis dan pemeliharaan yang rapi |

**Pemetaan dari prototype v3.** Router `#hash` menjadi rute Laravel. Penyimpanan `localStorage` menjadi tabel MySQL. Fungsi `calcPrice` menjadi `PricingService` yang dipakai oleh halaman publik, penyimpanan reservasi, dan pratinjau promo di admin. Halaman yang dibuat dengan string JavaScript menjadi komponen Blade.

**Hal yang perlu dipastikan sebelum mulai.** Versi PHP dan jenis database yang tersedia di paket Rumahweb, serta apakah `exec` dan `symlink` diizinkan (memengaruhi `storage:link`).
