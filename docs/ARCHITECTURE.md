# Arsitektur Sistem

## Ikhtisar

Sistem ini adalah **Learning Management System (LMS)** berbasis Laravel 12 untuk program pelatihan BASS Training Center. Mendukung 4 peran: Admin, Instructor, Participant, dan Event Organizer.

## Arsitektur Umum

```
┌─────────────────────────────────────────────────────┐
│                    CLIENT LAYER                      │
│  ┌──────────┐  ┌──────────┐  ┌──────────────────┐  │
│  │ Web App  │  │ Mobile   │  │ Public Pages     │  │
│  │ (Blade)  │  │ (API)    │  │ (Shop/Cert)      │  │
│  └────┬─────┘  └────┬─────┘  └────────┬─────────┘  │
├───────┼──────────────┼─────────────────┼────────────┤
│       └──────────────┼─────────────────┘            │
│                 ROUTING LAYER                        │
│  ┌──────────┐  ┌──────────┐  ┌──────────────────┐  │
│  │ web.php  │  │ api.php  │  │ auth.php         │  │
│  └────┬─────┘  └────┬─────┘  └────────┬─────────┘  │
├───────┼──────────────┼─────────────────┼────────────┤
│                 MIDDLEWARE LAYER                     │
│  ┌──────────┐  ┌──────────┐  ┌──────────────────┐  │
│  │Auth:Sanctum│ │Permission│  │LogActivity       │  │
│  │VerifyOTP │  │Role Check│  │ForceJson         │  │
│  └────┬─────┘  └────┬─────┘  └────────┬─────────┘  │
├───────┼──────────────┼─────────────────┼────────────┤
│                CONTROLLER LAYER                      │
│  ┌──────────┐  ┌──────────┐  ┌──────────────────┐  │
│  │Admin/    │  │Auth/     │  │Api/ (Mobile)     │  │
│  │(9 Ctrls) │  │(12 Ctrls)│  │(22 Ctrls)        │  │
│  └────┬─────┘  └────┬─────┘  └────────┬─────────┘  │
├───────┼──────────────┼─────────────────┼────────────┤
│                 SERVICE LAYER                        │
│  ┌──────────┐  ┌──────────┐  ┌──────────────────┐  │
│  │Payment/  │  │Token     │  │OTP / Enrollment  │  │
│  │Midtrans  │  │Generator │  │Code Generator    │  │
│  └────┬─────┘  └────┬─────┘  └────────┬─────────┘  │
├───────┼──────────────┼─────────────────┼────────────┤
│                 MODEL LAYER (38 Models)              │
│  ┌──────────┐  ┌──────────┐  ┌──────────────────┐  │
│  │Core Edu  │  │Assessment│  │Commerce/Chat     │  │
│  │Models    │  │Models    │  │Models            │  │
│  └────┬─────┘  └────┬─────┘  └────────┬─────────┘  │
├───────┼──────────────┼─────────────────┼────────────┤
│                  DATABASE LAYER                      │
│  ┌──────────┐  ┌──────────┐  ┌──────────────────┐  │
│  │92 Migr.  │  │6 Seeders │  │MySQL/SQLite      │  │
│  └──────────┘  └──────────┘  └──────────────────┘  │
└─────────────────────────────────────────────────────┘
```

## Pola Arsitektur

### 1. MVC + Service Layer

- **Model**: 38 model Eloquent, masing-masing dengan relasi yang didefinisikan
- **View**: 36 direktori Blade dengan komponen reusable
- **Controller**: 68 controller terorganisir berdasarkan domain
- **Service**: 8 service class untuk logika bisnis kompleks

### 2. Role-Based Access Control (RBAC)

Menggunakan **Spatie Laravel Permission** dengan 4 role:
- `super-admin` — Bypass semua izin via `Gate::before()`
- `instructor` — Kelola kursus sendiri, nilai, buat konten
- `event-organizer` — Lihat kursus, laporan, kelola sertifikat
- `participant` — Enroll, ikuti kuis, diskusi, chat

Izin diperiksa di **route level**, bukan di dalam controller.

### 3. Multi-Channel Access

