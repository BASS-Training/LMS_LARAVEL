# Runbook Go-Live Pembayaran — LMS BASS

> Dokumen ini dipakai untuk **menaikkan fitur pembayaran (Midtrans) ke server produksi**.
> Dibagi jelas: **Bagian A** dikerjakan **rekan yang men-deploy** (di server), **Bagian B** dikerjakan
> **pemilik akun Midtrans** (di dashboard). Kerjakan **berurutan A → B → C**.

---

## 0. Ringkasan situasi

| Hal | Status |
|-----|--------|
| Website produksi | ✅ Live: `https://lms.basstrainingacademy.com` |
| Akun Midtrans | ✅ **Produksi aktif** (Merchant ID `G103556634`) |
| Kode fitur pembayaran | ✅ Ada di GitHub, branch **`payment`** (`BASS-Training/LMS_LARAVEL`) — **belum di server** |
| Migrasi database baru | ⚠️ 11 migrasi, **wajib dijalankan** saat deploy |
| Environment saat ini di server | Belum ada konfigurasi Midtrans produksi |

> ⚠️ **Penting soal branch:** branch `payment` **bukan hanya fitur pembayaran**. Dia ~18 commit di depan `main`
> dan ikut membawa fitur lain (sertifikat API mobile, notifikasi, antrian penilaian, katalog API, prasyarat, zoom dokumen).
> **Men-deploy branch ini = merilis semua fitur itu sekaligus.** Pastikan itu memang diinginkan sebelum lanjut.

---

## BAGIAN A — Dikerjakan REKAN yang men-deploy (di server)

### A0. Sebelum mulai (WAJIB)
1. **Backup database produksi dulu.** Migrasi mengubah struktur DB live.
   ```bash
   mysqldump -u <user> -p <nama_db> > backup-sebelum-payment-$(date +%F).sql
   ```
2. Pastikan ada akses SSH ke server, atau fitur Git hPanel.
3. Idealnya pasang **maintenance mode** sebentar saat migrasi:
   `php artisan down` (dan `php artisan up` setelah selesai).

### A1. Ambil kode ke server
Pilih salah satu:

**Opsi 1 — rekomendasi (rapi): merge `payment` → `main` lebih dulu**, lalu server menarik `main`.
```bash
# di server (branch produksi = main):
git fetch origin
git checkout main
git merge origin/payment        # atau lakukan merge via Pull Request di GitHub
git pull origin main
```

**Opsi 2 — cepat: server langsung pakai branch `payment`**
```bash
git fetch origin
git checkout payment
git pull origin payment
```

### A2. Dependency PHP
```bash
composer install --no-dev --optimize-autoloader
```
> ⚠️ Ada paket **baru**: `barryvdh/laravel-dompdf` (invoice PDF). Aplikasi juga memakai `phpoffice/phpspreadsheet`
> yang **butuh ekstensi PHP `gd`**. Jika `composer install` gagal karena platform requirement,
> **aktifkan `ext-gd`** di PHP server (lebih baik daripada `--ignore-platform-req=ext-gd`, karena gd dipakai saat runtime).

### A3. Jalankan migrasi database
```bash
php artisan migrate --force
```
10 migrasi baru yang akan jalan (semuanya **bersifat menambah**, tidak menghapus data lama):
- `add_shop_fields_to_courses_table` — kolom `visibility`, `price`, `short_description`
- `create_orders_table` — tabel pesanan (snapshot harga)
- `add_requires_payment_verification_to_courses`
- `add_verification_fields_to_orders`
- `widen_status_on_orders`
- `add_fee_breakdown_to_orders` — kolom `base_amount`, `fee_amount` (biaya layanan)
- `add_payment_method_key_to_orders` — kolom `payment_method_key` (metode yang dipilih pembeli; dasar biaya layanan per metode)
- (+ migrasi fitur lain yang ikut di branch ini)

