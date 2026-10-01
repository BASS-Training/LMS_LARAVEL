# Rencana: Course Bundle (Harga Per-Course + Harga Bundle)

Status: **RENCANA — belum diimplementasikan.**
Dokumen ini disiapkan agar implementasi bisa langsung dijalankan kapan pun diperlukan.

Referensi riset: `docs/system/COURSE-COMMERCE-ROADMAP.md` (bundle ada di §8 "Fitur yang Ditunda" dan Fase 4 — diangkat lebih awal karena fondasi order/payment sudah stabil).

---

## 1. Aturan Bisnis (Sudah Diputuskan)

1. **Potongan untuk course yang sudah dimiliki.**
   `harga bayar = max(harga bundle − Σ harga per course yang sudah dimiliki, 0)`.
   - "Sudah dimiliki" = sudah enrolled (`course_user`) **ATAU** mengelola course tersebut (instruktur/super-admin).
   - Hasil ≤ 0 → order DITOLAK dengan pesan: "Anda sudah memiliki seluruh isi bundle."
   - Potongan dicatat di `orders.discount_amount`.
   - Harga personal (setelah potongan) ditampilkan di halaman checkout, bukan di halaman publik bundle (publik tetap lihat harga bundle normal).
2. **Semua course dalam bundle wajib berbayar** (`price > 0`), published, dan visibility catalog. Divalidasi saat admin create/update bundle.
3. **Verifikasi pembayaran PER BUNDLE, bukan per course.**
   Kolom `bundles.requires_payment_verification` (admin yang menentukan per bundle). Order bundle memakai flag ini — bukan warisan dari salah satu course.
4. **Tampil di mobile.** Mobile tetap TANPA in-app purchase (arsitektur yang ada): mobile hanya menampilkan bundle, pembelian lewat tombol **Website** → `https://lms.basstrainingacademy.com/bundles/{slug}`.
5. **Harga bundle diisi absolut oleh admin** (bukan persen). Total harga per-course dihitung sistem dari `Σ courses.price` untuk tampilan "Hemat Rp X (Y%)".

---

## 2. Skema Database (3 Migration)

```text
bundles
- id
- title
- slug unique
- description nullable
- price unsigned int                  -- harga BUNDLE (admin isi absolut)
- is_active bool default true         -- index
- requires_payment_verification bool default false
- timestamps

bundle_courses
- id
- bundle_id -> bundles.id (cascadeOnDelete)
- course_id -> courses.id (cascadeOnDelete)
- sort_order unsigned int default 0
- unique(bundle_id, course_id)

orders (ALTER)
- course_id -> nullable()             -- sebelumnya wajib
- + bundle_id -> bundles.id nullable (index)
- + discount_amount unsigned int default 0   -- potongan "course sudah dimiliki"
```

- Constraint "tepat satu dari `course_id` / `bundle_id`" dijaga di **level aplikasi** (`OrderService`), sesuai konvensi repo.
- Kolom `discount_amount` dipakai BERSAMA dengan plan kupon (`docs/future/COUPON-PLAN.md`) — **buat sekali saja**, jangan dobel migration. Bedanya: bundle mengisinya untuk potongan kepemilikan, kupon mengisinya untuk diskon kode kupon.
- `orders.course_id` yang nullable aman: satu-satunya query `where('course_id', ...)` di orders ada di `app/Services/Payment/OrderService.php` (dedupe order pending) — NULL tidak pernah match, jadi tidak error.

---

## 3. Backend Laravel

### 3.1 Model

- **`app/Models/Bundle.php` (baru)**
  - `fillable`: title, slug, description, price, is_active, requires_payment_verification.
  - `casts`: price integer, is_active bool, requires_payment_verification bool.
  - Relasi: `courses()` belongsToMany withPivot `sort_order` (urut `sort_order`).
  - Helper: `originalPrice(): int` (Σ `courses.price`), `savings(): int` (`originalPrice() − price`), `requiresPaymentVerification(): bool`.
  - Scope: `scopeActive($q)` → `where('is_active', true)`.
