# Panduan Update Development dan Production

Dokumen ini digunakan untuk memperbarui LMS dari branch pengembangan:

```text
dev/UI
```

Branch ini mencakup taxonomy course, refund, coupon, Bundle, Learning Path, pembaruan halaman publik, filter manajemen course, email status pembayaran, dan antrean pembayaran Midtrans. Gunakan commit rilis yang benar-benar sudah di-push/merge; perubahan lokal yang belum di-commit tidak akan ikut ketika server menjalankan `git pull`.

## Catatan Penting

- Backup database production sebelum menjalankan migration.
- Jangan menjalankan `php artisan migrate:fresh` di database yang sudah berisi data.
- Jangan menjalankan seeder umum dengan `--seed` di production.
- Pastikan worktree bersih sebelum berpindah branch atau pull: `git status`.
- File `.env` tidak boleh ditimpa atau dimasukkan ke Git.
- Coupon tetap nonaktif jika `COUPONS_FEATURE_ENABLED=false`.
- Antrean pembayaran tetap nonaktif selama `PAYMENT_ASYNC_ENABLED=false`. Jangan aktifkan sebelum migrasi dan worker `payments` berjalan.
- Akses produksi harus memakai `QUEUE_CONNECTION=database`; koneksi `payment_database` menggunakan database yang sama dengan tabel `orders` dan `jobs`.

## Update Development

### Pertama Kali Mengambil Branch

```bash
git fetch origin
git switch --track origin/dev/UI
```

Jika branch lokal sudah pernah dibuat:

```bash
git switch dev/UI
git pull --ff-only origin dev/UI
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
mysqldump -u <DB_USERNAME> -p <DB_DATABASE> > backup-sebelum-update-$(date +%F-%H%M).sql
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
git switch --track origin/dev/UI
```

Jika server sudah menggunakan branch tersebut:

```bash
git fetch origin
git switch dev/UI
git pull --ff-only origin dev/UI
```

Jika perubahan `dev/UI` sudah di-merge ke `main`, server production dapat menarik `main`:

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
- `2026_10_06_000000_add_payment_queue_tracking.php` (kolom status Snap dan tabel receipt webhook)

`RolesAndPermissionsSeeder` bersifat idempotent dan diperlukan untuk membuat permission `manage coupons`, `manage bundles`, `manage learning paths`, dan `manage course taxonomy` yang belum ada.

### 6. Periksa Environment

Pastikan konfigurasi pembayaran production tetap benar dan rahasia tidak ditulis ke repository:

```dotenv
MIDTRANS_IS_PRODUCTION=true
MIDTRANS_SERVER_KEY=<server-key-production>
MIDTRANS_CLIENT_KEY=<client-key-production>
QUEUE_CONNECTION=database
PAYMENT_ASYNC_ENABLED=false
```

Aktifkan fitur coupon hanya setelah konfigurasi dan coupon di panel admin siap:

```dotenv
COUPONS_FEATURE_ENABLED=true
```

Jika coupon belum siap dirilis:

```dotenv
COUPONS_FEATURE_ENABLED=false
```

### 7. Siapkan Worker Pembayaran

Bangun cache konfigurasi dengan flag masih `false` supaya worker mengenali koneksi queue baru:

```bash
php artisan optimize:clear
php artisan config:cache
```

Pastikan worker queue default yang mengirim email tetap berjalan. Tambahkan worker permanen khusus pembayaran melalui Supervisor atau process manager server. Contoh perintah proses:

```bash
php artisan queue:work database --queue=default --tries=3 --timeout=60
php artisan queue:work payment_database --queue=payments --tries=5 --timeout=40
```

Jalankan **dua proses** worker `payment_database` sebagai titik awal. Atur direktori kerja ke root aplikasi, user proses sesuai kepemilikan aplikasi, `autostart=true`, dan `autorestart=true`. Worker default dan worker pembayaran harus memiliki nama proses terpisah. `--timeout=40` berada di bawah `retry_after=90` pada `config/queue.php`. Jangan menjalankan worker ini hanya di sesi SSH karena proses akan berhenti saat sesi ditutup.

Contoh konfigurasi Supervisor untuk worker pembayaran (sesuaikan path, nama user, dan executable PHP di server):

```ini
[program:lms-payments]
process_name=%(program_name)s_%(process_num)02d
command=/usr/bin/php /path/ke/lms/artisan queue:work payment_database --queue=payments --tries=5 --timeout=40 --sleep=1
directory=/path/ke/lms
user=www-data
numprocs=2
autostart=true
autorestart=true
stopwaitsecs=45
redirect_stderr=true
stdout_logfile=/path/ke/lms/storage/logs/payments-worker.log
```

Setelah menyimpan konfigurasi Supervisor, jalankan `sudo supervisorctl reread`, `sudo supervisorctl update`, dan `sudo supervisorctl status lms-payments:*`. Pastikan kedua proses berstatus `RUNNING`.

Pastikan process manager melaporkan kedua worker pembayaran berjalan sebelum mengubah `PAYMENT_ASYNC_ENABLED` menjadi `true`. Jika worker belum siap, biarkan flag `false` agar checkout dan webhook tetap memakai alur lama.

### 8. Aktifkan Antrean dan Bangun Ulang Cache

Setelah migrasi dan worker siap, ubah `.env` server:

```dotenv
PAYMENT_ASYNC_ENABLED=true
```

Kemudian jalankan:

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
```

Restart worker default, worker pembayaran, dan Reverb melalui Supervisor atau process manager yang digunakan server. Ganti nama proses di bawah dengan nama yang benar pada server:

```bash
sudo supervisorctl restart <queue-worker-name>
sudo supervisorctl restart <payments-worker-name>
sudo supervisorctl restart <reverb-name>
```

### 9. Buka Kembali Aplikasi

```bash
php artisan up
```

### 10. Verifikasi Production

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
- Satu checkout uji membuat order `pending` dengan `snap_status=queued`, lalu worker mengubahnya menjadi `ready` dan halaman menunggu membuka Snap.
- Setelah pembayaran uji terkonfirmasi, receipt webhook memiliki `processed_at`, order berubah sesuai status pembayaran, akses course/Bundle diberikan satu kali, dan email status pembayaran masuk melalui worker default.
- Periksa antrean `payments` dan `failed_jobs`; tidak ada penumpukan atau kegagalan baru. Periksa order `snap_status=needs_review` terhadap dashboard Midtrans sebelum tindakan manual.
- Jalankan `php artisan queue:failed` dan cek jumlah job `payments` di tabel `jobs`. Periksa log worker jika jumlahnya terus naik.
- Bundle atau Learning Path AVPN tidak terlihat oleh akun yang belum disetujui.
- Worker default, dua worker pembayaran, dan Reverb berjalan tanpa error.

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

Jika gangguan khusus antrean pembayaran terjadi, set `PAYMENT_ASYNC_ENABLED=false` dan jalankan `php artisan config:cache` untuk menghentikan pembuatan job pembayaran baru. **Biarkan worker pembayaran memproses job yang sudah tersimpan**; jangan hapus tabel `jobs` atau receipt webhook. Periksa `failed_jobs`, `storage/logs/laravel.log`, serta status transaksi di Midtrans sebelum mencoba ulang pembayaran yang belum memiliki tautan Snap.

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
