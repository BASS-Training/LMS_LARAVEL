<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
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
 *  1. Wajib login (mobile selalu punya sesi) — tidak melayani tamu.
 *  2. TIDAK ADA jalur pembelian. Kebijakan anti-steering Google Play melarang
 *     aplikasi mengarahkan pengguna ke pembayaran di luar Play Billing, jadi
 *     mobile tidak mengirim tombol beli, link checkout, maupun ajakan membeli
 *     di web. Kursus berbayar hanya bisa di-preview; jalan masuknya adalah
 *     kode akses (EnrollmentApiController) atau pembelian di web.
 *
 * Yang dikirim hanya metadata + JUDUL kurikulum. Isi konten (body, file,
 * template soal) tidak pernah ikut — persis seperti halaman preview di web.
 */
class ShopApiController extends Controller
{
    /**
     * Daftar kursus di etalase. Mendukung pencarian & filter gratis/berbayar.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => 'nullable|string|max:100',
            'harga' => 'nullable|in:free,paid',
        ]);

        $user = $request->user();
        $search = $validated['q'] ?? null;

        $query = Course::inCatalog()->with('instructors:id,name');

        $this->hideRestrictedPrograms($query, $user);

        if ($search !== null && mb_strlen($search) >= 2) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%'.$search.'%')
                    ->orWhere('short_description', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%');
            });
        }

        $priceFilter = $validated['harga'] ?? null;
        if ($priceFilter === 'free') {
            $query->where(fn ($q) => $q->whereNull('price')->orWhere('price', '<=', 0));
        } elseif ($priceFilter === 'paid') {
            $query->where('price', '>', 0);
        }

        $courses = $query->withCount('lessons')->latest()->get();

        // Satu query untuk semua, daripada isEnrolledBy() per baris (N+1).
        $enrolledIds = $this->enrolledIds($user, $courses->pluck('id')->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil mengambil katalog kursus',
            'data' => $courses->map(
                fn (Course $course) => $this->transformCard($course, $enrolledIds)
            )->values(),
            'meta' => [
                // Mobile membaca ini untuk memutuskan menampilkan harga atau
                // sekadar label "Berbayar". Lihat config/shop.php.
                'showPrice' => $this->showPrice(),
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
            'instructors:id,name',
            'lessons' => fn ($q) => $q->select('id', 'course_id', 'title', 'order')->orderBy('order'),
            'lessons.contents' => fn ($q) => $q->select('id', 'lesson_id', 'title', 'type', 'order')->orderBy('order'),
        ]);

        $isEnrolled = $course->isEnrolledBy($user);

        $data = $this->transformCard($course, $isEnrolled ? [$course->id => true] : []);

        $data['description'] = $course->description ?? '';
        $data['totalContents'] = $course->lessons->sum(fn ($lesson) => $lesson->contents->count());
        $data['sections'] = $course->lessons->values()->map(function ($lesson, $index) {
            return [
                'id' => (string) $lesson->id,
                'sectionNumber' => $index + 1,
                'title' => $lesson->title,
                // Judul + tipe saja. Tidak ada body/file — kurikulum digembok
                // sampai user benar-benar ter-enroll.
                'lessons' => $lesson->contents->values()->map(fn ($content) => [
                    'id' => (string) $content->id,
                    'title' => $content->title,
                    'type' => $content->type ?? 'text',
                ])->values(),
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
            'shortDescription' => $course->short_description ?? '',
            'instructor' => $course->instructors->pluck('name')->filter()->implode(', '),
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
        ];
    }

    /**
     * Kursus AVPN hanya tampil untuk akun yang sudah disetujui — aturan yang
     * sama dipakai CourseApiController::index(). Tanpa ini, etalase jadi pintu
     * belakang untuk melihat program terbatas.
     */
    private function hideRestrictedPrograms($query, ?User $user): void
    {
        if (! $user || ! $user->isAvpnApproved()) {
            $query->where('program_type', '!=', 'avpn_ai');
        }
    }

    /**
     * 404 (bukan 403) untuk kursus di luar etalase — jangan bocorkan bahwa
     * sebuah kursus privat itu ada.
     */
    private function assertVisible(Course $course, ?User $user): void
    {
        abort_unless($course->isInCatalog(), 404);
        abort_unless($user && $user->canAccessProgram($course->program_type ?? ''), 404);
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
