<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\MobileCatalogIndexRequest;
use App\Models\Category;
use App\Models\Course;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Etalase kursus untuk MOBILE (tab "Jelajahi").
 *
 * Sengaja DIPISAH dari CourseApiController: `index()` di sana di-scope ke
 * kursus yang sudah di-enroll, dan hasilnya disimpan mobile ke cache Hive
 * sebagai "Kursus Saya". Menyelipkan kursus katalog ke sana berisiko membuat
 * kursus yang belum dimiliki ikut tersimpan & tampil sebagai milik user.
 *
 * Mirror dari ShopController (web), dengan dua perbedaan penting:
 *  1. Daftar dan preview melayani tamu, sedangkan pendaftaran wajib login.
 *  2. TIDAK ADA jalur pembelian di dalam app. Kursus berbayar hanya bisa
 *     di-preview; jalan masuknya adalah kode akses (EnrollmentApiController)
 *     atau pembelian di website.
 *
 * Yang dikirim hanya metadata + JUDUL kurikulum. Isi konten (body, file,
 * template soal) tidak pernah ikut — persis seperti halaman preview di web.
 */
class ShopApiController extends Controller
{
    /**
     * Daftar kursus di etalase. Mendukung pencarian & filter gratis/berbayar.
     */
    public function index(MobileCatalogIndexRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = $request->user();
        $search = $validated['q'] ?? null;

        $query = Course::inCatalog()->with([
            'instructors:id,name,avatar,instructor_bio',
            'salesProfile',
            'categories' => fn ($query) => $query->active()->ordered()->select('categories.id', 'name', 'slug'),
            'tags' => fn ($query) => $query->active()->orderBy('name')->select('tags.id', 'name', 'slug'),
        ]);

        if ($search !== null) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%'.$search.'%')
                    ->orWhere('short_description', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%')
                    ->orWhereHas('salesProfile', function ($query) use ($search) {
                        $query->where('headline', 'like', '%'.$search.'%')
                            ->orWhere('target_audience', 'like', '%'.$search.'%')
                            ->orWhere('learning_benefits', 'like', '%'.$search.'%');
                    });
            });
        }

        $priceFilter = $validated['harga'] ?? null;
        if ($priceFilter === 'free') {
            $query->where(fn ($q) => $q->whereNull('price')->orWhere('price', '<=', 0));
        } elseif ($priceFilter === 'paid') {
            $query->where('price', '>', 0);
        }

        if (isset($validated['category'])) {
            $query->whereHas('categories', fn ($query) => $query->active()->where('slug', $validated['category']));
        }

        if (isset($validated['tag'])) {
            $query->whereHas('tags', fn ($query) => $query->active()->where('slug', $validated['tag']));
        }

        $courses = $query
            ->withCount('lessons')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($validated['perPage'] ?? 20)
            ->withQueryString();

        // Satu query untuk semua, daripada isEnrolledBy() per baris (N+1).
        $enrolledIds = $this->enrolledIds($user, $courses->pluck('id')->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil mengambil katalog kursus',
            'data' => $courses->getCollection()->map(
                fn (Course $course) => $this->transformCard($course, $enrolledIds)
            )->values(),
            'meta' => [
                // Mobile membaca ini untuk memutuskan menampilkan harga atau
                // sekadar label "Berbayar". Lihat config/shop.php.
                'showPrice' => $this->showPrice(),
                'filters' => [
                    'categories' => Category::active()->ordered()->get(['id', 'name', 'slug'])->map(fn (Category $category) => [
                        'id' => (string) $category->id,
                        'name' => $category->name,
                        'slug' => $category->slug,
                    ])->values(),
                    'tags' => Tag::active()->orderBy('name')->get(['id', 'name', 'slug'])->map(fn (Tag $tag) => [
                        'id' => (string) $tag->id,
                        'name' => $tag->name,
                        'slug' => $tag->slug,
                    ])->values(),
                ],
                'pagination' => [
                    'currentPage' => $courses->currentPage(),
                    'lastPage' => $courses->lastPage(),
                    'perPage' => $courses->perPage(),
                    'total' => $courses->total(),
                    'from' => $courses->firstItem(),
                    'to' => $courses->lastItem(),
                    'hasMorePages' => $courses->hasMorePages(),
                    'nextPageUrl' => $courses->nextPageUrl(),
                    'previousPageUrl' => $courses->previousPageUrl(),
                ],
            ],
        ]);
    }

    /**
     * Preview satu kursus: metadata + outline kurikulum (judul saja).
     */
    public function show(Request $request, Course $course): JsonResponse
    {
        $user = $request->user();

        $this->assertVisible($course, $user);

        $course->load([
            'instructors:id,name,avatar,instructor_bio',
            'salesProfile',
            'previews:id,course_id,content_id,sort_order',
            'categories' => fn ($query) => $query->active()->ordered()->select('categories.id', 'name', 'slug'),
            'tags' => fn ($query) => $query->active()->orderBy('name')->select('tags.id', 'name', 'slug'),
            'lessons' => fn ($q) => $q->select('id', 'course_id', 'title', 'order')->orderBy('order'),
            'lessons.contents' => fn ($q) => $q->select('id', 'lesson_id', 'title', 'type', 'order')->orderBy('order'),
        ]);

        $isEnrolled = $course->isEnrolledBy($user);

        $data = $this->transformCard($course, $isEnrolled ? [$course->id => true] : []);

        $data['description'] = $course->description ?? '';
        $data['salesProfile'] = $course->salesProfile ? [
            'slug' => $course->salesProfile->slug,
            'headline' => $course->salesProfile->headline,
            'targetAudience' => $course->salesProfile->target_audience,
            'learningBenefits' => $course->salesProfile->learning_benefits,
            'requirements' => $course->salesProfile->requirements,
            'level' => $course->salesProfile->level,
            'estimatedDurationMinutes' => $course->salesProfile->estimated_duration_minutes,
            'language' => $course->salesProfile->language,
            'promoVideoUrl' => $course->salesProfile->promo_video_url,
            'faq' => $course->salesProfile->faq ?? [],
        ] : null;
        $data['totalContents'] = $course->lessons->sum(fn ($lesson) => $lesson->contents->count());
        $previewIds = $course->previews->pluck('content_id');
        $data['sections'] = $course->lessons->values()->map(function ($lesson, $index) use ($course, $previewIds) {
            return [
                'id' => (string) $lesson->id,
                'sectionNumber' => $index + 1,
                'title' => $lesson->title,
                // Judul + tipe saja. Tidak ada body/file — kurikulum digembok
                // sampai user benar-benar ter-enroll.
                'lessons' => $lesson->contents->values()->map(function ($content) use ($course, $previewIds) {
                    $isPreview = $previewIds->contains($content->id);

                    return [
                        'id' => (string) $content->id,
                        'title' => $content->title,
                        'type' => $content->type ?? 'text',
                        'isPreview' => $isPreview,
                        'previewUrl' => $isPreview
                            ? route('api.mobile.catalog.preview', [$course, $content])
                            : null,
                    ];
                })->values(),
            ];
        })->values();

        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil mengambil detail katalog',
            'data' => $data,
            'meta' => ['showPrice' => $this->showPrice()],
        ]);
    }

    /**
     * Daftar langsung ke kursus katalog yang GRATIS.
     *
     * Tidak ada uang yang berpindah, jadi ini di luar lingkup Play Billing dan
     * aman dilakukan dari mobile. Kursus berbayar ditolak di sini — satu-satunya
     * jalur berbayar adalah checkout di web atau kode akses.
     */
    public function enrollFree(Request $request, Course $course): JsonResponse
    {
        $user = $request->user();

        $this->assertVisible($course, $user);

        if ($course->isPaid()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kursus ini tidak dapat diikuti langsung dari aplikasi.',
            ], 422);
        }

        if ($course->isEnrolledBy($user)) {
            return response()->json([
                'status' => 'success',
                'message' => 'Anda sudah terdaftar di kursus ini.',
                'data' => ['courseId' => (string) $course->id, 'isEnrolled' => true],
            ]);
        }

        // Enroll di level course (tanpa CourseClass) — jalur yang sama dipakai
        // ShopController::enrollFree() di web dan kode enrollment tanpa kelas.
        DB::transaction(function () use ($course, $user) {
            $course->enrolledUsers()->syncWithoutDetaching([$user->id]);
        });

        return response()->json([
            'status' => 'success',
            'message' => "Berhasil bergabung dengan kursus: {$course->title}",
            'data' => ['courseId' => (string) $course->id, 'isEnrolled' => true],
        ]);
    }

    /**
     * Bentuk kartu katalog. Harga hanya disertakan bila flag server mengizinkan;
     * `isPaid` selalu dikirim supaya mobile tetap bisa membedakan gratis/berbayar
     * tanpa perlu tahu nominalnya.
     *
     * @param  array<int, mixed>  $enrolledIds  peta id kursus yang sudah diikuti
     * @return array<string, mixed>
     */
    private function transformCard(Course $course, array $enrolledIds): array
    {
        $showPrice = $this->showPrice();

        return [
            'id' => (string) $course->id,
            'title' => $course->title,
            'shortDescription' => $course->salesProfile?->headline ?: ($course->short_description ?? ''),
            'instructor' => $course->instructors->pluck('name')->filter()->implode(', '),
            'instructors' => $course->instructors->map(fn (User $instructor) => [
                'id' => (string) $instructor->id,
                'name' => $instructor->name,
                'avatarUrl' => $instructor->avatar ? asset('storage/'.$instructor->avatar) : null,
                'bioHtml' => $instructor->instructor_bio,
            ])->values(),
            'thumbnailUrl' => $course->thumbnail ? asset('storage/'.$course->thumbnail) : null,
            // index() memakai withCount(); show() sudah memuat relasinya. Pakai
            // yang tersedia agar tidak ada COUNT tambahan per kursus.
            'lessonsCount' => (int) ($course->lessons_count
                ?? ($course->relationLoaded('lessons') ? $course->lessons->count() : $course->lessons()->count())),
            'isFree' => $course->isFree(),
            'isPaid' => $course->isPaid(),
            // null saat flag mati — mobile menampilkan label "Berbayar" saja.
            'price' => $showPrice && $course->isPaid() ? (int) $course->price : null,
            'priceLabel' => $course->isFree()
                ? 'Gratis'
                : ($showPrice ? $course->price_label : 'Berbayar'),
            'isEnrolled' => isset($enrolledIds[$course->id]),
            'categories' => $course->categories->map(fn ($category) => [
                'id' => (string) $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
            ])->values(),
            'tags' => $course->tags->map(fn ($tag) => [
                'id' => (string) $tag->id,
                'name' => $tag->name,
                'slug' => $tag->slug,
            ])->values(),
        ];
    }

    /**
     * 404 (bukan 403) untuk kursus di luar etalase — jangan bocorkan bahwa
     * sebuah kursus privat itu ada.
     */
    private function assertVisible(Course $course, ?User $user): void
    {
        abort_unless($course->isInCatalog(), 404);
    }

    /**
     * @param  array<int, int>  $courseIds
     * @return array<int, bool>
     */
    private function enrolledIds(?User $user, array $courseIds): array
    {
        if (! $user || empty($courseIds)) {
            return [];
        }

        return DB::table('course_user')
            ->where('user_id', $user->id)
            ->whereIn('course_id', $courseIds)
            ->pluck('course_id')
            ->flip()
            ->map(fn () => true)
            ->all();
    }

    private function showPrice(): bool
    {
        return (bool) config('shop.mobile_show_price', false);
    }
}
