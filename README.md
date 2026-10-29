# DM Karaoke & Cafe – Website & Panel Admin

Laravel 12 (PHP 8.2+), MySQL/MariaDB, Blade + Tailwind CSS 3.4 + Alpine.js. Acuan: `docs/PRD-DM-Karaoke.md`, `docs/SRS-DM-Karaoke.md`, status pengerjaan di `docs/RTM.md`. Prototype lama ada di `docs/prototype/`.

## Menjalankan di lokal (XAMPP)

```bash
composer install
npm install
cp .env.example .env && php artisan key:generate   # sesuaikan DB_* (default: dm_karaoke, root tanpa sandi)
php artisan migrate:fresh --seed                    # data inti + data demo (bukan di production)
npm run build                                       # atau `npm run dev` saat mengubah tampilan
php artisan serve                                   # http://127.0.0.1:8000
```

Akun demo (hanya dibuat di luar production): `admin / dmkaraoke` (Super Admin), `kasir / kasir123` (Admin Reservasi), `dewi / konten123` (Admin Konten), `bayu / pesan123` (Admin Pesan, nonaktif). Di production sandi Super Admin diambil dari `SEED_ADMIN_PASSWORD`.

Pengujian memakai database `dm_karaoke_test` (lihat `phpunit.xml`): `php artisan test`.

## Struktur penting

| Lokasi | Isi |
| --- | --- |
| `app/Services/PricingService.php` | Satu-satunya perhitungan harga (siang/malam, paket, promo) |
| `app/Services/AvailabilityService.php` | Jam kosong, durasi maksimum, aturan booking |
| `app/Services/ReservationService.php` | Simpan reservasi dalam transaksi + `lockForUpdate`, ubah status, pindah jadwal |
| `app/Services/PromoService.php` | Pilih satu promo per transaksi, cek bentrok promo |
| `app/Services/WhatsApp/` | Kontrak gateway, Fonnte, mode simulasi, log + retry |
| `app/Support/OpHours.php` | Hari operasional 14.00–04.00 (jam 24–27 = dini hari) |
| `config/dmk.php` | Peran & hak akses, status, kategori, batas anti-spam, default pengaturan |

## Deploy ke shared hosting (Rumahweb)

1. Di komputer pengembang: `composer install --no-dev -o` dan `npm run build`.
2. Unggah seluruh folder ke luar `public_html` (misal `~/dmkaraoke`), lalu arahkan document root ke `~/dmkaraoke/public` atau salin isi `public/` ke `public_html` dan sesuaikan path di `index.php`.
3. Buat `.env` produksi: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://…`, kredensial DB, `SEED_ADMIN_PASSWORD=…`.
4. `php artisan key:generate`, `php artisan migrate --force`, `php artisan db:seed --class=CoreSeeder --force` (sekali saja).
5. `php artisan config:cache && php artisan route:cache && php artisan view:cache`.
6. Pastikan `storage/`, `bootstrap/cache/`, dan `public/uploads/` bisa ditulis. Unggahan disimpan di `public/uploads` sehingga tidak butuh `storage:link`.
7. Cron cPanel:
   - Setiap menit: `php /home/USER/dmkaraoke/artisan schedule:run >> /dev/null 2>&1` (retry WhatsApp tiap 5 menit, bersihkan log 02.00).
   - Harian 02.00: backup database via `mysqldump` atau fitur backup cPanel, simpan minimal 7 hari (NFR-14).
8. Bila situs berada di belakang Cloudflare/proxy, aktifkan `trustProxies` di `bootstrap/app.php` hanya untuk IP proxy tersebut agar batas 3 reservasi/jam per IP membaca IP asli tamu.

Lakukan rilis di luar jam ramai (bukan 19.00–02.00).
