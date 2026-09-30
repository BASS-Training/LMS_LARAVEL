<?php

namespace App\Providers;

use App\Models\Course; // Tambahkan ini jika belum ada
use App\Policies\CoursePolicy; // Tambahkan ini jika belum ada
use App\Models\Quiz; // <--- PASTIKAN INI ADA
use App\Policies\QuizPolicy; // <--- PASTIKAN INI ADA
use App\Models\Chat;
use App\Policies\ChatPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use App\Models\User;

class AppServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Course::class => CoursePolicy::class, // Ini sudah ada
        Quiz::class => QuizPolicy::class, // <--- TAMBAHKAN ATAU PASTIKAN INI ADA
        Chat::class => ChatPolicy::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Load polyfills for environments missing some PHP extensions (e.g., mbstring)
        $polyfills = base_path('app/Support/polyfills.php');
        if (file_exists($polyfills)) {
            require_once $polyfills;
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureMobileRateLimiters();

        // Global model change logging (create/update/delete) for non-participants
        \Illuminate\Database\Eloquent\Model::updated(function ($model) {
            try {
                if (!auth()->check()) return;
                $user = auth()->user();
                $roles = method_exists($user, 'getRoleNames') ? $user->getRoleNames() : collect();
                if ($roles->count() === 1 && $roles->contains('participant')) return;

                $original = $model->getOriginal();
                $attributes = $model->getAttributes();
                $changes = array_keys($model->getChanges());
                $ignore = ['updated_at', 'created_at', 'remember_token', 'password'];
                $changed = array_values(array_diff($changes, $ignore));
                if (empty($changed)) return; // nothing meaningful changed

                $before = [];
                $after = [];
                foreach ($changed as $key) {
                    $before[$key] = $original[$key] ?? null;
                    $after[$key] = $attributes[$key] ?? null;
                }

                \App\Models\ActivityLog::log('model.updated', [
                    'description' => sprintf('Updated %s #%s', get_class($model), $model->getKey()),
                    'metadata' => [
                        'model' => get_class($model),
                        'id' => $model->getKey(),
                        'changed_fields' => $changed,
                        'before' => $before,
                        'after' => $after,
                    ],
                ]);
            } catch (\Throwable $e) {}
        });

        \Illuminate\Database\Eloquent\Model::created(function ($model) {
            try {
                if (!auth()->check()) return;
                $user = auth()->user();
                $roles = method_exists($user, 'getRoleNames') ? $user->getRoleNames() : collect();
                if ($roles->count() === 1 && $roles->contains('participant')) return;

                \App\Models\ActivityLog::log('model.created', [
                    'description' => sprintf('Created %s #%s', get_class($model), $model->getKey()),
                    'metadata' => [
                        'model' => get_class($model),
                        'id' => $model->getKey(),
                        'after' => $model->getAttributes(),
                    ],
                ]);
            } catch (\Throwable $e) {}
        });

        \Illuminate\Database\Eloquent\Model::deleted(function ($model) {
            try {
                if (!auth()->check()) return;
                $user = auth()->user();
                $roles = method_exists($user, 'getRoleNames') ? $user->getRoleNames() : collect();
                if ($roles->count() === 1 && $roles->contains('participant')) return;

                \App\Models\ActivityLog::log('model.deleted', [
                    'description' => sprintf('Deleted %s #%s', get_class($model), $model->getKey()),
                    'metadata' => [
                        'model' => get_class($model),
                        'id' => $model->getKey(),
                        'before' => $model->getOriginal(),
                    ],
                ]);
            } catch (\Throwable $e) {}
        });

        // Push global activity logger middleware (logs only non-GET and skips pure participants internally)
        try {
            app(\Illuminate\Contracts\Http\Kernel::class)->pushMiddleware(\App\Http\Middleware\LogActivity::class);
        } catch (\Throwable $e) {
            // ignore if kernel not available in certain contexts
        }

        // Gate untuk mengelola kursus
        Gate::define('manage-courses', function (User $user) {
            return $user->can('manage all courses') || $user->can('manage own courses');
        });

        // Gate untuk user admin
        Gate::define('admin-only', function (User $user) {
            return $user->can('manage users') || $user->can('manage roles');
        });

        // Mobile notification triggers (sinkron web↔mobile lewat model events):
        // balasan diskusi & nilai essay / studi kasus yang keluar.
        \App\Models\Discussion::observe(\App\Observers\DiscussionObserver::class);
        \App\Models\DiscussionReply::observe(\App\Observers\DiscussionReplyObserver::class);
        \App\Models\EssaySubmission::observe(\App\Observers\EssaySubmissionObserver::class);
        \App\Models\CaseStudySubmission::observe(\App\Observers\CaseStudySubmissionObserver::class);
        \App\Models\DocumentSubmission::observe(\App\Observers\DocumentSubmissionObserver::class);
    }

    private function configureMobileRateLimiters(): void
    {
        $tooManyRequests = static function (Request $request, array $headers) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terlalu banyak permintaan. Silakan coba lagi.',
                'retry_after' => (int) ($headers['Retry-After'] ?? 60),
            ], 429, $headers);
        };

        $userKey = static function (Request $request): string {
            $token = $request->bearerToken();

            return $token ? 'token:'.hash('sha256', $token) : 'ip:'.$request->ip();
        };
        $emailKey = static function (Request $request): string {
            $email = strtolower(trim((string) $request->input('email')));

            return $email !== '' ? $email : $request->ip();
        };

        RateLimiter::for('mobile-login', static fn (Request $request): array => [
            Limit::perMinute(5)->by('email:'.$emailKey($request))->response($tooManyRequests),
            Limit::perMinute(20)->by('ip:'.$request->ip())->response($tooManyRequests),
        ]);

        RateLimiter::for('mobile-register', static fn (Request $request): Limit => Limit::perMinutes(10, 3)
            ->by($request->ip())
            ->response($tooManyRequests));

        RateLimiter::for('mobile-otp-send', static fn (Request $request): Limit => Limit::perMinutes(10, 3)
            ->by($request->bearerToken() ? $userKey($request) : 'email:'.$emailKey($request))
            ->response($tooManyRequests));

        RateLimiter::for('mobile-otp-verify', static fn (Request $request): Limit => Limit::perMinutes(10, 5)
            ->by($request->bearerToken() ? $userKey($request) : 'email:'.$emailKey($request))
            ->response($tooManyRequests));

        RateLimiter::for('mobile-api', static fn (Request $request): array => [
            Limit::perMinute(120)->by($userKey($request))->response($tooManyRequests),
            Limit::perMinute(600)->by('ip:'.$request->ip())->response($tooManyRequests),
        ]);

        RateLimiter::for('mobile-autosave', static fn (Request $request): Limit => Limit::perMinute(20)
            ->by($userKey($request))
            ->response($tooManyRequests));

        RateLimiter::for('mobile-upload', static fn (Request $request): Limit => Limit::perMinute(5)
            ->by($userKey($request))
            ->response($tooManyRequests));

        RateLimiter::for('mobile-certificate', static function (Request $request) use ($tooManyRequests, $userKey): Limit {
            $course = $request->route('course');
            $courseKey = is_object($course) && method_exists($course, 'getRouteKey')
                ? $course->getRouteKey()
                : $course;

            return Limit::perMinutes(10, 2)
                ->by($userKey($request).':course:'.$courseKey)
                ->response($tooManyRequests);
        });

        RateLimiter::for('mobile-handoff', static fn (Request $request): array => [
            Limit::perMinute(10)->by($userKey($request))->response($tooManyRequests),
            Limit::perMinute(30)->by('ip:'.$request->ip())->response($tooManyRequests),
        ]);
    }
}
