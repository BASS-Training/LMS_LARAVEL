# Perbaikan Laravel - 17 September 2026

## Masalah

Laravel tidak bisa dijalankan. Saat menjalankan `php artisan`, muncul error berulang:

```
Target class [Laravel\Reverb\Console\Commands\InstallCommand] does not exist.
```

Error ini muncul berpuluh-puluh kali di log (`storage/logs/laravel.log`) sejak tanggal 17 September 2026.

## Penyebab

Pemeriksaan lebih lanjut menemukan bahwa **beberapa file di dalam proyek mengalami kerusakan** — isinya tergantikan oleh data biner (karakter Unicode, Dart, atau data Wasm), bukan kode PHP yang seharusnya. Diduga keras penyebabnya adalah **masalah pada penyimpanan (disk F:)**, yang diperkuat oleh adanya log error:

```
Writing to the log file failed: Write of 13801 bytes failed with errno=28 No space left on device
```

### File yang rusak

| File | Kondisi |
|------|---------|
| `vendor/laravel/reverb/src/Console/Commands/InstallCommand.php` | Isi berupa data Unicode (`'succnapprox': '\u2ABA'`), bukan PHP |
| `config/midtrans.php` | Terbaca sebagai binary file oleh editor, padahal seharusnya PHP |
| `app/Services/Payment/OrderService.php` | Isi berupa kode Dart (`import 'visitor.dart'`), bukan PHP |
| `database/migrations/2026_08_14_000100_add_fee_breakdown_to_orders.php` | Terbaca sebagai binary file |
| `vendor/doctrine/dbal/src/Platforms/PostgreSQL120Platform.php` | Isi berupa data Unicode |
| `vendor/fakerphp/faker/src/Faker/Provider/es_AR/Company.php` | Isi berupa data Unicode |
| `vendor/phpunit/phpunit/src/Framework/MockObject/Runtime/Stub/ReturnCallback.php` | Isi berupa kode Dart |
| `vendor/psy/psysh/src/CodeCleaner/ImplicitUsePass.php` | Terbaca sebagai binary file |
| `vendor/maennchen/zipstream-php/test/ZipStreamTest.php` | Terbaca sebagai binary file |

Selain itu, ada satu objek riwayat Git yang corrupt:

```
error: unable to open loose object 9e5f31516151d2b6af91f78811b39e5fd95705da: Function not implemented
```

## Perbaikan yang Dilakukan

### 1. Pasang ulang paket vendor yang rusak

```powershell
composer reinstall laravel/reverb --prefer-dist --no-scripts --no-interaction
composer reinstall doctrine/dbal fakerphp/faker maennchen/zipstream-php phpunit/phpunit psy/psysh --prefer-dist --no-scripts --no-interaction
composer dump-autoload --no-interaction
```

### 2. Pulihkan file aplikasi dari Git

Karena file-file ini masih bisa dibaca dari Git ( HEAD ), mereka dikembalikan ke versi semula:

```powershell
# Backup dulu file corrupt
Copy-Item "config/midtrans.php" "config/midtrans.php.corrupt-bak"
Copy-Item "app/Services/Payment/OrderService.php" "app/Services/Payment/OrderService.php.corrupt-bak"
Copy-Item "database/migrations/2026_08_14_000100_add_fee_breakdown_to_orders.php" "database/migrations/2026_08_14_000100_add_fee_breakdown_to_orders.php.corrupt-bak"

# Restore dari Git
git restore --source=HEAD --worktree -- config/midtrans.php
git restore --source=HEAD --worktree -- app/Services/Payment/OrderService.php
git restore --source=HEAD --worktree -- database/migrations/2026_08_14_000100_add_fee_breakdown_to_orders.php
```

### 3. Bangun ulang aset frontend

```powershell
npm run build
```

### 4. Validasi

| Pemeriksaan | Hasil |
|---|---|
| `php artisan --version` | Laravel Framework 12.42.0 |
| `php artisan migrate:status` | Seluruh migrasi berstatus Ran |
| `php artisan route:list --path=login` | Route login muncul normal |
| HTTP GET `http://127.0.0.1:8000/up` | HTTP 200 |
| HTTP GET `http://127.0.0.1:8000/` | HTTP 200 |
| HTTP GET `http://127.0.0.1:8000/login` | HTTP 200 |
| HTTP GET `http://127.0.0.1:5173/@vite/client` | HTTP 200 |
| Syntax check semua file PHP | Tidak ada error |

## Layanan yang Berjalan

| Layanan | Port | Perintah |
|---|---|---|
| Laravel Dev Server | 8000 | `php artisan serve --host=127.0.0.1 --port=8000` |
| Laravel Reverb | 8080 | `php artisan reverb:start --host=127.0.0.1 --port=8080` |
| Vite Dev Server | 5173 | `npx vite --host=127.0.0.1` |

Untuk menjalankan semua layanan sekaligus, gunakan `runlaravel.bat`.

## Pencegahan ke Depan

1. **Cadangkan proyek dan database secara berkala** ke drive lain (misalnya drive D: atau E:).
2. **Periksa kesehatan drive F:** — kerusakan file secara masif menunjukkan kemungkinan masalah pada media penyimpanan.
3. Jika terjadi error serupa di masa depan, jalankan skrip deteksi file corrupt berikut untuk mempercepat diagnosis:

```powershell
# Deteksi file PHP yang isinya bukan PHP (kemungkinan corrupt)
php -r "
\$count = 0;
\$suspicious = 0;
foreach (['app', 'bootstrap', 'config', 'routes', 'database', 'vendor'] as \$dir) {
    \$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(getcwd().'/'.\$dir, FilesystemIterator::SKIP_DOTS));
    foreach (\$it as \$file) {
        if (!\$file->isFile() || \$file->getExtension() !== 'php') continue;
        \$count++;
        \$src = file_get_contents(\$file->getPathname());
        if (str_contains(\$src, \"\\0\") || !str_contains(\$src, '<?php')) {
            \$suspicious++;
            echo \$file->getPathname().PHP_EOL;
        }
    }
}
echo \"Checked: \$count files; suspicious: \$suspicious\n\";
"
```

## Akun Test Default

Setelah migrasi dan seeding, akun default tersedia:

| Role | Email | Password |
|---|---|---|
| Admin | admin@example.com | password |
| Instructor | instructor@example.com | password |
| Participant | participant@example.com | password |
| Event Organizer | eo@example.com | password |
