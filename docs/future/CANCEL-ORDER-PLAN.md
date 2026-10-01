# Rencana — Fitur Batalkan Pesanan (Pending) + Auto-Expire

> Status: **RENCANA (belum diimplementasi)**
> Scope (sudah disepakati): hanya order **`pending`**, tombol di halaman **Pesanan Saya** + halaman **finish**, sekalian scheduler **auto-expire**.

---

## 1. Latar belakang

Saat ini pengguna **tidak punya cara membatalkan pesanan** — tidak ada tombol "Batalkan" di manapun (`grep batal|cancel` di `resources/views` = 0). Yang ada baru:

- Tombol **"Ganti metode pembayaran"** di `checkout/finish` → memanggil `OrderService::abandon()` (yang isinya *adalah* logika batal) — jadi tombol batal tinggal dibuat, logikanya sudah jalan.
- Order pending **tidak pernah di-expire otomatis** di aplikasi: `expires_at` (24 jam, `config('midtrans.expiry_hours')`) hanya dipakai `Order::isPayable()`; scheduler di `routes/console.php:13` isinya cuma `certificates:cleanup-downloads`. Daftar `/pesanan` bisa menampilkan status basi. Ini diakui di `docs/DEPLOY-PEMBAYARAN-KE-PRODUKSI.md:179` (*"Belum ada cron rekonsiliasi order pending…"*).

### Fakta kode (hasil riset)

| Fakta | Lokasi |
|-------|--------|
| `abandon(Order)`: blokir jika `isPaymentConfirmed()`, cancel Midtrans best-effort, set `cancelled` **hanya jika masih pending** | `app/Services/Payment/OrderService.php:117-132` |
| API cancel Midtrans `POST /v2/{id}/cancel` sudah ada | `app/Services/Payment/MidtransGateway.php:208-215` |
| Status `cancelled` / `expired` sudah ada di kolom (varchar, lebar 32) | `database/migrations/2026_07_14_150000_create_orders_table.php:26` |
| `isPayable()` = pending + snap_redirect_url + belum lewat `expires_at` | `app/Models/Order.php:95-100` |
| Refresh status manual (`refreshFromGateway`) hanya dipanggil dari `finish` & `changeMethod` | `app/Http/Controllers/CheckoutController.php:117,161` |
| Riwayat pesanan `GET /pesanan` → `CheckoutController@index` | `routes/web.php:83` |
| Blade riwayat: **tanpa tombol aksi** | `resources/views/checkout/index.blade.php:22-43` |
| Blade finish pending: tombol bayar / muat ulang / ganti metode | `resources/views/checkout/finish.blade.php:76-97` |
| Mobile/API: sengaja **tidak ada** jalur beli (anti-steering Google Play) | `app/Http/Controllers/Api/ShopApiController.php:22-29` |
| Order tidak menyentuh kuota kelas (`course_class_user`) → batal tidak perlu urus stok | riset: relasi Order tanpa `course_class_id` |

---

## 2. Desain

### 2.1 Route & Controller

```php
// routes/web.php — dekat route pesanan lain (sekitar :83-86)
Route::post('/pesanan/{order}/batal', [CheckoutController::class, 'cancel'])
    ->name('checkout.cancel');
```

`CheckoutController::cancel(Request, Order)`:

1. Pastikan order **milik user login** (`$order->user_id !== auth()->id()` → 404/403). *(Jangan mengandalkan pola route lama — kepemilikan wajib dicek eksplisit.)*
2. Pastikan `status === 'pending'` → selain itu abort 403 (server-side, tidak hanya sembunyikan tombol).
3. Panggil `app(OrderService::class)->abandon($order)` — **tidak perlu logika baru**, guard `isPaymentConfirmed()` sudah ada (OrderService.php:121).
4. Redirect ke `route('checkout.index')` dengan flash sukses, mis. `"Pesanan {order_code} dibatalkan."`

### 2.2 UI

| Tempat | Perubahan |
|--------|-----------|
| `resources/views/checkout/index.blade.php` | Tombol **"Batalkan"** (ghost/merah, kecil) di baris order — hanya dirender jika `isPending()`; `<form>` POST ke `checkout.cancel` + konfirmasi (Alpine `confirm()` / `x-data`) |
| `resources/views/checkout/finish.blade.php` (setelah :97) | Tombol **"Batalkan Pesanan"** di bawah "Ganti metode pembayaran", hanya saat pending; teks menjelaskan: tidak ada dana yang tertahan, bisa pesan ulang kapan saja |

Gaya mengikuti tombol yang sudah ada (rounded-lg, min-h, hover `text-bass-red`). Non-pending → tombol tidak dirender; validasi tetap di server.

### 2.3 Scheduler auto-expire + rekonsiliasi

Command baru **`orders:reconcile`** (`app/Console/Commands/ReconcileExpiredOrdersCommand.php`):

