# Rencana: Sistem Kupon (Voucher)

Status: **RENCANA — belum diimplementasikan.**
Dokumen ini disiapkan agar implementasi bisa langsung dijalankan kapan pun diperlukan.

Referensi riset: `docs/system/COURSE-COMMERCE-ROADMAP.md`
- §6.2 Kupon — rancangan tabel asli.
- §5.6 — kolom snapshot order (`discount_amount`, `coupon_code`).
- §9 — kupon memang di **Fase 2 (Conversion) item 2**.
- Bisa didahulukan dari Fase 1: tidak ada dependency ke category/tag/sales profile; prasyarat hanya kolom snapshot orders.

---

## 1. Keputusan yang Masih Perlu Dikonfirmasi

Sebelum eksekusi, putuskan dulu:

1. **Kapan limit pemakaian dihitung — saat order dibuat atau saat paid?**
   - Roadmap (§6.2): validasi + pembuatan order dalam satu transaksi → limit di-reserve saat order dibuat.
   - Risiko: order pending yang dibatalkan/kedaluwarsa tetap menghabiskan limit → perlu aturan rilis (mis. `coupon_redemptions` dihapus saat `OrderService::abandon()` / order expired, atau hanya hitung redemption yang order-nya paid).
   - **Saran**: reserve saat order dibuat + rilis saat abandon/expired → limit akurat tanpa menunggu bayar.
2. **Kupon global dulu atau langsung per-course (`coupon_course`)?**
   - **Saran MVP**: global (tanpa pivot `coupon_course`) — pivot ditambahkan hanya kalau ada kebutuhan kupon khusus course.
   - Jika langsung pakai `coupon_course`, ikuti skema roadmap §6.2 persis.
3. **Menumpuk dengan promosi terjadwal (`course_promotions` §6.1)?**
   - **Saran MVP**: promosi terjadwal TIDAK dibuat dulu — kupon saja. Kalau nanti keduanya ada, putuskan apakah boleh stack (saran: tidak boleh, cukup pakai yang paling menguntungkan).

Aturan yang sudah bisa diasumsikan (konsisten dengan keputusan bundle):
- **Fee dihitung dari harga SETELAH diskon** (fee % mengikuti yang benar-benar ditagih ke Midtrans).
- Kupon bisa dipakai untuk order course tunggal DAN order bundle (diskon dihitung dari `base_amount` sebelum fee).
- Berlaku di web checkout; mobile tetap lewat Website (tidak ada in-app purchase).

---

## 2. Skema Database

```text
coupons
- id
- code unique (case-insensitive: simpan uppercase, validasi uppercase)
- discount_type enum(fixed, percentage)
- discount_value unsigned int        -- rupiah utk fixed, persen utk percentage (max 100)
- minimum_amount nullable unsigned int
- usage_limit nullable unsigned int  -- global
- per_user_limit nullable unsigned int default 1
- starts_at nullable timestamp
- expires_at nullable timestamp
- is_active bool default true        -- index
- timestamps

coupon_redemptions
- id
- coupon_id -> coupons.id
- order_id -> orders.id
- user_id -> users.id
- discount_amount unsigned int
- redeemed_at timestamp
- unique(coupon_id, order_id)
- index(coupon_id, user_id)

-- opsional (jika kupon per-course, lihat §1 butir 2):
coupon_course
- coupon_id -> coupons.id
- course_id -> courses.id
- unique(coupon_id, course_id)

orders (ALTER)
- + coupon_code nullable string      -- snapshot kode saat checkout
- + discount_amount unsigned int     -- SHARED dengan plan bundle!
```

> **PENTING — jangan dobel migration:**
> - `orders.discount_amount` dibuat oleh **`docs/future/COURSE-BUNDLE-PLAN.md`** (untuk potongan kepemilikan course dalam bundle).
> - Kupon hanya menambah **`orders.coupon_code`** dan MENGISI `discount_amount` untuk transaksi ber-kupon.
> - Jika kupon dieksekusi PERTAMA, kupon yang membuat `discount_amount` + `coupon_code`, bundle hanya menambah `bundle_id` dan mengisi `discount_amount` yang sudah ada.
> - Kolom `orders.discount_amount` menyimpan SATU angka final yang mengurangi `base_amount`. Untuk MVP tidak ada stacking bundle+kupon dalam satu order (kupon pada order bundle boleh, hasilnya satu angka gabungan yang disimpan).

---

## 3. Backend Laravel

### 3.1 Model