### A4. Konfigurasi `.env` di server
Tambahkan / ubah baris berikut:
```dotenv
MIDTRANS_IS_PRODUCTION=true
MIDTRANS_SERVER_KEY=<<ISI DARI PEMILIK AKUN — RAHASIA, jangan ditulis di repo/chat publik>>
MIDTRANS_CLIENT_KEY=Mid-client-aPe2ZhnunP8Vxh8D

# (opsional) biaya layanan gabungan — hanya dipakai kalau mode per-metode dimatikan
# MIDTRANS_FEE_ENABLED=true
# MIDTRANS_FEE_PERCENT=2
# MIDTRANS_FEE_FIXED=2500

# (opsional) matikan pemilihan metode per-transaksi → balik ke tarif gabungan 1 klik
# MIDTRANS_METHODS_ENABLED=true
```
> 💡 **Biaya layanan kini PER METODE** (transparan): pembeli memilih metode sebelum
> bayar dan melihat biaya persis metode itu (transfer bank flat, QRIS 0,7%, dst).
> Tarif tiap metode diatur di `config/midtrans.php` blok **`methods`** — angka default
> di sana adalah tarif umum Midtrans; **sesuaikan dengan MDR asli akunmu** (dashboard
> Midtrans). Untuk menonaktifkan fitur ini dan kembali ke satu tarif rata, set
> `MIDTRANS_METHODS_ENABLED=false`.
> 🔑 **Server Key = rahasia.** Terima dari pemilik akun lewat jalur privat (bukan chat grup / bukan di dalam repo).
> Client Key & Merchant ID **tidak** rahasia (Client Key memang dipakai di sisi browser).

### A5. Bersihkan & build ulang
```bash
php artisan config:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link # jika symlink storage belum ada
php artisan optimize:clear
php artisan optimize     

npm ci
npm run build                 # kompilasi ulang aset (halaman checkout pakai Tailwind/Alpine)
```

### A6. Pastikan queue worker jalan
Notifikasi email memakai queue default (`QUEUE_CONNECTION=database`). Jika `PAYMENT_ASYNC_ENABLED=true`, pembuatan Snap, webhook, dan rekonsiliasi pembayaran memakai queue `payment_database` / `payments`.

Jalankan migrasi sebelum mengaktifkan fitur ini. Pastikan queue pembayaran memakai koneksi database yang sama dengan order. Jalankan worker permanen (Supervisor / systemd), contoh:
```bash
php artisan queue:work database --queue=default --tries=3 --timeout=60
php artisan queue:work payment_database --queue=payments --tries=5 --timeout=40
```
Mulai dengan dua proses worker `payments`, lalu sesuaikan dengan antrean dan kapasitas database. `--timeout` harus lebih kecil dari `retry_after` (90 detik). Aktifkan `PAYMENT_ASYNC_ENABLED=true` dan jalankan `php artisan config:cache` hanya setelah migrasi serta worker siap. Pantau jumlah job menunggu dan `failed_jobs`; pesanan dengan `snap_status=needs_review` perlu diperiksa terhadap Midtrans sebelum pengguna membuat tagihan baru. Webhook mengirim 200 setelah receipt dan job tersimpan dalam transaksi database; jika penyimpanan gagal, server mengirim 503 agar Midtrans dapat mengirim ulang.
Jika sebelumnya sudah ada worker, **restart** agar memuat kode baru:
```bash
php artisan queue:restart
```

### A7. Selesai & cek cepat
```bash
php artisan up                # matikan maintenance mode (jika tadi 'down')
```
Cek di browser:
- `https://lms.basstrainingacademy.com/katalog` → etalase kursus muncul.
- Halaman detail kursus berbayar menampilkan **rincian harga + biaya layanan**.

---

## BAGIAN B — Dikerjakan PEMILIK AKUN (di dashboard Midtrans)

Login `dashboard.midtrans.com`, pastikan **Environment = Production** (pojok kiri atas).

