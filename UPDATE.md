# Panduan Update Development dan Production

Dokumen ini digunakan untuk memperbarui LMS ke branch:

```text
dev/course-bundle-coupon
```

Branch ini mencakup taxonomy course, refund, coupon, Bundle, Learning Path, dan pembaruan halaman publik. Commit rilis terbaru saat dokumen ini dibuat adalah `28a17e3`.

## Catatan Penting

- Backup database production sebelum menjalankan migration.
- Jangan menjalankan `php artisan migrate:fresh` di database yang sudah berisi data.
- Jangan menjalankan seeder umum dengan `--seed` di production.
- Pastikan worktree bersih sebelum berpindah branch atau pull: `git status`.
- File `.env` tidak boleh ditimpa atau dimasukkan ke Git.
- Coupon tetap nonaktif jika `COUPONS_FEATURE_ENABLED=false`.

## Update Development

### Pertama Kali Mengambil Branch

```bash
git fetch origin
git switch --track origin/dev/course-bundle-coupon
```

Jika branch lokal sudah pernah dibuat:

```bash
git switch dev/course-bundle-coupon
git pull --ff-only origin dev/course-bundle-coupon
```

### Perbarui Dependency dan Database

```bash
composer install
npm install
php artisan migrate
php artisan db:seed --class=RolesAndPermissionsSeeder
php artisan optimize:clear
npm run build
```

Pada Windows, jika `npm` tidak dapat dijalankan langsung:

```powershell
npm.cmd install
npm.cmd run build
```

Untuk menjalankan aplikasi development:

```bash
composer dev
```

### Verifikasi Development

```bash
php artisan migrate:status
php artisan test
```

Test chat membutuhkan Laravel Reverb di port yang dikonfigurasi. Jalankan Reverb jika test chat gagal karena koneksi WebSocket:

```bash
php artisan reverb:start
```

## Update Production

### 1. Backup dan Periksa Server

```bash
git status
php artisan migrate:status
```

Backup MySQL sebelum deploy:

```bash
mysqldump -u <DB_USERNAME> -p <DB_DATABASE> > backup-sebelum-course-bundle-coupon-$(date +%F-%H%M).sql
```

Pastikan backup berhasil dibuat dan dapat dibaca sebelum melanjutkan.

### 2. Aktifkan Maintenance Mode

```bash
php artisan down
```

### 3. Ambil Branch

Jika server belum memiliki branch lokal:

```bash
git fetch origin
git switch --track origin/dev/course-bundle-coupon
```

Jika server sudah menggunakan branch tersebut:

```bash
git fetch origin
git switch dev/course-bundle-coupon
git pull --ff-only origin dev/course-bundle-coupon
```

Untuk deployment production jangka panjang, opsi yang lebih rapi adalah merge branch ini ke `main` melalui Pull Request, lalu server menjalankan:

```bash
git switch main
git pull --ff-only origin main
```

Jangan menjalankan kedua skenario branch dalam deployment yang sama. Pilih branch langsung atau `main` hasil merge.

### 4. Install Dependency

```bash
composer install --no-dev --optimize-autoloader --no-interaction
npm ci
npm run build
```

### 5. Jalankan Migration dan Permission Seeder

```bash
php artisan migrate --force
php artisan db:seed --class=RolesAndPermissionsSeeder --force
```

Migration terkait yang akan dijalankan jika belum tersedia:

- `2026_10_01_000000_create_course_taxonomy_tables.php`
- `2026_10_02_000000_create_refunds_and_track_enrollment_source.php`
- `2026_10_03_000000_create_refund_settings_table.php`
- `2026_10_03_100000_create_coupons_and_coupon_redemptions.php`
- `2026_10_03_110000_create_bundles_and_extend_orders.php`
- `2026_10_05_000000_create_learning_paths_tables.php`

`RolesAndPermissionsSeeder` bersifat idempotent dan diperlukan untuk membuat permission `manage coupons`, `manage bundles`, `manage learning paths`, dan `manage course taxonomy` yang belum ada.

### 6. Periksa Environment

Pastikan konfigurasi pembayaran production tetap benar dan rahasia tidak ditulis ke repository:

```dotenv
MIDTRANS_IS_PRODUCTION=true
MIDTRANS_SERVER_KEY=<server-key-production>
MIDTRANS_CLIENT_KEY=<client-key-production>
```

Aktifkan fitur coupon hanya setelah konfigurasi dan coupon di panel admin siap:

```dotenv
COUPONS_FEATURE_ENABLED=true
```

Jika coupon belum siap dirilis:

```dotenv
COUPONS_FEATURE_ENABLED=false
```

### 7. Bangun Ulang Cache dan Restart Process

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
```

Restart worker queue dan Reverb melalui Supervisor atau process manager yang digunakan server. Contoh nama process harus disesuaikan dengan konfigurasi server:

```bash
sudo supervisorctl restart <queue-worker-name>
sudo supervisorctl restart <reverb-name>
```

### 8. Buka Kembali Aplikasi

```bash
php artisan up
```

### 9. Verifikasi Production

```bash
git branch --show-current
git log -1 --oneline
php artisan migrate:status
php artisan about
```

Periksa secara manual:

- `/` menampilkan landing page baru.
- `/katalog` menampilkan course dan filter/sorting bekerja.
- `/bundles` menampilkan Bundle aktif.
- `/learning-paths` menampilkan Learning Path aktif.
- Login admin dapat membuka pengelolaan coupon, Bundle, taxonomy, dan Learning Path.
- Checkout course dan Bundle dapat membuka halaman pemilihan metode pembayaran.
- Bundle atau Learning Path AVPN tidak terlihat oleh akun yang belum disetujui.
- Queue worker dan Reverb berjalan tanpa error.

## Jika Pull Ditolak

Jika `git pull --ff-only` gagal, hentikan deployment dan periksa:

```bash
git status
git branch --show-current
git log --oneline --decorate -10
```

Jangan menggunakan `git reset --hard` atau menghapus perubahan server sebelum memastikan perubahan tersebut memang aman dibuang.

## Pemulihan Darurat

Jika aplikasi error setelah deploy:

```bash
php artisan down
php artisan optimize:clear
```

Periksa log:

```bash
tail -n 200 storage/logs/laravel.log
```

Untuk rollback kode, checkout commit stabil sebelumnya sesuai prosedur tim. Jangan menjalankan `migrate:rollback` di production tanpa meninjau dampaknya terhadap data coupon, Bundle, order, refund, taxonomy, dan Learning Path. Gunakan backup database jika pemulihan data diperlukan.

Setelah masalah diperbaiki:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
php artisan up
```
