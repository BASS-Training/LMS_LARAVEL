# Rencana — Perluas Email Notification di LMS BASS

> Status: **RENCANA (belum diimplementasi)**
> Prinsip: email hanya untuk **transaksional & milestone** (sekali kirim, penting, frekuensi rendah).
> Percakapan/real-time tetap pakai notifikasi in-app (`database`) yang sudah ada.

---

## 1. Kondisi sekarang

| Kanal | Penggunaan saat ini |
|-------|---------------------|
| **Email (SMTP)** | Hanya OTP (`OtpService` → `app/Mail/OtpMail.php`) & link reset password (`CustomResetPasswordNotification`) |
| **Database (in-app)** | Chat, 5 observer (diskusi, essay/studi kasus/dokumen), export selesai, "materi baru" |

Temuan kunci dari riset kode:

- Event finansial (**order lunas / approve / reject**) → **nol notifikasi**, hanya `Log::info` (`app/Services/Payment/OrderService.php:204,253,287`).
- **Invoice PDF** hanya bisa diunduh manual (`CheckoutController.php:129`, route `GET /pesanan/{order}/invoice`).
- **Sertifikat** → notifikasi ditulis mati: `// $user->notify(new CertificateGeneratedNotification(...))` (`ProgressController.php:289`) — class-nya tidak pernah dibuat.
- **AVPN approved/rejected** → hanya `ActivityLog` (`Admin/ParticipantController.php:78,106`), tanpa notifikasi padahal menahan akses.
- Tidak ada preferensi notifikasi per user, tidak ada template email selain `otp.blade.php` & `reset-password.blade.php`.

---

## 2. Prioritas implementasi

### Fase 1 — Wajib (nilai tinggi, volume rendah)

| # | Event | Email berisi | Titik kode (hook) |
|---|-------|--------------|-------------------|
| 1 | Order **lunas** (webhook Midtrans / approve manual) | Konfirmasi pembayaran + **lampiran invoice PDF** (dompdf sudah ada) | `OrderService::confirmPayment` (app/Services/Payment/OrderService.php:204) |
| 2 | Order **ditolak / diapprove** admin | Alasan penolakan / status approve | `OrderService::reject` (:287), `approve` (:253) |
| 3 | **Sertifikat terbit** | Link sertifikat + kode verifikasi publik | 4 lokasi generate → buat `CertificateGeneratedNotification` (sekarang cuma komentar di `ProgressController.php:289`) |
| 4 | Status **AVPN approved / rejected** | Hasil verifikasi + langkah berikutnya | `Admin/ParticipantController.php:78,106` (+ bulk `:229,:278`) |

### Fase 2 — Layak (medium)

| # | Event | Cara |
|---|-------|------|
| 5 | Hasil penilaian (essay/studi kasus/dokumen) | Tambah `'mail'` ke `MobileNotification` yang sudah dipakai 5 observer (`EssaySubmissionObserver.php:50`, dst.) — **khusus event graded/passed/failed ke peserta**, bukan "tugas baru" ke instruktur |
| 6 | Export peserta selesai | Tambah `'mail'` + `toMail()` di `ExportCompletedNotification` (sudah `ShouldQueue`); file export di disk **privat** → wajib **attachment** atau **signed URL** (link biasa tidak bisa dibuka dari inbox) |
| 7 | Pengingat jadwal (Zoom/materi terjadwal) H-1 & H-0 | Scheduler baru di `routes/console.php` (sekarang hanya `certificates:cleanup-downloads`), query `Content.scheduled_start` / agenda Zoom |

### Fase 3 — Jangan di-email (keputusan eksplisit)

- Diskusi baru / balasan diskusi → real-time, volume tinggi, risiko spam.
- Hasil quiz → sudah tampil langsung di sesi yang sama (`QuizController::submitAttempt`).
- Setiap upload "materi baru" → terlalu sering; cukup in-app.

---

## 3. Desain teknis

### 3.1 Pola notifikasi
- Semua Notification **`ShouldQueue`** (queue Redis + Supervisor sudah jalan di prod) — SMTP tidak menahan request user.
- Tambah **toggle preferensi** per user (mis. kolom `users.email_notifications` JSON atau tabel `notification_preferences`): default ON untuk Fase 1 (transaksional), default OFF untuk Fase 2 kecuali penilaian.
- Ulangi event finansial harus **idempotent** (jangan kirim 2× kalau webhook dipanggil ulang — `confirmPayment` sudah dicek statusnya, pertahankan guard itu).