- **`app/Models/Coupon.php` (baru)**: fillable/casts; helper:
  - `isValidNow(): bool` — `is_active` + `starts_at <= now() <= expires_at`.
  - `computeDiscount(int $amount): int` — fixed → min(value, amount); percentage → `amount * value / 100` (bulatkan ke atas ke rupiah utuh), selalu `min(discount, amount)` (harga tidak negatif).
  - `remainingGlobalUsage(): ?int`, `redeemedCountBy(User): int`.
- **`app/Models/CouponRedemption.php` (baru)**.
- **`app/Models/Order.php`**: tambah `coupon_code` ke fillable (sudah ada `discount_amount` dari plan bundle).

### 3.2 Layanan Validasi + Penerapan

**`app/Services/Payment/CouponService.php` (baru)** — dipanggil dari `OrderService`:

```php
public function validateAndApply(Coupon $coupon, int $amount, User $user): int
// melempar RuntimeException dengan pesan Indonesia bila:
//  - belum aktif / belum mulai / sudah kedaluwarsa
//  - amount < minimum_amount
//  - usage_limit tercapai
//  - per_user_limit tercapai
//  - tidak berlaku untuk course/order ini (jika pivot course_course dipakai)
// mengembalikan besaran diskon
```

- **Wajib dalam `DB::transaction` + `lockForUpdate()`** pada row `coupons` saat insert `coupon_redemptions` → mencegah pemakaian melebihi limit pada request bersamaan (syarat roadmap §6.2).

### 3.3 Integrasi Checkout

- **`OrderService::checkout()` dan `checkoutBundle()`**:
  - Parameter baru `?string $couponCode = null`.
  - Urutan hitung: `base awal` (harga course / harga bundle setelah potongan kepemilikan) → `discount = CouponService::validateAndApply(...)` → `base = base awal − discount` → `ServiceFee::forMethod(base, methodKey)` → `amount = base + fee`.
  - Simpan `coupon_code`, `discount_amount` di order. Buat `coupon_redemptions` dalam transaksi yang sama.
  - Reuse order pending (dedupe): cocokkan juga `coupon_code` — order lama dengan kode berbeda tidak di-reuse, buat order baru.
- **`OrderService::abandon()`**: sesuai keputusan §1 butir 1 — bila memakai reserve-at-order, hapus `coupon_redemptions` order ini saat abandon (dan saat order berstatus `expired`).
- **`CheckoutController`**: terima input `coupon_code` dari request (`store` + `storeBundle`), validasi di service (bukan di controller) → `RuntimeException` ditangkap seperti error checkout lain (redirect balik dengan `withErrors`).

### 3.4 UI Checkout (Web)

- **`resources/views/checkout/choose.blade.php`**: input "Kode Kupon" + tombol "Gunakan".
  - Rekomendasi: submit kupon via form POST terpisah yang menghitung ulang estimasi (atau endpoint `POST /katalog/{course}/cek-kupon` yang mengembalikan JSON {discount, total}) → update Alpine state. Halaman pilih metode tetap menampilkan total final per metode setelah kupon.
  - Tampilkan baris "Diskon (KUPONXYZ)" −Rp … dan total baru.
- Pastikan kupon ikut saat `changeMethod` (order lama di-abandon → order baru dibuat dengan kode kupon yang sama dari input ulang / session — putuskan: simpan `coupon_code` sementara di session `checkout.coupon` agar tidak hilang saat ganti metode. **Saran: session.**)

### 3.5 Admin CRUD Kupon

- Permission baru **`manage coupons`** (naming gaya repo: `manage bundles`, `manage users`) di `RolesAndPermissionsSeeder`.
- Route grup terpisah `admin/coupons` + `permission:manage coupons` (pola `admin/bundles` di plan bundle / `admin/verifikasi-pembayaran`).
- **`app/Http/Controllers/Admin/CouponController.php`**: index (tabel + info pemakaian), create/store/edit/update/destroy (aktif/nonaktif), inline validate:
  - `code => required|string|max:50|unique:coupons,code` (uppercase-kan).
  - `discount_type => required|in:fixed,percentage`; `discount_value => required|integer|min:1` + `max:100` bila percentage.
  - `usage_limit/per_user_limit/minimum_amount => nullable|integer|min:1`; `starts_at/expires_at => nullable|date` + `after_or_equal` untuk expires.
- View `resources/views/admin/coupons/{index,create,edit}.blade.php` — pola `admin/users/index.blade.php`.
- `ActivityLog::log('coupon_created', ...)`.

### 3.6 Invoice / Tampilan Order

