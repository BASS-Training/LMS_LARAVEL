# Routing

## Struktur Route Files

```
routes/
├── web.php         # 659 baris - Route web utama
├── auth.php        # 115 baris - Autentikasi
├── api.php         # 153 baris - Mobile API
├── channels.php    # Broadcasting channels
└── console.php     # Artisan console & schedules
```

## Route Groups

### 1. Public Routes (Tanpa Auth)

```
GET  /                                    → Welcome page
GET  /certificates/verify/{code}          → Verifikasi sertifikat publik
GET  /certificates/download/{code}        → Download sertifikat publik
GET  /katalog                             → Katalog kursus
GET  /katalog/{course}                    → Detail kursus
POST /katalog/{course}/daftar-gratis      → Free enrollment
GET  /katalog/{course}/beli               → Pilih metode bayar
POST /katalog/{course}/beli               → Buat order
GET  /pesanan                             → Riwayat order
GET  /pesanan/{order}                     → Status order
GET  /pesanan/{order}/invoice             → Invoice order
POST /webhooks/midtrans                   → Midtrans webhook (no CSRF)
```

### 2. Auth + Verified Middleware

```
GET  /dashboard                           → Dashboard
GET  /certificate/{code}                  → Detail sertifikat
POST /images/upload                       → Upload gambar editor
GET  /file-control/*                      → File management
GET  /activity-logs/*                     → Activity logs
GET  /attendance/*                        → Kehadiran
GET  /profile/*                           → Profil user
POST /courses/{id}/duplicate              → Duplikasi kursus
GET  /ajax/quizzes/*                      → Quiz AJAX partials
GET  /notifications/*                     → Notifikasi
GET  /announcements/*                     → Pengumuman
GET|POST /courses/*                       → CRUD kursus
GET  /courses/{id}/token/*                → Token management kursus
GET  /courses/{id}/enrollment-codes/*     → Enrollment codes
GET|POST /courses/{id}/lessons/*          → CRUD lessons
GET|POST /courses/{id}/lessons/{lid}/*    → CRUD contents
GET  /quizzes/*                           → Quiz management
GET  /essays/*                            → Essay submissions
GET  /case-studies/*                      → Case study submissions
GET  /document-submissions/*              → Document submissions
GET  /feedback/*                          → Feedback forms
GET  /courses/{id}/discussions/*          → Discussions
POST /courses/{id}/add-eo                 → Assign EO
POST /enroll                              → Token enrollment
POST /enroll/course                       → Course enrollment
POST /enroll/class                        → Class enrollment
GET  /courses/{id}/periods/*              → Course classes
GET  /courses/{id}/periods/{id}/token/*   → Class token
GET  /chat/*                              → Chat interface
GET  /courses/{id}/export-*               → Export data
GET  /courses/{id}/gradebook/*            → Gradebook
GET  /certificate-management/*            → Certificate management
GET  /instructor-analytics/*              → Instructor analytics
```

### 3. Admin Routes (prefix: `admin`)

```
Middleware: auth, verified, permission:manage users|manage roles|...

GET    /admin/roles/*                     → Role management
GET    /admin/participants/*              → Participant management
GET    /admin/certificate-templates/*     → Certificate templates
GET    /admin/tools/*                     → Admin tools
GET    /admin/users/*                     → User management
POST   /admin/users/import/*              → Bulk user import
GET    /admin/announcements/*             → Announcement management
POST   /admin/auto-grade/*                → Auto-grade
POST   /admin/force-complete/*            → Force-complete
GET    /admin/verifikasi-pembayaran/*     → Payment verification
       (role: super-admin)
```

### 4. Event Organizer Routes (prefix: `event-organizer`)

```
GET    /event-organizer/courses           → Kursus yang dikelola
```

### 5. API Routes (prefix: `api`)

```
Middleware: auth:sanctum

POST   /api/chats/*                       → Chat API
```

## Mobile API Routes (prefix: `api/mobile`)

### Public (Tanpa Auth)

```
POST   /api/mobile/login                  → Login
POST   /api/mobile/register               → Register
POST   /api/mobile/send-otp               → Kirim OTP
POST   /api/mobile/reset-password          → Reset password
```

### Authenticated (middleware: `mobile.api.user`)

