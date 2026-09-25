# Optimasi Performa `courses/show`

> Status: **Fase 1 selesai (T1–T8)** — backup BEFORE di `docs/backup/CourseController.before-show-optimization.php`.
> Tanggal: 2026-09-24

## Konteks

Audit rate limiting menunjukkan halaman `courses/show` berat bukan karena throttle, melainkan karena **payload JS terlalu besar** dan **query berlebih**. Rate limiting hanya aktif di `GET /courses` (30/menit), beberapa OTP/email, dan login — bukan di `courses/show`.

## Masalah

1. **Payload JS terlalu besar**
   - `resources/views/courses/show.blade.php:220` — `Js::from($course->lessons->sortBy('order')->values())` serialize seluruh model `Lesson` + nested `Content` (termasuk `body`, `file_path`, submission fields, timestamps).
   - `resources/views/courses/show.blade.php:509` — `@js($course->periods->toArray())` serialize seluruh model `CourseClass` (termasuk `enrollment_token`, `token_enabled`, `token_expires_at`, `token_type`, `class_code`, `max_participants`, `program_type`).
   - `Content` / `CourseClass` tidak punya `$hidden` → semua kolom ikut ter-serialize.

2. **Query berlebih**
   - `$course->load('lessons.contents', 'instructors')` ambil semua kolom.
   - `isInstructorFor()` + `isEventOrganizerFor()` = 2× query `exists()`.
   - `$course->hasActivePeriod()` di blade `:582` = 1 query lagi, padahal periods sudah di-load.
   - `exportHistories` eager-load `courseClass` penuh (ikut token).

## Field minimum yang dibutuhkan view/JS

| Sumber | Field |
|---|---|
| Lesson JS | `id`, `title`, `description` (`order` hanya untuk sort server-side) |
| Content JS | `id`, `title`, `type` |
| Period JS | `id`, `name`, `status`, `start_date`, `end_date`, `description` |
| Period Blade count | `status` |
| Export history | `id`, `filter`, `file_path`, `status`, `error_message`, `created_at` + relasi `courseClass.id`, `courseClass.name` |

**WAJIB** sertakan FK pada select relasi agar eager-load matching works: `lessons.course_id`, `contents.lesson_id`, `periods.course_id`. Kolom pivot/relasi pakai prefix tabel (`users.id`, `users.name`) untuk hindari ambigu.

## Fase 1 — Rencana Implementasi (disetujui)

Pecahan task kecil, urutan: T1→T2→T3→T4→T5 (controller), T6 (view), T7 (test), T8 (verifikasi).

### T1 — Select minimal eager-load ✅ SELESAI (2026-09-24)

**File:** `app/Http/Controllers/CourseController.php` (`show()`), `app/Models/Course.php` (`getUserInstructorPeriods()`)

- Eager-load dengan select minimal:
  ```
  lessons:id,course_id,title,description,order
    → contents:id,lesson_id,title,type,order
  instructors:users.id,users.name
  eventOrganizers:users.id,users.name
  periods:id,course_id,name,start_date,end_date,status,description
  ```
- `getUserInstructorPeriods()` (hanya dipakai dari `show()`): tambah `->select()` 7 kolom periods (id, course_id, name, start_date, end_date, status, description).
- **Dikerjakan:** `CourseController::show()` — `load('lessons.contents', 'instructors')` diganti closure select minimal + nested `contents` select; `load('periods')` diganti select 7 kolom; `eventOrganizers` select `users.id,users.name`. `Course::getUserInstructorPeriods()` — tambah select 7 kolom.

### T2 — Hilangkan 2 query exists ✅ SELESAI (2026-09-24)

**File:** `app/Http/Controllers/CourseController.php`

- Load `eventOrganizers` **selalu** (digabung ke `load()` utama, bukan hanya saat `$canManageCourse`); bagian view tetap dijaga `@can`.
- Ganti `$user->isInstructorFor($course)` → `$course->instructors->contains('id', $user->id)`.
- Ganti `$user->isEventOrganizerFor($course)` → `$course->eventOrganizers->contains('id', $user->id)`.
- **Dikerjakan:** cabang if/else `eventOrganizers` dihapus; cek role pakai koleksi `$isInstructor` / `$isEventOrganizer`. Regresi `CourseEventOrganizerManagementTest` lulus (5 tes).

### T3 — `$hasActivePeriod` dari koleksi ✅ SELESAI (2026-09-24)

**File:** `app/Http/Controllers/CourseController.php`, `resources/views/courses/show.blade.php`

- Load periods **penuh dulu** (select T1) → hitung `$hasActivePeriod` dari koleksi dengan kondisi identik `Course::hasActivePeriod()`:
  ```php
  status === 'active' && start_date <= now() && end_date >= now()
  ```