```
┌─────────────────────────────────────┐
│           CHANNEL LAYER             │
├─────────────┬───────────────────────┤
│  Web (Breeze)│  Mobile API (Sanctum)│
│  68 Controllers│  22 API Controllers │
│  Blade Views │  JSON Responses      │
│  Session Auth│  Bearer Token Auth   │
└─────────────┴───────────────────────┘
```

### 4. Event-Driven Architecture

- **Observers**: 5 observer untuk notifikasi otomatis
- **Events**: `MessageSent`, `UserTyping` untuk real-time
- **Jobs**: 5 job untuk proses async (logging, export, bulk operations)
- **Broadcasting**: Laravel Reverb (WebSocket) di port 8080

### 5. Dual Enrollment System

```
Course ──────────┬── enrollment_token (course-level)
                 │
CourseClass ─────┴── enrollment_token (class-level)
  (batch/period)
```

Keduanya memiliki: `token_enabled`, `token_expires_at`, `token_type`

## Struktur Direktori

```
app/
├── Console/                    # Artisan commands
├── Events/                     # Broadcast events (2)
├── Http/
│   ├── Controllers/
│   │   ├── Admin/              # 9 admin controllers
│   │   ├── Api/                # 22 API controllers + 1 Concern
│   │   ├── Auth/               # 12 auth controllers
│   │   └── (root)              # 24 general controllers
│   └── Middleware/              # 4 custom middleware
├── Jobs/                       # 5 queued jobs
├── Mail/                       # 1 mailable (OTP)
├── Models/
│   ├── Traits/                 # Duplicateable trait
│   └── (root)                  # 38 Eloquent models
├── Notifications/              # 5 notification classes
├── Observers/                  # 5 model observers
├── Policies/                   # 5 authorization policies
├── Providers/                  # 2 service providers
├── Services/                   # 8 service classes
│   └── Payment/                # 3 payment services
└── View/
    └── Components/             # 2 view components
```

## Data Hierarchy

```
Course
├── Lesson (ordered, with prerequisites)
│   ├── Content (text|video|quiz|essay|document|image|zoom)
│   │   ├── ContentImage (multiple)
│   │   ├── ContentDocument (multiple)
│   │   ├── EssayQuestion → EssayAnswer
│   │   ├── FeedbackQuestion → FeedbackAnswer
│   │   └── Quiz → Question → Option
│   └── Quiz
│       └── Attempt → QuestionAnswer
├── CourseClass (batch/period)
│   ├── Instructors (pivot)
│   └── Participants (pivot)
├── CertificateTemplate
├── Certificate (issued)
├── Chat
├── Discussion → DiscussionReply
└── EnrollmentCode
```

## Alur Autentikasi

### Web (Breeze)
```
Login → Session → Middleware: auth → Verify Email (OTP)
       ↓
   Guest Routes: /login, /register, /forgot-password
       ↓
   Auth Routes: /dashboard, /courses, /profile, etc.
```

### Mobile API (Sanctum)
```
Login → Bearer Token → Middleware: auth:sanctum
       ↓
   Public: /api/mobile/login, /api/mobile/register
       ↓
   Auth: /api/mobile/courses, /api/mobile/quiz, etc.
```

## Real-time Architecture

```
┌────────────┐     WebSocket      ┌────────────┐
│  Web Client │ ◄──────────────► │  Reverb    │
│  (Echo)     │    Port 8080      │  Server    │
└────────────┘                    └────────────┘
                                      │
                              ┌───────┴───────┐
                              │  Broadcasting  │
                              │  Channels      │
                              │  chat.{id}     │
                              └───────────────┘
```

- **Laravel Echo** (client) + **Pusher.js** (protocol)
- Channel auth: `chat.{chatId}` — cek participant membership
- Events: `MessageSent`, `UserTyping`

## Async Processing

```
Request → Controller → Job Dispatch → Queue (Database)
                                          ↓
                                   Worker Process
                                          ↓
                                   Notification/Export
```

- **Queue**: Database driver (`QUEUE_CONNECTION=database`)
- **Jobs**: LogActivity, ExportParticipants, BulkCertificates, BulkForceComplete, BulkDownloadCertificates
- **Pattern**: Fire-and-forget untuk logging & export
