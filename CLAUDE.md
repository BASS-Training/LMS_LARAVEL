# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Laravel 12 LMS (Learning Management System) for Indonesian training programs. Supports 4 roles: Admin, Instructor, Participant, Event Organizer.

## Development Commands

### Prerequisite: Redis (cache)

Cache uses Redis (`CACHE_STORE=redis`, phpredis). Redis must be running before `composer dev`.

```bash
# Ubuntu/Debian
sudo apt install redis-server php-redis && sudo systemctl enable --now redis-server
# macOS
brew install redis php-redis && brew services start redis
# Docker (any OS)
docker run -d --name lms-redis -p 6379:6379 redis:7
# Check
redis-cli ping            # PONG
php -m | grep redis       # Windows: php -m | findstr redis
```

**Windows** (Redis has no official native build) — pick one:
- **WSL2 (recommended):** `wsl --install`, then follow the Ubuntu steps inside WSL. Laravel on Windows can still reach `127.0.0.1:6379`.
- **Docker Desktop:** use the `docker run` command above.
- **Laragon:** enable Redis from the Laragon menu; `php_redis.dll` is bundled.
- Community alternative: Memurai.
- PHP extension on plain Windows PHP: download `php_redis.dll` matching your PHP version/TS/arch from pecl.php.net/package/redis, put it in `ext/`, add `extension=redis` to `php.ini`. If that is hard, use `composer require predis/predis` and set `REDIS_CLIENT=predis` in your local `.env` only (not production).

```bash
# Start all dev processes concurrently (server + queue + logs + Vite HMR)
composer dev

# Individual processes
php artisan serve          # Dev server on :8000
php artisan queue:listen   # Process queued jobs
npm run dev                # Vite dev server (HMR)
npm run build              # Compile assets for production

# Database
php artisan migrate --seed  # Fresh setup with test data
php artisan storage:link    # Create public storage symlink

# Code formatting
./vendor/bin/pint           # Laravel Pint (PSR-12)

# Testing
php artisan test            # Run PHPUnit tests
php artisan test --filter=TestName  # Run single test
```

**Default test accounts (after seeding):**
- `admin@example.com` / `password`
- `instructor@example.com` / `password`
- `participant@example.com` / `password`
- `eo@example.com` / `password`

## Architecture

### Role-Based Access Control
All routes use Spatie Laravel Permission middleware (`middleware('permission:...')`). Roles: Admin, Instructor, Participant, Event Organizer. Permission checks happen at the route level, not inside controllers.

### Core Data Hierarchy
```
Course → Lesson → Content (type: text|video|quiz|essay|document|image|zoom)
       → CourseClass (batch/period with separate enrollment tokens)
Bundle → bundle_course → Course
```

Enrollment is token-based: both `Course` and `CourseClass` have their own `enrollment_token`, `token_enabled`, `token_expires_at`, `token_type` fields.

### Assessment System
- **Quizzes**: Auto-graded via `QuizAttempt` → `QuestionAnswer` comparison against `Option.is_correct`
- **Essays**: Manually graded by instructors — `EssaySubmission` → `EssayAnswer` (per question) with rubric support; grade stored on both question-level and submission-level
- Progress tracked via three pivot tables: `course_user`, `lesson_user`, `content_user`

### Content Scheduling & Access
`Content` has scheduling fields (`is_scheduled`, `scheduled_start`, `scheduled_end`, `timezone_offset`), optional flag (`is_optional`), attendance requirement (`attendance_required`, `min_attendance_minutes`), and grading config (`scoring_enabled`, `grading_mode`, `requires_review`).

### Duplication Feature
Courses, lessons, and content can be duplicated. Uses `app/Models/Traits/Duplicateable.php` trait. Duplicates related data (lessons → content → quiz questions/options) but not user associations or completion records.

### Activity Logging
`LogActivity` middleware captures all POST/PUT/PATCH/DELETE requests with before/after model state. Stored async via database queue. Sensitive fields (passwords, tokens) are filtered. GET request logging is allowlist-based.

### Real-time
Laravel Reverb (WebSocket) on port 8080 with Laravel Echo client. Used for chat and notifications. Broadcasting config in `.env`: `BROADCAST_CONNECTION=reverb`.

### Certificate System
Templates have x/y coordinate fields for dynamic placement of name, date, title, signature on a background image. Three template tiers: basic, enhanced, advanced. Certificates have a unique verification `code` for public lookup at `/certificates/verify/{code}`.

### File Storage
- Rich-text editor images: uploaded via `ImageUploadController`
- Content documents: `ContentDocument` model with `document_access_type` (public|restricted)
- Content images: `ContentImage` model with caption and order
- Certificates: stored as PDFs at path in `Certificate.pdf_path`

### API Routes
No separate `routes/api.php`. API routes live inside `routes/web.php` under an `/api` prefix with Sanctum middleware. Currently covers chat/messaging endpoints.

### Queue & Sessions
Cache runs on Redis (`CACHE_STORE=redis`, phpredis). Sessions and queue are configured separately via `SESSION_DRIVER` and `QUEUE_CONNECTION` (payment jobs use the `payment_database` connection on purpose). Redis is shared with other apps on the host — never run `FLUSHALL`/`FLUSHDB`.

## Key Conventions

- **Indonesian locale** (`APP_LOCALE=id`) — user-facing text in Indonesian
- Views use **Alpine.js** for interactivity (dropdowns, modals, collapsible sections) and **Tailwind CSS** for styling
- Rich text editing uses **Summernote** (loaded via CDN), not TinyMCE
- Admin routes are prefixed with `admin/` and live in `app/Http/Controllers/Admin/`
- API controllers are in `app/Http/Controllers/Api/`
- The `AVPN` fields on `User` (avpn_verification_status, avpn_verified_at, avpn_verified_by) relate to an external registration verification workflow