### B1. Set Payment Notification URL (WAJIB — ini jalur resmi enroll)
**Settings → Snap Preferences** (atau **Configuration**), isi kolom **Payment Notification URL**:
```
https://lms.basstrainingacademy.com/webhooks/midtrans
```
> Tanpa ini, order bisa nyangkut `pending` walau uang sudah masuk.

### B2. (Opsional) Redirect / Finish URL
Jika ada kolom Finish/Unfinish/Error URL, arahkan ke halaman selesai checkout web, mis.
`https://lms.basstrainingacademy.com/pesanan` (opsional — Snap punya default).

### B3. Aktifkan metode pembayaran
**Payment Methods** → aktifkan yang ingin diterima (QRIS, Virtual Account, e-wallet, kartu, dll).
> ⚠️ **Selaraskan dengan config:** metode yang diaktifkan di dashboard sebaiknya sama dengan yang
> `enabled=true` di `config/midtrans.php` blok `methods`. Karena biaya kini dihitung **per metode**,
> pembeli melihat & menyetujui biaya persis tiap metode (kartu pun tidak nombok — biayanya
> ditampilkan apa adanya). Pastikan angka `percent`/`fixed` tiap metode cocok dengan **MDR asli** akunmu.

### B4. Serahkan Server Key ke rekan (Bagian A4) lewat jalur privat.

---

## BAGIAN C — Uji transaksi NYATA (setelah A & B selesai)

1. Pakai akun **peserta biasa** (bukan admin/instruktur — mereka diblok beli course sendiri).
2. Beli **1 kursus nominal kecil** dari katalog → bayar **beneran** (mis. QRIS).
3. Pastikan:
   - Status order jadi **paid** dan peserta **otomatis ter-enroll** (course muncul di "Kursus Saya" web & mobile).
   - **Invoice PDF** bisa diunduh dari Riwayat Pembelian.
   - Notifikasi Midtrans masuk (cek **Transactions** di dashboard = "settlement").
4. Uang masuk ke **Balance** Midtrans, bisa dicairkan lewat **Withdrawal** (T+ sesuai kebijakan Midtrans).

---

## BAGIAN D — Catatan & risiko

- **Backup DB sebelum migrasi** — sudah ditekankan di A0.
- **Branch `payment` membawa fitur lain** — deploy = rilis semua sekaligus (lihat catatan di bagian 0).
- **Refund/pembatalan** dilakukan **manual** dari dashboard Midtrans (belum ada tombol refund di aplikasi).
- **Belum ada** cron rekonsiliasi order `pending` (jaring pengaman bila webhook meleset). Di lokal tertolong reconcile
  saat user kembali ke halaman selesai; di produksi webhook = jalur utama. Disarankan dibuat sebelum trafik tinggi.
- **Mobile tidak menjual apa pun** (tanpa tombol beli) — kebijakan anti-steering Google Play. Pembelian hanya di web.

---

## Lampiran — Fakta teknis (untuk rekan)

| Item | Nilai |
|------|-------|
| Repo | `github.com/BASS-Training/LMS_LARAVEL` |
| Branch berisi fitur | `payment` |
| Merchant ID | `G103556634` (tidak rahasia) |
| Client Key (produksi) | `Mid-client-aPe2ZhnunP8Vxh8D` (tidak rahasia) |
| Server Key (produksi) | **RAHASIA** — dari pemilik akun, jangan ditulis di sini |
| Route webhook | `POST /webhooks/midtrans` → `CheckoutController@notification` (CSRF-exempt, verifikasi signature sha512, idempotent) |
| Config biaya layanan | `config/midtrans.php` blok `methods` (per metode) + `fee` (fallback gabungan) |
| Kalkulator biaya | `app/Services/Payment/ServiceFee.php` (`forMethod` / `options` / `cheapest`) |
| Kunci metode di Snap | `enabled_payments` diset dari metode pilihan (`MidtransGateway::createSnapTransaction`) |
| Framework | Laravel 12, PHP 8.x, MySQL, queue+cache+session = database |

---

*Dibuat 2026-08-19. Jika ada pertanyaan teknis saat deploy, hubungi pemilik proyek.*
