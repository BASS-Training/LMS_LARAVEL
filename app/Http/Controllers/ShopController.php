<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Course;
use App\Models\LearningPath;
use App\Models\RefundSetting;
use App\Models\Tag;
use App\Services\Payment\ServiceFee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Etalase kursus (katalog publik).
 *
 * Sengaja DIPISAH dari CourseController: CoursePolicy::view() mewajibkan user
 * sudah ter-enroll, dan aturan itu tidak boleh dilonggarkan. Controller ini
 * hanya melayani course dengan status=published & visibility=catalog, dan
 * hanya menampilkan metadata + judul kurikulum — tidak pernah isi konten.
 *
 * Guest boleh membuka index & show (penting untuk share link / SEO).
 */
class ShopController extends Controller
{
    public function refundPolicy()
    {
        return view('shop.refund-policy', ['settings' => RefundSetting::current()]);
    }

    public function __construct()
    {
        // Hanya aksi yang mengubah data yang butuh login.
        $this->middleware('auth')->only('enrollFree');
    }

    public function index(Request $request)
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'min:2', 'max:100'],
            'harga' => ['nullable', Rule::in(['free', 'paid'])],
            'category' => ['nullable', 'string', Rule::exists('categories', 'slug')->where('is_active', true)],
            'tag' => ['nullable', 'string', Rule::exists('tags', 'slug')->where('is_active', true)],
            'sort' => ['nullable', Rule::in(['latest', 'price_asc', 'price_desc'])],
        ]);
        $search = $validated['q'] ?? null;
        $query = Course::inCatalog()->with([
            'instructors',
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

        // Filter harga: 'free' | 'paid'
        $priceFilter = $validated['harga'] ?? null;
        if ($priceFilter === 'free') {
            $query->where(fn ($q) => $q->whereNull('price')->orWhere('price', '<=', 0));
        } elseif ($priceFilter === 'paid') {
            $query->where('price', '>', 0);
        }

        $categoryFilter = $validated['category'] ?? null;
        if ($categoryFilter !== null) {
            $query->whereHas('categories', fn ($query) => $query->active()->where('slug', $categoryFilter));
        }

        $tagFilter = $validated['tag'] ?? null;
        if ($tagFilter !== null) {
            $query->whereHas('tags', fn ($query) => $query->active()->where('slug', $tagFilter));
        }

        $sort = $validated['sort'] ?? 'latest';
        match ($sort) {
            'price_asc' => $query->orderByRaw('COALESCE(price, 0) asc')->orderBy('id'),
            'price_desc' => $query->orderByDesc('price')->orderBy('id'),
            default => $query->latest()->orderByDesc('id'),
        };

        $courses = $query->withCount('lessons')
            ->paginate(8)
            ->withQueryString();

        return view('shop.index', [
            'courses' => $courses,
            'search' => $search,
            'priceFilter' => $priceFilter,
            'categoryFilter' => $categoryFilter,
            'tagFilter' => $tagFilter,
            'sort' => $sort,
            'categories' => Category::active()->ordered()->get(['id', 'name', 'slug']),
            'tags' => Tag::active()->orderBy('name')->get(['id', 'name', 'slug']),
            'enrolledIds' => $this->enrolledIds($courses->pluck('id')->all()),
            'managedIds' => $this->managedIds($courses),
        ]);
    }

    public function show(Course $course, ServiceFee $fee, \App\Services\FeatureAvailability $features)
    {
        abort_unless($course->isInCatalog(), 404);

        // Kurikulum: judul saja. Isi konten TIDAK pernah dikirim ke view.
        $course->load([
            'instructors:id,name,avatar,instructor_bio',
            'salesProfile',
            'previews:id,course_id,content_id,sort_order',
            'categories' => fn ($query) => $query->active()->ordered()->select('categories.id', 'name', 'slug'),
            'tags' => fn ($query) => $query->active()->orderBy('name')->select('tags.id', 'name', 'slug'),
            'lessons' => fn ($q) => $q->select('id', 'course_id', 'title', 'order')->orderBy('order'),
            'lessons.contents' => fn ($q) => $q->select('id', 'lesson_id', 'title', 'type', 'order')->orderBy('order'),
        ]);

        $user = Auth::user();
        $learningPaths = $features->learningPathsEnabled()
            ? LearningPath::query()
                ->inCatalog()
                ->visibleTo($user)
                ->whereHas('courses', fn ($query) => $query->whereKey($course->id))
                ->with('courses:id,title')
                ->get()
            : collect();

        // Rincian harga hanya relevan untuk kursus berbayar. Saat pemilihan
        // metode aktif, biaya layanan berbeda per metode → tampilkan estimasi
        // TERMURAH ("mulai dari"); rincian persis muncul di halaman pilih metode.
        $methodsEnabled = $course->isPaid() && $fee->methodsEnabled();
        $breakdown = $course->isPaid()
            ? ($methodsEnabled ? $fee->cheapest((int) $course->price) : $fee->forBase((int) $course->price))
            : null;

        return view('shop.show', [
            'course' => $course,
            'isEnrolled' => $course->isEnrolledBy($user),
            'isManager' => $course->isManagedBy($user),
            'totalContents' => $course->lessons->sum(fn ($lesson) => $lesson->contents->count()),
            'breakdown' => $breakdown,
            'methodsEnabled' => $methodsEnabled,
            'feeLabel' => $fee->label(),
            'learningPaths' => $learningPaths,
            'previewContentIds' => $course->previews->pluck('content_id'),
        ]);
    }

    /**
     * Daftar langsung untuk course katalog yang GRATIS.
     * Course berbayar tidak lewat sini — nanti lewat checkout (Fase 2).
     */
    public function enrollFree(Course $course)
    {
        abort_unless($course->isInCatalog(), 404);

        $user = Auth::user();

        // Pengelola sudah punya akses penuh — arahkan ke pengelolaan, bukan enroll.
        if ($course->isManagedBy($user)) {
            return redirect()->route('courses.show', $course)
                ->with('success', 'Anda pengelola kursus ini — semua materi bisa langsung dibuka.');
        }

        if ($course->isPaid()) {
            return back()->withErrors(['shop' => 'Kursus ini berbayar. Silakan lanjut ke pembayaran.']);
        }

        if ($course->isAvpnProgram() && ! $user->canAccessProgram('avpn_ai')) {
            return back()->withErrors([
                'shop' => 'Kursus ini khusus program AVPN. Akun Anda belum terverifikasi untuk program tersebut.',
            ]);
        }

        if ($course->isEnrolledBy($user)) {
            return redirect()->route('courses.show', $course)
                ->with('success', 'Anda sudah terdaftar di kursus ini.');
        }

        // Enroll di level course (tanpa batch/CourseClass) — jalur yang sama
        // dipakai EnrollmentApiController saat kode tidak terikat kelas.
        DB::transaction(function () use ($course, $user) {
            $course->enrolledUsers()->syncWithoutDetaching([$user->id]);
        });

        return redirect()->route('courses.show', $course)
            ->with('success', "Berhasil bergabung dengan kursus: {$course->title}");
    }

    /**
     * ID course yang sudah diikuti user login (buat menandai kartu "Sudah dimiliki").
     *
     * @param  array<int>  $courseIds
     * @return array<int>
     */
    private function enrolledIds(array $courseIds): array
    {
        $user = Auth::user();

        if (! $user || empty($courseIds)) {
            return [];
        }

        return $user->courses()
            ->whereIn('courses.id', $courseIds)
            ->pluck('courses.id')
            ->all();
    }

    /**
     * ID course yang DIKELOLA user login (super-admin: semua; instruktur:
     * course-nya) — buat menandai kartu "Dikelola" alih-alih tombol beli.
     *
     * @param  \Illuminate\Support\Collection<int,\App\Models\Course>  $courses
     * @return array<int>
     */
    private function managedIds($courses): array
    {
        $user = Auth::user();

        if (! $user) {
            return [];
        }

        if ($user->hasRole('super-admin')) {
            return $courses->pluck('id')->all();
        }

        return $courses->filter(fn ($c) => $c->isManagedBy($user))->pluck('id')->all();
    }
}
