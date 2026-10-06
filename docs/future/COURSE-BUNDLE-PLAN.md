# Rencana: Course Bundle (Harga Per-Course + Harga Bundle)

Status: **LARAVEL MVP DIIMPLEMENTASIKAN — Flutter belum diimplementasikan.**
Web catalog, checkout Midtrans, kupon global, enrollment, refund, admin CRUD, dan mobile API tersedia. Bagian Flutter tetap menjadi pekerjaan session terpisah.

Referensi riset: `docs/system/COURSE-COMMERCE-ROADMAP.md` (bundle ada di §8 "Fitur yang Ditunda" dan Fase 4 — diangkat lebih awal karena fondasi order/payment sudah stabil).

---

## 1. Aturan Bisnis (Sudah Diputuskan)

1. **Potongan untuk course yang sudah dimiliki.**
   `harga bayar = max(harga bundle − Σ harga per course yang sudah dimiliki, 0)`.
   - "Sudah dimiliki" = sudah enrolled (`course_user`) **ATAU** mengelola course tersebut (instruktur/super-admin).
   - Potongan kepemilikan dibatasi maksimal sebesar harga bundle dan dicatat di `orders.bundle_discount_amount`.
   - Hasil ≤ 0 → order DITOLAK. Pesan "Anda sudah memiliki seluruh isi bundle" hanya dipakai bila semua course memang sudah dimiliki; jika belum, gunakan pesan bahwa tidak ada nominal yang dapat ditagihkan setelah potongan kepemilikan.
   - Harga personal (setelah potongan) ditampilkan di halaman checkout, bukan di halaman publik bundle (publik tetap lihat harga bundle normal).
2. **Semua course dalam bundle wajib berbayar** (`price > 0`), published, dan visibility catalog. Divalidasi saat admin create/update bundle.
3. **Verifikasi pembayaran PER BUNDLE, bukan per course.**
   Kolom `bundles.requires_payment_verification` (admin yang menentukan per bundle). Order bundle memakai flag ini — bukan warisan dari salah satu course.
4. **Tampil di mobile.** Mobile tetap TANPA in-app purchase (arsitektur yang ada): mobile hanya menampilkan bundle, pembelian lewat tombol **Website** → `https://lms.basstrainingacademy.com/bundles/{slug}`.
5. **Harga bundle diisi absolut oleh admin** (bukan persen). Total harga per-course dihitung sistem dari `Σ courses.price` untuk tampilan "Hemat Rp X (Y%)".
6. **Kupon bundle untuk MVP hanya menerima kupon global.** Kupon yang dibatasi ke course tertentu ditolak pada checkout bundle karena cakupannya ambigu untuk order multi-course.
7. **Urutan kalkulasi dan snapshot tidak boleh ambigu.**
   `harga bundle → potongan kepemilikan → diskon kupon global → biaya layanan`.
   - `bundle_discount_amount` = potongan kepemilikan.
   - `discount_amount` = diskon kupon saja.
   - `base_amount` = harga akhir setelah kedua potongan, sebelum biaya layanan.
   - `amount` = `base_amount + fee_amount`.
8. **Refund berlaku untuk satu order bundle secara utuh**, bukan per course:
   - Progres refund = progres tertinggi dari seluruh course dalam bundle.
   - Sertifikat pada salah satu course memblokir refund.
   - Refund berhasil mencabut semua enrollment yang sumbernya order bundle tersebut.
   - Enrollment dengan `has_independent_access = true` tetap dipertahankan dan hanya dilepas dari `order_id`.

---

## 2. Skema Database

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

bundle_course
- bundle_id -> bundles.id (cascadeOnDelete)
- course_id -> courses.id (cascadeOnDelete)
- sort_order unsigned int default 0
- unique(bundle_id, course_id)

orders (ALTER)
- course_id -> nullable()             -- sebelumnya wajib
- + bundle_id -> bundles.id nullable (index)
- + bundle_discount_amount unsigned bigint default 0