- `invoices/pdf.blade.php` + `checkout/finish.blade.php`: tampilkan baris "Diskon (KODE)" −Rp … bila `discount_amount > 0 && coupon_code` (bundle juga menampilkan diskonnya — bedakan label: bundle = "Potongan bundle", kupon = "Kupon {kode}").
  - Opsional MVP: cukup satu baris "Diskon" dengan catatan `coupon_code` di metadata.

---

## 4. Mobile API

- **Tidak ada endpoint kupon khusus** — kupon dimasukkan saat checkout di WEBSITE (mobile tetap lewat tombol Website, konsisten dengan arsitektur tanpa in-app purchase).
- Opsional (nanti): `GET /api/mobile/coupons/validate?code=...` hanya bila mobile kelak punya halaman checkout sendiri — TIDAK termasuk scope MVP.

---

## 5. Tests

| Test | Cakupan |
|---|---|
| `tests/Feature/CouponCheckoutTest.php` | kode valid → order `base_amount` berkurang, `discount_amount`/`coupon_code` terisi, fee dihitung dari harga setelah diskon, total benar; kupon tidak mengubah harga course lama (snapshot aman) |
| `tests/Feature/CouponValidationTest.php` | expired / belum mulai / nonaktif / di bawah `minimum_amount` / usage_limit habis / per_user_limit habis → ditolak dengan pesan Indonesia + TIDAK ada redemption tersimpan |
| `tests/Feature/CouponConcurrencyTest.php` (atau unit-style) | limit global 1 → dua order bersamaan hanya satu yang dapat (transaksi + lock) |
| `tests/Feature/CouponAbandonTest.php` | order ber-kupon di-abandon → redemption dirilis, kode bisa dipakai lagi |
| `tests/Feature/AdminCouponRouteTest.php` | 403 tanpa `manage coupons`, store valid 200, percentage > 100 ditolak |

- Regresi: `CheckoutChooseTest`, `CheckoutStatusTest`, `BundleCheckoutTest`/`BundlePaymentTest` (jika bundle sudah terlebih dahulu), `MobileCatalogApiTest`.
- Daftarkan route admin coupon di `RouteMiddlewareCoverageTest`.

---

## 6. Dokumentasi

- `docs/system/ERD.md`: entity COUPONS + COUPON_REDEMPTIONS (+ COUPON_COURSE bila dipakai), ORDERS + `coupon_code`, relasi, baris Katalog Tabel, aturan integritas (validasi + insert dalam transaksi).
- `docs/system/COURSE-COMMERCE-ROADMAP.md`: §6.2 ditandai direalisasikan.
- `CLAUDE.md`: satu baris pada Assessment/Commerce bila relevan.

---

## 7. Tahapan Eksekusi

1. **Fase 1 — Inti**: migration (cek dulu `discount_amount` sudah ada dari plan bundle atau belum) → `Coupon`/`CouponRedemption` model → `CouponService` → integrasi `OrderService::checkout()`/`checkoutBundle()` + `abandon()`.
2. **Fase 2 — UI**: input kupon di `checkout/choose.blade.php` + estimasi hitung-ulang + session saat `changeMethod` → invoice/finish baris diskon.
3. **Fase 3 — Admin**: permission → route → controller → view → test permission.
4. **Fase 4 — Test + docs** (lihat §5, §6). Gate: `./vendor/bin/pint` + `php artisan test` lulus.

> Estimasi urutan relatif plan bundle: **setelah Fase A bundle** (karena berbagi kolom `discount_amount` dan menyentuh `OrderService` yang sama) — tapi bisa berdiri sendiri lebih dulu asal migration-nya yang membuat `discount_amount`.

---

## 8. Catatan Edge (Ditetapkan)

- Kupon 100% diskon → `base = 0` → **tolak** (Midtrans butuh amount > 0) atau otomatis enroll gratis tanpa lewat Midtrans? **Saran MVP: tolak dengan pesan "Kupon tidak berlaku untuk transaksi ini"** — putuskan saat eksekusi.
- Kupon untuk order bundle: diskon dihitung dari `base_amount` order bundle (setelah potongan kepemilikan), bukan dari harga course individual.
- Pembeli menyetujui verifikasi manual (`approve()`) — redemption sudah tercatat sejak order dibuat; tidak diubah saat approve.
- Kupon dipakai lalu order-nya `failed`/`rejected` → tergantung keputusan §1 butir 1 (riil: rilis saat status non-pending yang bukan paid).
- Kode kupon case-insensitive: normalisasi `strtoupper()` di controller + service.