- **`app/Models/Order.php` (ubah)**
  - Tambah `bundle_id`, `discount_amount` ke `$fillable` + `$casts`.
  - Relasi `bundle()` belongsTo.
  - **Accessor `orderTitle(): string`** → judul course ATAU judul bundle. Ini meminimalkan churn di view yang selama ini asumsi `$order->course` satu course.
  - Helper `isBundleOrder(): bool`.

### 3.2 OrderService (`app/Services/Payment/OrderService.php`)

- **Method baru `checkoutBundle(Bundle $bundle, User $user, ?string $methodKey = null): Order`** — pola mirror dari `checkout()`:
  - Guard: gateway aktif; bundle `is_active`; semua course valid (published+catalog+berbayar); user bukan pengelola seluruh isi bundle.
  - Hitung `owned` (enrolled OR managed) → `base = max(bundle->price − Σ owned prices, 0)` → jika `base <= 0` throw `RuntimeException`.
  - Fee: `ServiceFee::forMethod($base, $methodKey)` — **tanpa perubahan** (input sudah int).
  - Reuse order pending yang di-key `bundle_id` + `payment_method_key` + nominal (pola `checkout()` baris ~72-84).
  - `Order::create([... 'course_id' => null, 'bundle_id' => ..., 'discount_amount' => potongan, 'base_amount' => base, 'fee_amount' => fee, 'amount' => base + fee ...])` dalam `DB::transaction`, lalu Snap.
- **Ekstrak helper `grantAccess(Order $order)`** dari `confirmPayment()` (baris ~222-243) dan `approve()` (baris ~269):
  - Verifikasi: `$order->isBundleOrder() ? $order->bundle->requiresPaymentVerification() : $order->course->requiresPaymentVerification()`.
  - Enrollment: bundle → **loop** `bundle->courses` → `syncWithoutDetaching([$user_id])` per course (idempotent); course tunggal → perilaku lama persis.
  - Dipakai juga oleh `approve()`.
- `abandon()` tidak berubah (tidak memakai `course_id`).

### 3.3 Checkout Web

Route (ikut gaya `routes/web.php` blok katalog/checkout, auth via constructor `CheckoutController`):

```text
GET  /bundles/{slug}            -> BundleController@show        name: bundles.show     (PUBLIK)
GET  /bundles/{slug}/beli       -> CheckoutController@chooseBundle   name: checkout.bundle-choose   (auth)
POST /bundles/{slug}/beli       -> CheckoutController@storeBundle    name: checkout.bundle-store    (auth)
```

- `CheckoutController::chooseBundle/storeBundle` — duplikat tipis dari `choose/store` (method TERPISAH supaya test course lama tidak tersentuh).
- `finish`, `changeMethod`, `index`, `invoice`: branch bundle.
  - `resources/views/checkout/finish.blade.php:7` (`needsVerif`) → `order->course ? course->requiresPaymentVerification() : order->bundle->requiresPaymentVerification()`.
  - Judul di semua tempat → `orderTitle()`.
  - `changeMethod` (baris ~173-176): order bundle → kembali ke `checkout.bundle-choose`.
- **`app/Services/Payment/MidtransGateway.php`** (~baris 37, 49-50): Snap `item_details` branch — bundle → `id = bundle-{id}`, `name = bundle title`.

### 3.4 View Baru / Penyesuaian

- **`resources/views/shop/bundle.blade.php` (baru)**: daftar course + harga masing-masing, total per-course dicoret, harga bundle mencolok, "Hemat Rp X (Y%)", deskripsi, CTA Beli.
- **Section "Paket Kursus"** di atas grid `resources/views/shop/index.blade.php` — `ShopController::index()` load `Bundle::active()->with('courses')` (opsional: hanya tampil kalau ada bundle aktif).
- `checkout/choose.blade.php`: generik — terima `title`, `base`, `options`, `actionRoute` (halaman course tetap identik; test lulus).
- Penyesuaian `orderTitle()`:
  - `resources/views/checkout/finish.blade.php` (:24, :34, :126, :147 + daftar isi bundle)
  - `resources/views/checkout/index.blade.php` (:27)
  - `resources/views/invoices/pdf.blade.php` (:108 — baris "Bundle: {judul}" + daftar isi course)
  - `resources/views/admin/payment-verifications/index.blade.php` (:39, :82), `show.blade.php` (:44)