#### Auth & Profile
```
GET    /api/mobile/me                     → Data user
POST   /api/mobile/logout                 → Logout
POST   /api/mobile/email-otp              → Kirim email OTP
POST   /api/mobile/verify-email-otp       → Verifikasi email
POST   /api/mobile/email-change           → Ubah email
POST   /api/mobile/password-change        → Ubah password
PUT    /api/mobile/profile                → Update profil
```

#### Kursus
```
GET    /api/mobile/courses                → Kursus saya
GET    /api/mobile/saved-courses          → Kursus tersimpan
GET    /api/mobile/catalog                → Katalog kursus
POST   /api/mobile/enroll                 → Enroll kursus
```

#### Kuis
```
GET    /api/mobile/quizzes/lesson/{id}    → Kuis per lesson
POST   /api/mobile/quizzes/{id}/start     → Mulai attempt
POST   /api/mobile/quizzes/{id}/submit    → Submit jawaban
GET    /api/mobile/quizzes/{id}/leaderboard → Leaderboard
```

#### Essay
```
POST   /api/mobile/essays/submit          → Submit essay
POST   /api/mobile/essays/draft           → Save draft
GET    /api/mobile/essays/lesson/{id}     → Essay per lesson
```

#### Case Study
```
GET    /api/mobile/case-studies/{id}      → Detail case study
POST   /api/mobile/case-studies/{id}/submit → Submit
POST   /api/mobile/case-studies/{id}/draft  → Save draft
GET    /api/mobile/case-studies/{id}/download → Download
```

#### Document Submission
```
GET    /api/mobile/document-submissions/{id} → Detail
POST   /api/mobile/document-submissions/{id}/upload → Upload
POST   /api/mobile/document-submissions/{id}/submit → Submit
GET    /api/mobile/document-submissions/{id}/grade → Grade
```

#### Feedback
```
GET    /api/mobile/feedback/{id}          → Form feedback
POST   /api/mobile/feedback/{id}/submit   → Submit feedback
```

#### Discussions
```
GET    /api/mobile/discussions/feed       → Feed diskusi
GET    /api/mobile/discussions/structure/{id} → Struktur diskusi
POST   /api/mobile/discussions/{id}/replies → Reply
```

#### Agenda
```
GET    /api/mobile/agenda/scheduled       → Agenda terjadwal
GET    /api/mobile/agenda/personal        → Agenda pribadi
POST   /api/mobile/agenda/personal        → Buat agenda
```

#### Gamification
```
POST   /api/mobile/game-scores            → Submit skor
GET    /api/mobile/achievements           → Achievement tiers
```

#### Sertifikat
```
GET    /api/mobile/certificates           → Sertifikat saya
POST   /api/mobile/certificates/{id}/generate → Generate PDF
```

#### Notifications
```
GET    /api/mobile/notifications          → Notifikasi
```

#### Instructor
```
GET    /api/mobile/instructor/dashboard   → Dashboard
GET    /api/mobile/instructor/grading-queue → Antrian nilai
GET    /api/mobile/instructor/participants → Peserta
POST   /api/mobile/instructor/grade       → Nilai siswa
```

## Broadcasting Channels

```php
// channels.php
Broadcast::channel('chat.{chatId}', function ($user, $chatId) {
    // Cek apakah user adalah participant
    return $this->checkParticipant($user, $chatId);
});
```

## Console Schedules

```php
// console.php
Schedule::command('certificates:cleanup-downloads')->hourly();
Schedule::command('permissions:audit')->daily();
```

## Naming Conventions

| Prefix | Keterangan |
|--------|------------|
| `admin.` | Route admin |
| `event-organizer.` | Route EO |
| `courses.` | Route nested courses |
| `courses.lessons.` | Route nested lessons |
| `lessons.contents.` | Route nested contents |

## Middleware Stack

### Web Routes
```php
Route::middleware(['auth', 'verified'])->group(function () {
    // Auth + email verified required
});

Route::middleware(['auth', 'verified', 'permission:manage users'])->prefix('admin')->group(function () {
    // Admin routes
});
```

### API Routes
```php
Route::prefix('api/mobile')->group(function () {
    // Public routes

    Route::middleware('mobile.api.user')->group(function () {
        // Authenticated mobile routes
    });
});
```

### Special Middleware
| Middleware | Fungsi |
|------------|--------|
| `LogActivity` | Global - log semua POST/PUT/PATCH/DELETE |
| `ForceJsonResponse` | AJAX chat endpoints |
| `EnsureEmailVerifiedOtp` | Gate akun baru yang perlu verifikasi OTP |