```
1. Ambil order: status = pending AND expires_at < now()
2. Untuk tiap order:
   a. Kalau gateway terkonfigurasi → refreshFromGateway(order)
      (cek status ASLI di Midtrans — jangan paksa expired kalau ternyata
       baru saja dibayar / settlement telat masuk)
   b. Kalau setelah refresh masih pending →
      gateway->cancelTransaction(order_code) + update status = 'expired'
   c. Kalau gateway tidak terkonfigurasi (dev lokal) → update expired langsung
3. Log ringkas: jumlah dieksekusi, jumlah yang berubah.
```

Daftarkan di `routes/console.php`:

```php
Schedule::command('orders:reconcile')->everyFifteenMinutes();
```

> Kenapa 15 menit: jendela expiry 24 jam, tapi daftar `/pesanan` jadi tidak basi >15 menit. Biaya: query ringan + beberapa call API status.

**Kenapa cek ke Midtrans dulu (penting):** guard `applyPaymentStatus` hanya menahan order yang `isPaymentConfirmed()` — order `cancelled`/`expired` yang uangnya ternyata masuk **tetap harus** diproses ke `paid`. Dengan refresh-before-expire, race "bayar detik-detik akhir" tidak pernah salah status.

### 2.4 Aman terhadap race

| Skenario | Hasil |
|----------|-------|
| Klik batal, lalu webhook `settlement` telat masuk | `applyPaymentStatus` → `confirmPayment` → tetap `paid` + enroll (uang tidak hilang; guard hanya menahan yang sudah `isPaymentConfirmed()`) |
| Klik batal saat order sudah `paid` di server | `abandon()` return diam-diam; controller juga sudah reject non-pending sebelumnya (403) |
| Dua tab klik batal bersamaan | Kedua kali aman — update ke `cancelled` idempotent |
| "Ganti metode" vs "Batalkan" | Keduanya pakai `abandon()` yang sama; ganti metode lalu bikin order baru tetap berjalan |

---

## 3. Urutan implementasi

1. [ ] `routes/web.php`: `POST /pesanan/{order}/batal` → `checkout.cancel`
2. [ ] `CheckoutController::cancel()`: cek kepemilikan + status pending + `abandon()` + flash
3. [ ] Blade `checkout/index.blade.php`: tombol Batalkan (pending only) + konfirmasi
4. [ ] Blade `checkout/finish.blade.php`: tombol Batalkan Pesanan (pending only)
5. [ ] `ReconcileExpiredOrdersCommand` (`orders:reconcile`) + daftar di `routes/console.php` (`everyFifteenMinutes`)
6. [ ] Pastikan `Schedule` helper ter-import di `routes/console.php` (style Laravel 12)
7. [ ] Test:
   - [ ] Batal pending → `cancelled` (`Http::fake()` untuk gateway)
   - [ ] Batal paid / awaiting_verification → 403, status tidak berubah
   - [ ] Batal order user lain → 404/403
   - [ ] `orders:reconcile`: pending lewat expiry → `expired`; order yang status aslinya sudah paid di gateway → jadi `paid`, **bukan** expired
8. [ ] Jalankan `./vendor/bin/pint` + `php artisan test`

### Estimasi sentuhan
- Baru: 1 command, 1–2 file test.
- Ubah: `routes/web.php` (1 baris), `CheckoutController` (1 method), 2 blade, `routes/console.php` (1 baris).
- **Tanpa migrasi** — status `cancelled`/`expired` sudah ada.

---

## 4. Di luar scope (keputusan eksplisit)

- **Batal order `paid` / refund** → tidak: butuh un-enroll (tidak ada `order_id` di `course_user`) + refund; refund tetap **manual via dashboard Midtrans** (`docs/DEPLOY-PEMBAYARAN-KE-PRODUKSI.md:178`).
- **Tombol batal di mobile/API** → tidak, konsisten kebijakan anti-steering (app tidak punya jalur beli).
- **Efek kuota/kelas** → tidak perlu, order tidak menulis `course_class_user`.
- **Notifikasi email batal** → lihat `EMAIL-NOTIFICATION-PLAN.md`; bisa ditambah menyusul (order-status notification sudah terencana di Fase 1 — tinggal tambah case `cancelled`).

---

## 5. Verifikasi manual (setelah implementasi)

1. Beli kursus → di `/pesanan` muncul order pending + tombol Batalkan → klik, konfirmasi → status jadi **Dibatalkan**, badge sesuai `status_label`.
2. Cek di dashboard Midtrans: transaksi tercatat `cancelled` (jika gateway aktif).
3. Order paid → tombol tidak muncul; POST paksa ke route batal → 403.
4. Set `expires_at` order pending ke waktu lampau (lokal) → jalankan `php artisan orders:reconcile` → status `expired`.
5. `php artisan schedule:list` → `orders:reconcile` terjadwal tiap 15 menit.

---

*Dibuat 2026-09-30 — rencana, belum diimplementasi.*