- `app/Http/Controllers/Admin/PaymentVerificationController.php` (~:59, :85): deskripsi/metadata ActivityLog pakai `orderTitle()`.

### 3.5 Admin CRUD Bundle

- **Permission baru `manage bundles`** → tambah di `database/seeders/RolesAndPermissionsSeeder.php` (blok daftar permission, ~:21-140); super-admin otomatis dapat.
- **Route** — grup terpisah (pola `admin/verifikasi-pembayaran` di `routes/web.php:81`, JANGAN ikut pipe besar grup admin):

```php
Route::middleware(['auth', 'permission:manage bundles'])
    ->prefix('admin/bundles')->name('admin.bundles.')
    ->group(function () { /* index, create, store, edit, update, destroy */ });
```

- **`app/Http/Controllers/Admin/BundleController.php` (baru)** — inline `$request->validate` (konvensi `Admin\UserController`), `ActivityLog::log('bundle_created'/'bundle_updated', ...)`, validasi: semua course_id → course published + catalog + `price > 0`; harga bundle > 0; slug unik.
- **View** `resources/views/admin/bundles/{index,create,edit}.blade.php` — pola `admin/users/index.blade.php` (`<x-app-layout>`, tabel, search, pagination); create/edit: input title/slug/description/price/is_active/requires_payment_verification + multi-select course (hanya yang berbayar+published, tampil harga masing-masing + total).

---

## 4. Mobile API

Lokasi: `routes/api.php` di dalam group `mobile.api.user` + `force.json` (referensi `routes/api.php:39,75`). Controller baru `app/Http/Controllers/Api/BundleApiController.php` (plain array, envelope standar — gaya `ShopApiController`).

```text
GET /api/mobile/bundles          -> list bundle aktif (paginasi, gaya transformCard)
GET /api/mobile/bundles/{slug}   -> detail bundle + isi course
```

- Envelope selalu `{status, message, data, meta}`.
- **Kartu list**: `id, slug, title, thumbnailUrl (course pertama), price, priceLabel, originalPriceLabel, savingsLabel, savingsPercent, coursesCount, isEnrolled? (opsional)`.
- **Detail**: field kartu + `description` + `courses[] {id, title, thumbnailUrl, price, priceLabel, lessonsCount}` + `meta.showPrice`.
- Filter: hanya `is_active` + seluruh course published & catalog. 404 untuk slug tidak aktif/tidak ditemukan (pola `assertVisible`).

---

## 5. Tests

### Laravel (baru)

| Test | Cakupan |
|---|---|
| `tests/Feature/BundleCheckoutTest.php` | halaman pilih metode bundle (200, judul, harga); store → order benar (`base/fee/amount/discount_amount`, `course_id` null, `bundle_id` terisi); potongan saat punya sebagian course; tolak saat sudah punya semua; tolak guest |
| `tests/Feature/BundlePaymentTest.php` | simulasi payload Midtrans settlement → SEMUA course enrolled; bundle dengan flag verifikasi → `awaiting_verification`, `approve()` → enrolled; notifikasi ganda → idempotent (tidak dobel enroll/invoice) |
| `tests/Feature/AdminBundleRouteTest.php` | 403 tanpa `manage bundles`, 200 setelah grant (pola `AdminRoutesPermissionTest`); store menolak bundle berisi course gratis |
| `tests/Feature/Api/MobileBundleApiTest.php` | auth `mobile.api.user`, envelope + pagination, hanya bundle aktif, detail isi course (pola `MobileCatalogApiTest`) |

- Daftarkan route admin bundle di `tests/Feature/Permissions/RouteMiddlewareCoverageTest.php`.
- **Regresi wajib lulus**: `CheckoutChooseTest`, `CheckoutStatusTest`, `MobileCatalogApiTest` (alur course tunggal tidak boleh berubah).