- **Baru setelah itu** filter periods untuk instruktur (`setRelation` / `getUserInstructorPeriods`).
- Pass `$hasActivePeriod` ke view; blade `:582` → `@if($hasActivePeriod)`.
- Perilaku identik: cek **semua** period course, bukan hanya yang difilter instruktur.
- **Dikerjakan:** `show()` load periods di atas cabang filter; `$hasActivePeriod` dari `contains()` (null-safe pada dates); `compact(..., 'hasActivePeriod')`; blade `:582` pakai variabel baru.

### T4 — Export histories ramping ✅ SELESAI (2026-09-24)

**File:** `app/Http/Controllers/CourseController.php`

- Select hanya kolom yang dipakai view: `id, filter, course_class_id, file_path, status, error_message, created_at` (opsional `course_class_id`).
- `with('courseClass:id,name')` — tanpa token/kolom lain.
- **Dikerjakan:** `ExportHistory::query()->select(...)` + `with('courseClass:id,name')`. View hanya butuh `created_at`, `filter`, `courseClass.name`, `status`, `error_message`, `file_path`, `id` (route download).

### T5 — Payload array plain ✅ SELESAI (2026-09-24)

**File:** `app/Http/Controllers/CourseController.php`

- Siapkan di controller:
  - `$lessonPayload` — per lesson: `id`, `title`, `description`, `contents` (per content: `id`, `title`, `type`); sudah `sortBy('order')`.
  - `$periodPayload` — per period: `id`, `name`, `status`, `start_date`, `end_date`, `description`.
- Array plain — **tanpa** `body`, `enrollment_token`, `token_*`, `class_code`, `max_participants`, `program_type`.
- `compact()` bersama variabel lain.
- **Dikerjakan:** dua `map()` setelah filter instruktur (period payload ikut filter); `compact(..., 'lessonPayload', 'periodPayload')`. Wire view di T6.

### T6 — Wire view ✅ SELESAI (2026-09-24)

**File:** `resources/views/courses/show.blade.php`

- `:220` → `lessons: {{ Js::from($lessonPayload) }}`
- `:509` → `x-data="periodManager({{ $course->id }}, @js($periodPayload))"`
- `:582` → `@if($hasActivePeriod)` (sudah dari T3)
- Count `:559`, `:563`, `:567`, `:697` tetap `$course->periods` (sudah select `status`).
- **Dikerjakan:** dua baris wire selesai; pint fix encoding blade.

### T7 — Test payload ✅ SELESAI (2026-09-24)

**File:** `tests/Feature/CourseShowPayloadTest.php` (baru)

- Course + lesson/content (`body` rahasia) + period (`enrollment_token`, `class_code` rahasia) → GET `courses.show`: `assertOk`, `assertSee` judul/deskripsi, `assertDontSee` secret.
- Tes instruktur: hanya period miliknya yang tampil (butuh permission `view courses` karena middleware route `permission:view courses|manage all courses`).
- Tes `$hasActivePeriod`: active dalam rentang tanggal → `Buka Chat` tampil; selesai di luar rentang → tidak tampil.
- **Dikerjakan:** 4 tes lulus; regresi `Course*` 30 tes lulus (hanya deprecation PDO SSL PHP 8.5).

### T8 — Verifikasi ✅ SELESAI (2026-09-24)

```bash
php artisan test --filter=Course   # 30 tests pass
php artisan view:cache              # OK
npm run build                       # OK
./vendor/bin/pint                   # OK
git diff --check                    # OK
```

**Backup belajar:** `docs/backup/CourseController.before-show-optimization.php` — `show()` versi sebelum T1–T5 (load penuh, if/else EO, `isInstructorFor` query, tanpa payload/`$hasActivePeriod`).

## Risiko

- Select relasi wajib punya FK (`course_id`, `lesson_id`) — tanpa itu eager-load kosong.
- Kolom pivot ambigu → pakai prefix `users.id`.
- `getUserInstructorPeriods` hanya aman di-select jika tidak dipakai tempat lain (sudah diverifikasi: hanya `CourseController::show`).

## Fase 2 — Opsi (belum disetujui)

**Lazy-load tab**: data lessons/periods dimuat via endpoint kecil (`GET /courses/{id}/lessons-payload`) hanya saat tab diklik, diisi ke Alpine via `fetch`.

- Plus: HTML/JS awal jauh lebih ringan untuk course dengan banyak lesson/period.
- Minus: endpoint baru + loading/error state di view + lebih banyak kode/test.
- Relevan hanya jika setelah fase 1 halaman masih berat karena **jumlah** record.

## Referensi

- `app/Http/Controllers/CourseController.php:168-246` — `show()`
- `app/Models/Course.php:170` — `hasActivePeriod()`
- `app/Models/Course.php:234` — `getUserInstructorPeriods()`
- `resources/views/courses/show.blade.php:220,509,582`
- `tests/Feature/CourseEventOrganizerManagementTest.php` — regresi EO
