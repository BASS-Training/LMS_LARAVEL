# Gaya Penulisan & Konvensi Kode

## Lokal & Bahasa

- **APP_LOCALE**: `id` (Bahasa Indonesia)
- Semua teks user-facing dalam Bahasa Indonesia
- Nama route, view, dan konten menggunakan Bahasa Indonesia
- Contoh: `/katalog`, `/pesanan`, `/daftar-gratis`, `/verifikasi-pembayaran`

## Naming Conventions

### Directories
```
app/
├── Http/Controllers/Admin/        # PascalCase
├── Http/Controllers/Api/          # PascalCase
├── Models/                        # Singular, PascalCase
├── Services/Payment/              # PascalCase
└── ...
```

### Files
| Tipe | Format | Contoh |
|------|--------|--------|
| Model | PascalCase.php | `Course.php`, `QuizAttempt.php` |
| Controller | PascalCaseController.php | `CourseController.php` |
| Service | PascalCase.php | `TokenGenerator.php`, `OrderService.php` |
| Migration | timestamp_description.php | `2025_06_25_082203_create_courses_table.php` |
| View | kebab-case.blade.php | `course-detail.blade.php` |
| Job | PascalCaseJob.php | `LogActivityJob.php` |
| Policy | PascalCasePolicy.php | `CoursePolicy.php` |

### Database
- **Table names**: snake_case, plural (`courses`, `quiz_attempts`, `course_user`)
- **Pivot tables**: alphabetical order (`course_user` bukan `user_course`)
- **Column names**: snake_case (`enrollment_token`, `is_scheduled`, `token_expires_at`)
- **Boolean columns**: `is_` prefix (`is_correct`, `is_optional`, `is_scheduled`)

### Routes
```php
// Web routes - Bahasa Indonesia
Route::get('/katalog', ...);
Route::post('/katalog/{course}/daftar-gratis', ...);
Route::get('/pesanan', ...);

// Admin prefix
Route::prefix('admin')->group(function () { ... });

// API prefix
Route::prefix('api/mobile')->group(function () { ... });
```

### Views
```
resources/views/
├── courses/           # kebab-case directories
│   ├── index.blade.php
│   ├── create.blade.php
│   ├── show.blade.php
│   └── edit.blade.php
├── quizzes/
│   ├── partials/      # Partials subdirectory
│   │   ├── question-card.blade.php
│   │   └── ...
```

## Code Style

### PHP (PSR-12 via Laravel Pint)
```bash
./vendor/bin/pint
```

### Controller Pattern
```php
class CourseController extends Controller
{
    public function index()
    {
        // Logic
        return view('courses.index', compact('courses'));
    }

    public function store(StoreCourseRequest $request)
    {
        // Validated data via Form Request
        $course = Course::create($request->validated());
        return redirect()->route('courses.show', $course);
    }
}
```

### Service Pattern
```php
class OrderService
{
    public function checkout(User $user, Course $course, string $method): Order
    {
        // Business logic
        // Menggunakan DB transaction & lockForUpdate
        return DB::transaction(function () use ($user, $course, $method) {
            // ...
        });
    }
}
```

### Model Pattern
```php
class Course extends Model
{
    use Duplicateable;

    protected $fillable = ['title', 'description', 'price'];

    // Relasi
    public function lessons()
    {
        return $this->hasMany(Lesson::class);
    }

    // Scopes
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }
}
```

### Policy Pattern
```php
class CoursePolicy
{
    public function before(User $user): ?bool
    {
        if ($user->hasRole('super-admin')) {
            return true; // Bypass
        }
        return null;
    }

    public function view(User $user, Course $course): bool
    {
        return $course->enrolledUsers()->contains($user->id)
            || $course->instructors()->contains($user->id);
    }
}
```

## Frontend Conventions

### Alpine.js
Digunakan untuk semua interaktivitas:
```html
<div x-data="{ open: false }">
    <button @click="open = !open">Toggle</button>
    <div x-show="open" x-transition>Content</div>
</div>
```

### Tailwind CSS
Semua styling menggunakan Tailwind utility classes:
```html
<div class="bg-white rounded-lg shadow-md p-6">
    <h2 class="text-xl font-semibold text-gray-800">Title</h2>
</div>
```

### Summernote (Rich Text Editor)
Loaded via CDN, bukan npm package:
```html
<link href="https://cdn.jsdelivr.net/npm/summernote@0.9.0/dist/summernote-lite.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/summernote@0.9.0/dist/summernote-lite.min.js"></script>
```

## Middleware Conventions

### Global Middleware
```php
// AppServiceProvider.php
Route::pushMiddlewareGroup('web', [
    LogActivity::class,
]);
```

### Route-Level Permission
```php
Route::middleware(['permission:manage courses'])->group(function () {
    // Hanya user dengan izin "manage courses"
});
```

## Job Conventions

```php
class ExportCourseParticipantsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        // Async processing
        // Notification when complete
    }
}
```

## Notification Conventions

```php
class MobileNotification extends Notification
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['database']; // Database channel untuk mobile
    }

    public function toArray(object $notifiable): array
    {
        return [
            'category' => 'discussion_reply',
            'title' => '...',
            'message' => '...',
        ];
    }
}
```

## Test Accounts

| Email | Password | Role |
|-------|----------|------|
| admin@example.com | password | super-admin |
| instructor@example.com | password | instructor |
| participant@example.com | password | participant |
| eo@example.com | password | event-organizer |

## File Organization

### Model Traits
```
app/Models/Traits/
└── Duplicateable.php    # Duplikasi model + relasi
```

### Service Organization
```
app/Services/
├── TokenGenerator.php
├── OtpService.php
├── EnrollmentCodeGenerator.php
├── CourseService.php
├── CourseParticipantQueryService.php
└── Payment/
    ├── MidtransGateway.php
    ├── OrderService.php
    └── ServiceFee.php
```

### Controller Organization
```
app/Http/Controllers/
├── Admin/               # 9 controllers (admin panel)
├── Api/                 # 22 controllers (mobile API)
│   └── Concerns/        # Shared traits
│       └── PresentsMobileUser.php
├── Auth/                # 12 controllers (authentication)
└── (root)               # 24 controllers (general web)
```

## Database Conventions

### Migrations
- Timestamp prefix: `YYYY_MM_DD_HHMMSS_`
- Snake_case description
- Foreign key: `foreign()` → `references('id')` → `on('table')`
- Pivot tables: separate migration

### Seeders
```php
class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Define permissions
        // Create roles
        // Assign permissions to roles
    }
}
```

## Configuration Conventions

### Environment Variables
```env
# Grouped by concern
APP_NAME=
APP_ENV=
APP_KEY=

DB_CONNECTION=
DB_HOST=

MIDTRANS_MERCHANT_ID=
MIDTRANS_CLIENT_KEY=

REVERB_HOST=
REVERB_PORT=
```

### Config Files
- Menggunakan config bawaan Laravel
- Tambah custom config hanya jika diperlukan
- Contoh: `config/permission.php` (Spatie)