### 3.2 Template
- Buat layout blade bersama `resources/views/emails/layout.blade.php` (branding BASS, warna sesuai `Guidelines/BASS_LMS_Color_System.md`), lalu 1 view per event:
  - `emails/order-paid.blade.php` (+ attachment invoice dari `invoices.pdf` yang sudah ada)
  - `emails/order-status.blade.php` (approve/reject)
  - `emails/certificate-issued.blade.php`
  - `emails/avpn-result.blade.php`
  - `emails/grade-result.blade.php` (Fase 2)
  - `emails/export-ready.blade.php` (Fase 2)
- Dari: `MAIL_FROM_ADDRESS` / `MAIL_FROM_NAME` (sudah ada di `.env`).

### 3.3 Invoice sebagai attachment
- `CheckoutController::invoice` sudah merender `invoices.pdf` (`Pdf::loadView('invoices.pdf')`) — pindahkan logika render ke **service/method bersama** (`InvoiceService::render(order)`) supaya dipakai 2 tempat: route unduh + lampiran email.
- Attachment hanya untuk order lunas; format nama: `INV-{nomor}.pdf`.

### 3.4 Keamanan & deliverability
- Link di email (sertifikat, signed URL export) harus **signed/berbatas waktu**, tanpa membocorkan data privat.
- Semua log kirim email (succes/fail) → `Log::info/warning` supaya mudah dicek.
- SMTP Gmail: batas harian ~500–1000 — cukup untuk Fase 1–2 (volume rendah); kalau nanti kirim bulk/kampanye → pindah ke Resend/Mailgun/Postmark (tinggal ganti `MAIL_*`, tidak ada perubahan kode).

---

## 4. Urutan implementasi (Fase 1)

1. Buat `InvoiceService::render()` — refactor dari `CheckoutController::invoice` (tanpa ubah perilaku route).
2. Buat `CertificateGeneratedNotification` (yang tadi cuma komentar) + view email.
3. Buat notification class: `OrderPaidNotification`, `OrderStatusNotification`, `AvpnResultNotification` — semua `ShouldQueue`, channel `['mail', 'database']` (database sekalian supaya ada jejak in-app).
4. Sisipkan `notify()` di 4 titik hook (OrderService ×3, ParticipantController AVPN ×3 termasuk bulk, 4 lokasi generate sertifikat → cukup 1 helper `Certificate::notifyIssued()`).
5. Layout email + 4 view template.
6. Seeder/preference default: kolom preferensi (jika fase ini sudah diadopsi) atau lewati dulu (Fase 1 default ON).
7. Uji lokal: `MAIL_MAILER=log` → cek `storage/logs/laravel.log`, 1 test per event.

### Estimasi sentuhan
- Baru: ~6 notification class, ~6 view, 1 service, 1 helper.
- Ubah: `OrderService` (3 method), `ParticipantController` (approve/reject/bulk), 4 file generate sertifikat, `CheckoutController` (refactor invoice).
- Test: 1 Feature test per event (assert `Mail::fake()` / `Notification::fake()`).

---

## 5. Risiko

| Risiko | Mitigasi |
|--------|----------|
| Email ganda saat webhook retry | Guard status di `confirmPayment` sudah ada — pastikan `notify()` dipanggil **setelah** transisi status & sekali saja |
| Invoice PDF beda dengan yang di-download user | Satu sumber render (`InvoiceService`) |
| Spam ke peserta | Fase 1 hanya transaksional; toggle preferensi sebelum Fase 2 |
| Export link bocor dari inbox | Signed URL / attachment, bukan link biasa ke disk privat |
| SMTP down → deploy/aksi tertahan | Semua queued; gagal kirim hanya log error, tidak memblokir bisnis flow |

---

## 6. Hubungan dengan rencana lain

- **CI/CD** (`docs/future/CI-CD-PRODUCTION.md`): deploy otomatis aman karena email queued — tidak menambah beban request.
- Fitur Midtrans (`docs/DEPLOY-PEMBAYARAN-KE-PRODUKSI.md`): notifikasi order lunas adalah pelengkap alur webhook yang sudah ada.

---

*Dibuat 2026-09-30 — rencana, belum diimplementasi.*
