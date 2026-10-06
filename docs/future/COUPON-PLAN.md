# Rencana: Sistem Kupon (Voucher)

Status: **MVP DIIMPLEMENTASIKAN — checkout default NONAKTIF.**

Implementasi tersedia untuk checkout web course tunggal, kupon global/per-course,
reservasi kuota, admin CRUD, invoice, dan lifecycle order. Aktivasi memerlukan dua lapis kontrol:

```text
COUPONS_FEATURE_ENABLED=true
Admin > Manajemen Kupon > Aktifkan Checkout
```

Default environment dan database sama-sama `false`, sehingga deployment tidak langsung menampilkan kupon.

Referensi riset: `docs/system/COURSE-COMMERCE-ROADMAP.md`
- §6.2 Kupon — rancangan tabel asli.
- §5.6 — kolom snapshot order (`discount_amount`, `coupon_code`).
- §9 — kupon memang di **Fase 2 (Conversion) item 2**.
- Bisa didahulukan dari Fase 1: tidak ada dependency ke category/tag/sales profile; prasyarat hanya kolom snapshot orders.

---

## 1. Keputusan MVP yang Diterapkan

Keputusan implementasi:

1. **Limit direservasi saat order dibuat.**
   - Roadmap (§6.2): validasi + pembuatan order dalam satu transaksi → limit di-reserve saat order dibuat.
   - Risiko: order pending yang dibatalkan/kedaluwarsa tetap menghabiskan limit → perlu aturan rilis (mis. `coupon_redemptions` dihapus saat `OrderService::abandon()` / order expired, atau hanya hitung redemption yang order-nya paid).
   - **Saran**: reserve saat order dibuat + rilis saat abandon/expired → limit akurat tanpa menunggu bayar.
2. **Kupon mendukung global dan course tertentu** melalui `coupon_course`.
3. **Promosi terjadwal belum dibuat**, sehingga stacking belum didukung.
4. **Diskon yang menghasilkan Rp0 ditolak**; tidak ada enrollment gratis melalui kupon.
5. **Redemption dilepas** hanya untuk order unpaid `failed`, `cancelled`, atau `expired`.
6. **Redemption dipertahankan** untuk paid, awaiting verification, rejected setelah uang diterima, dan refunded.
7. **Order pending yang sudah memiliki redemption tetap memakai snapshot** walau kupon kemudian dinonaktifkan/kedaluwarsa.

Aturan yang sudah bisa diasumsikan (konsisten dengan keputusan bundle):
- **Fee dihitung dari harga SETELAH diskon** (fee % mengikuti yang benar-benar ditagih ke Midtrans).
- MVP mendukung order course tunggal dan bundle.
- Order bundle hanya menerima kupon global (`applies_to_all_courses = true`); kupon course-specific ditolak.
- Berlaku di web checkout; mobile tidak memiliki checkout atau tautan pembelian.

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

coupon_settings
- checkout_enabled bool default false
- updated_by nullable -> users.id

-- opsional (jika kupon per-course, lihat §1 butir 2):
coupon_course
- coupon_id -> coupons.id
- course_id -> courses.id
- unique(coupon_id, course_id)

coupons
- + applies_to_all_courses bool default true

orders (ALTER)
- + coupon_code nullable string      -- snapshot kode saat checkout
- + discount_amount unsigned int     -- diskon kupon saja
```

> **Kontrak dengan fitur bundle:**
> - Migration kupon sudah membuat `orders.discount_amount` dan `orders.coupon_code`; migration bundle tidak boleh membuatnya lagi.
> - `orders.discount_amount` selalu menyimpan diskon kupon.
> - Potongan karena course dalam bundle sudah dimiliki disimpan terpisah di `orders.bundle_discount_amount`.
> - Urutan bundle: harga bundle → `bundle_discount_amount` → kupon global (`discount_amount`) → `base_amount` → biaya layanan.

---

## 3. Backend Laravel

### 3.1 Model

- **`app/Models/Coupon.php` (baru)**: fillable/casts; helper:
  - `isValidNow(): bool` — `is_active` + `starts_at <= now() <= expires_at`.
  - `computeDiscount(int $amount): int` — fixed → min(value, amount); percentage → `amount * value / 100` (bulatkan ke atas ke rupiah utuh), selalu `min(discount, amount)` (harga tidak negatif).
  - `remainingGlobalUsage(): ?int`, `redeemedCountBy(User): int`.
- **`app/Models/CouponRedemption.php` (baru)**.
- **`app/Models/Order.php`**: `coupon_code` dan `discount_amount` sudah ditambahkan oleh implementasi kupon; bundle hanya menambah field miliknya sendiri.

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
  - Untuk bundle, tolak kupon course-specific dan hanya izinkan kupon global.
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

- `invoices/pdf.blade.php` + `checkout/finish.blade.php`: tampilkan baris "Kupon {KODE}" −Rp … bila `discount_amount > 0 && coupon_code`. Saat bundle diimplementasikan, `bundle_discount_amount` ditampilkan pada baris "Potongan kepemilikan course" yang terpisah.
  - Opsional MVP: cukup satu baris "Diskon" dengan catatan `coupon_code` di metadata.

---

## 4. Mobile API

- **Tidak ada endpoint kupon mobile** — kupon hanya tersedia jika pengguna secara mandiri membuka checkout web. Aplikasi mobile tidak menampilkan tombol/link pembelian.
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

1. **Fase 1 — Inti**: migration kupon membuat `discount_amount` → `Coupon`/`CouponRedemption` model → `CouponService` → integrasi `OrderService::checkout()` + `abandon()`. Integrasi `checkoutBundle()` dilakukan saat fitur bundle dibangun.
2. **Fase 2 — UI**: input kupon di `checkout/choose.blade.php` + estimasi hitung-ulang + session saat `changeMethod` → invoice/finish baris diskon.
3. **Fase 3 — Admin**: permission → route → controller → view → test permission.
4. **Fase 4 — Test + docs** (lihat §5, §6). Gate: `./vendor/bin/pint` + `php artisan test` lulus.

> Kupon diimplementasikan lebih dahulu. Bundle memakai kolom kupon yang sudah ada dan menyimpan potongan kepemilikan secara terpisah pada `bundle_discount_amount`.

---

## 8. Catatan Edge (Ditetapkan)

- Kupon yang menghasilkan `base <= 0` ditolak karena Midtrans membutuhkan nominal positif.
- Kupon untuk order bundle: hanya kupon global yang diizinkan dan diskon dihitung dari harga bundle setelah potongan kepemilikan, bukan dari harga course individual.
- Pembeli menyetujui verifikasi manual (`approve()`) — redemption sudah tercatat sejak order dibuat; tidak diubah saat approve.
- Kupon pada order unpaid `failed` dilepas; `rejected` setelah dana dikonfirmasi tetap dianggap terpakai.
- Kode kupon case-insensitive: normalisasi `strtoupper()` di controller + service.