order_items
- id
- order_id -> orders.id (cascadeOnDelete)
- course_id -> courses.id nullable (nullOnDelete)
- course_title string                 -- snapshot saat checkout
- sort_order unsigned int default 0
- unique(order_id, course_id)
```

- Constraint "tepat satu dari `course_id` / `bundle_id`" dijaga di **level aplikasi** (`OrderService`), sesuai konvensi repo.
- `orders.discount_amount` dan `orders.coupon_code` **sudah dibuat** oleh migration kupon. Migration bundle tidak boleh membuat atau mengubah makna kedua kolom tersebut.
- `discount_amount` tetap berarti diskon kupon; `bundle_discount_amount` hanya berarti kredit atas course yang sudah dimiliki. Pemisahan ini diperlukan agar invoice, refund, audit, dan rekonsiliasi tetap dapat menjelaskan setiap potongan.
- `orders.course_id` yang nullable aman: satu-satunya query `where('course_id', ...)` di orders ada di `app/Services/Payment/OrderService.php` (dedupe order pending) — NULL tidak pernah match, jadi tidak error.
- `order_items` menjadi snapshot membership bundle dan ledger sumber akses berbayar. Fulfillment/refund tidak membaca membership bundle terkini, sehingga edit bundle setelah checkout tidak mengubah hak pembeli.

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
  - Tambah `bundle_id`, `bundle_discount_amount` ke `$fillable` + `$casts`; `discount_amount` sudah tersedia dari fitur kupon.
  - Relasi `bundle()` belongsTo.
  - **Accessor `orderTitle(): string`** → judul course ATAU judul bundle. Ini meminimalkan churn di view yang selama ini asumsi `$order->course` satu course.
  - Helper `isBundleOrder(): bool`.
  - `originalBaseAmount` untuk order bundle = `base_amount + discount_amount + bundle_discount_amount`.

### 3.2 OrderService (`app/Services/Payment/OrderService.php`)

- **Method baru `checkoutBundle(Bundle $bundle, User $user, ?string $methodKey = null, ?string $couponCode = null): Order`** — pola mirror dari `checkout()`:
  - Guard: gateway aktif; bundle `is_active`; semua course valid (published+catalog+berbayar); user bukan pengelola seluruh isi bundle.
  - Hitung `owned` (enrolled OR managed) → `bundleDiscount = min(Σ owned prices, bundle->price)` → `couponBase = bundle->price - bundleDiscount` → jika `couponBase <= 0` throw `RuntimeException` dengan pesan sesuai kondisi kepemilikan.
  - Jika ada kode kupon, hanya izinkan kupon `applies_to_all_courses = true`; hitung diskon dari `couponBase`, bukan dari harga course individual atau harga bundle sebelum potongan kepemilikan.
  - Hitung `base = couponBase - couponDiscount`; hasil `base <= 0` tetap ditolak sesuai aturan kupon saat ini.
  - Fee: `ServiceFee::forMethod($base, $methodKey)` — **tanpa perubahan**.
  - Reuse order pending yang di-key `bundle_id` + `payment_method_key` + `coupon_code` + seluruh nominal snapshot.
  - `Order::create([... 'course_id' => null, 'bundle_id' => ..., 'bundle_discount_amount' => bundleDiscount, 'discount_amount' => couponDiscount, 'base_amount' => base, 'fee_amount' => fee, 'amount' => base + fee ...])` dalam `DB::transaction`, lalu Snap.
  - Reservasi `coupon_redemptions` dibuat dalam transaksi yang sama seperti checkout course tunggal dan dilepas mengikuti lifecycle order unpaid yang sudah berlaku.
- **Ekstrak helper `grantAccess(Order $order)`** dari `confirmPayment()` (baris ~222-243) dan `approve()` (baris ~269):
  - Verifikasi: `$order->isBundleOrder() ? $order->bundle->requiresPaymentVerification() : $order->course->requiresPaymentVerification()`.
  - Enrollment: bundle → **loop** `bundle->courses` → `syncWithoutDetaching([$user_id])` per course (idempotent); course tunggal → perilaku lama persis.
  - Dipakai juga oleh `approve()`.
- `abandon()` tidak berubah (tidak memakai `course_id`).

### 3.3 Refund dan Sumber Akses

- **`RefundService::eligibility()`** harus branch berdasarkan `isBundleOrder()`:
  - load seluruh `bundle.courses`;
  - hitung progres tiap course dan gunakan nilai tertinggi;
  - blokir bila sertifikat telah diterbitkan pada salah satu course;
  - pesan eligibility menyebut bundle dan, bila relevan, course yang memblokir.
- **`RefundService::approve()`** mengulang pemeriksaan sertifikat seluruh course untuk mencegah race antara request dan approval.
- **`RefundService::complete()`** untuk bundle mencari seluruh row `course_user` dengan `user_id` dan `order_id` yang sesuai:
  - `has_independent_access = false` → hapus enrollment;
  - `has_independent_access = true` → pertahankan enrollment dan set `order_id = null`.
- `RefundStatusNotification`, daftar/detail refund admin, dan halaman pengajuan refund menggunakan `orderTitle()` serta daftar course bundle; tidak boleh dereference `order->course` tanpa branch.
- Refund tetap satu record dan satu nominal penuh per order bundle. Partial refund per course tidak termasuk MVP.

### 3.4 Checkout Web

Route (ikut gaya `routes/web.php` blok katalog/checkout, auth via constructor `CheckoutController`):

```text
GET  /bundles/{slug}            -> BundleController@show        name: bundles.show     (PUBLIK)
GET  /bundles/{slug}/beli       -> CheckoutController@chooseBundle   name: checkout.bundle-choose   (auth)
POST /bundles/{slug}/beli       -> CheckoutController@storeBundle    name: checkout.bundle-store    (auth)
```

- `CheckoutController::chooseBundle/storeBundle` — duplikat tipis dari `choose/store` (method TERPISAH supaya test course lama tidak tersentuh), termasuk apply/remove kupon dengan session key khusus bundle.
- `finish`, `changeMethod`, `index`, `invoice`: branch bundle.
  - `resources/views/checkout/finish.blade.php:7` (`needsVerif`) → `order->course ? course->requiresPaymentVerification() : order->bundle->requiresPaymentVerification()`.
  - Judul di semua tempat → `orderTitle()`.
  - `changeMethod` (baris ~173-176): order bundle → kembali ke `checkout.bundle-choose`.
- **`app/Services/Payment/MidtransGateway.php`** (~baris 37, 49-50): Snap `item_details` branch — bundle → `id = bundle-{id}`, `name = bundle title`.

### 3.5 View Baru / Penyesuaian

- **`resources/views/shop/bundle.blade.php` (baru)**: daftar course + harga masing-masing, total per-course dicoret, harga bundle mencolok, "Hemat Rp X (Y%)", deskripsi, CTA Beli.
- **Section "Paket Kursus"** di atas grid `resources/views/shop/index.blade.php` — `ShopController::index()` load `Bundle::active()->with('courses')` (opsional: hanya tampil kalau ada bundle aktif).
- `checkout/choose.blade.php`: generik — terima `title`, `base`, `options`, `actionRoute`, route apply/remove kupon, dan breakdown `bundle_discount_amount`/`discount_amount` (halaman course tetap identik; test lulus).
- Penyesuaian `orderTitle()`:
  - `resources/views/checkout/finish.blade.php` (:24, :34, :126, :147 + daftar isi bundle)
  - `resources/views/checkout/index.blade.php` (:27)
  - `resources/views/invoices/pdf.blade.php` (:108 — baris "Bundle: {judul}" + daftar isi course + potongan kepemilikan dan kupon sebagai baris terpisah)
  - `resources/views/admin/payment-verifications/index.blade.php` (:39, :82), `show.blade.php` (:44)
- `app/Http/Controllers/Admin/PaymentVerificationController.php` (~:59, :85): deskripsi/metadata ActivityLog pakai `orderTitle()`.

### 3.6 Admin CRUD Bundle

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

Lokasi: `routes/api.php` di dalam group katalog `mobile.api.user:optional` + `throttle:mobile-api` + `force.json` (mengikuti route `/mobile/catalog`). Controller baru `app/Http/Controllers/Api/BundleApiController.php` (plain array, envelope standar — gaya `ShopApiController`).

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
| `tests/Feature/BundleCheckoutTest.php` | halaman pilih metode bundle (200, judul, harga); store → order benar (`base/fee/amount/bundle_discount_amount`, `course_id` null, `bundle_id` terisi); potongan saat punya sebagian course; pesan benar saat hasil Rp0; tolak guest |
| `tests/Feature/BundlePaymentTest.php` | simulasi payload Midtrans settlement → SEMUA course enrolled; bundle dengan flag verifikasi → `awaiting_verification`, `approve()` → enrolled; notifikasi ganda → idempotent (tidak dobel enroll/invoice) |
| `tests/Feature/AdminBundleRouteTest.php` | 403 tanpa `manage bundles`, 200 setelah grant (pola `AdminRoutesPermissionTest`); store menolak bundle berisi course gratis |
| `tests/Feature/Api/MobileBundleApiTest.php` | optional auth `mobile.api.user:optional`, akses guest dan authenticated, envelope + pagination, hanya bundle aktif, detail isi course (pola `MobileCatalogApiTest`) |
| `tests/Feature/BundleCouponTest.php` | kupon global dihitung setelah potongan kepemilikan; kupon course-specific ditolak; reservation/release sama dengan order course tunggal; invoice memisahkan kedua potongan |
| `tests/Feature/BundleRefundTest.php` | progres tertinggi dan sertifikat salah satu course memblokir; refund penuh mencabut seluruh enrollment dari order; akses independen dipertahankan |

- Daftarkan route admin bundle di `tests/Feature/Permissions/RouteMiddlewareCoverageTest.php`.
- **Regresi wajib lulus**: `CheckoutChooseTest`, `CheckoutStatusTest`, `MobileCatalogApiTest` (alur course tunggal tidak boleh berubah).

### Flutter (session lain — appendix, lihat §8)

Pola `test/features/catalog/catalog_bloc_test.dart` + `catalog_course_model_test.dart`: parse model bundle, bloc list & detail.

---

## 6. Dokumentasi

- `docs/system/ERD.md`: entity BUNDLES + BUNDLE_COURSES di §6 (Sertifikat/Pembayaran), ORDERS + `bundle_id`/`bundle_discount_amount`, `course_id` nullable, relasi `BUNDLES ||--o{ ORDERS`, baris "Katalog Tabel", +1 aturan integritas level aplikasi.
- `docs/system/COURSE-COMMERCE-ROADMAP.md`: catatan di §8 bahwa bundle diangkat dari "Ditunda".
- `CLAUDE.md`: satu baris hierarki data (`Bundle → bundle_course → Course`).

---

## 7. Tahapan Eksekusi

> **Scope session ini (bila dijalankan): Fase A s/d C. Flutter = FASE D, SESSION LAIN.**

### Fase A — Laravel inti (**SELESAI**)
1. 3 migration → `Bundle` model → ubah `Order` model.
2. `OrderService::checkoutBundle()` + helper `grantAccess()` + integrasi kupon global.
3. Route + `BundleController` (web publik) + `CheckoutController::chooseBundle/storeBundle` + view `shop/bundle.blade.php` + penyesuaian view `orderTitle()` + `MidtransGateway` branch.
4. Section bundle di `shop.index`.
5. Adaptasi `RefundService`, notifikasi, invoice, dan payment verification agar order bundle aman.
6. Test `BundleCheckoutTest` + `BundlePaymentTest` + `BundleCouponTest` + `BundleRefundTest`. **Gate: `./vendor/bin/pint` + test lulus.**

### Fase B — Admin (**SELESAI**)
1. Permission `manage bundles` di seeder → route group → `Admin\BundleController` → view admin.
2. Test `AdminBundleRouteTest` + update `RouteMiddlewareCoverageTest`.

### Fase C — Mobile API (**SELESAI**)
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
- Course dihapus oleh admin → `bundle_course` cascade; bundle otomatis hilang dari katalog sampai kembali memiliki seluruh course yang valid.
- Harga setelah potongan kepemilikan `<= 0` ditolak. Pesan membedakan "seluruh isi sudah dimiliki" dari kasus nilai course yang dimiliki telah menutup harga bundle.
- Kupon course-specific selalu ditolak untuk order bundle pada MVP; kupon global dihitung setelah potongan kepemilikan.
- Order pending bundle yang dibatalkan/kedaluwarsa tidak memengaruhi apa pun (tidak ada limit kuota di bundle).
- Order pending bundle berkupon tetap mengikuti reservasi dan pelepasan `coupon_redemptions` yang sama dengan checkout course tunggal.
- Refund bundle selalu penuh untuk satu order; partial refund per course tidak termasuk MVP.