### Flutter (session lain — appendix, lihat §8)

Pola `test/features/catalog/catalog_bloc_test.dart` + `catalog_course_model_test.dart`: parse model bundle, bloc list & detail.

---

## 6. Dokumentasi

- `docs/system/ERD.md`: entity BUNDLES + BUNDLE_COURSES di §6 (Sertifikat/Pembayaran), ORDERS + `bundle_id`/`discount_amount`, `course_id` nullable, relasi `BUNDLES ||--o{ ORDERS`, baris "Katalog Tabel", +1 aturan integritas level aplikasi.
- `docs/system/COURSE-COMMERCE-ROADMAP.md`: catatan di §8 bahwa bundle diangkat dari "Ditunda".
- `CLAUDE.md`: satu baris hierarki data (`Bundle → bundle_courses → Course`).

---

## 7. Tahapan Eksekusi

> **Scope session ini (bila dijalankan): Fase A s/d C. Flutter = FASE D, SESSION LAIN.**

### Fase A — Laravel inti
1. 3 migration → `Bundle` model → ubah `Order` model.
2. `OrderService::checkoutBundle()` + helper `grantAccess()`.
3. Route + `BundleController` (web publik) + `CheckoutController::chooseBundle/storeBundle` + view `shop/bundle.blade.php` + penyesuaian view `orderTitle()` + `MidtransGateway` branch.
4. Section bundle di `shop.index`.
5. Test `BundleCheckoutTest` + `BundlePaymentTest`. **Gate: `./vendor/bin/pint` + test lulus.**

### Fase B — Admin
1. Permission `manage bundles` di seeder → route group → `Admin\BundleController` → view admin.
2. Test `AdminBundleRouteTest` + update `RouteMiddlewareCoverageTest`.

### Fase C — Mobile API
1. `BundleApiController` + route api → `MobileBundleApiTest`.
2. Full test suite.

### Fase D — Flutter (**SESSION LAIN, tidak sekarang**)
Appendix judul file (berdasar riset repo `F:\Aplikasi Mobile Bass Training\new-main`):
1. `lib/src/core/config/constants/api_endpoints.dart:86-89` → tambah `bundles`, `bundles/{slug}`.
2. Feature baru `lib/src/features/bundle/` (entity, model `fromJson` manual — tanpa Freezed, remote DS bisa gabung di catalog DS, repository, bloc list+detail).
3. Section "Paket Kursus" di atas grid `lib/src/features/catalog/presentation/screens/catalog_screen.dart:104-136` → tap → `BundleDetailScreen` (daftar course, harga coret, harga bundle, tombol **"Beli di Website"** → `Uri.https('lms.basstrainingacademy.com', '/bundles/$slug')` — pola `catalog_detail_screen.dart:52-63`).
4. Route di `lib/src/core/config/constants/app_routes.dart:22-38` + `lib/src/core/routes/app_router.dart:191-200`; DI `lib/src/core/di/modules/` + `injector.dart:48`.
5. Tanpa fallback dummy (API gagal → section disembunyikan).
6. Test: model parse + bloc (pola `catalog_bloc_test.dart`).

---

## 8. Catatan Edge (Ditetapkan)

- Pembeli bayar bundle, lalu membeli satu course terpisah sebelum webhook masuk → `syncWithoutDetaching` menanganinya tanpa error (ia bayar penuh — trade-off diterima).
- Course dihapus oleh admin → `bundle_courses` cascade; harga bundle jadi kewajiban admin (validasi di form edit).
- Harga sangat kecil setelah potongan: `base <= 0` ditolak; tidak ada batas minimum lain (Midtrans menerima > 0).
- Order pending bundle yang dibatalkan/kedaluwarsa tidak memengaruhi apa pun (tidak ada limit kuota di bundle).
- Kupon (kalau menyusul): potongan kupon dihitung dari harga bundle setelah potongan kepemilikan — lihat `docs/future/COUPON-PLAN.md`.
