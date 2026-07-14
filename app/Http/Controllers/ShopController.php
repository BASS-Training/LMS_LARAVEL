<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
    public function __construct()
    {
        // Hanya aksi yang mengubah data yang butuh login.
        $this->middleware('auth')->only('enrollFree');
    }

    public function index(Request $request)
    {
        $search = null;
        $query = Course::inCatalog()->with('instructors');

        if ($request->filled('q')) {
            $validated = $request->validate(['q' => 'required|string|min:2|max:100']);
            $search = $validated['q'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                    ->orWhere('short_description', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        // Filter harga: 'free' | 'paid'
        $priceFilter = $request->input('harga');
        if ($priceFilter === 'free') {
            $query->where(fn ($q) => $q->whereNull('price')->orWhere('price', '<=', 0));
        } elseif ($priceFilter === 'paid') {
            $query->where('price', '>', 0);
        } else {
            $priceFilter = null;
        }

        $courses = $query->withCount('lessons')
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('shop.index', [
            'courses' => $courses,
            'search' => $search,
            'priceFilter' => $priceFilter,
            'enrolledIds' => $this->enrolledIds($courses->pluck('id')->all()),
        ]);
    }

    public function show(Course $course)
    {
        abort_unless($course->isInCatalog(), 404);

        // Kurikulum: judul saja. Isi konten TIDAK pernah dikirim ke view.
        $course->load([
            'instructors:id,name',
            'lessons' => fn ($q) => $q->select('id', 'course_id', 'title', 'order')->orderBy('order'),
            'lessons.contents' => fn ($q) => $q->select('id', 'lesson_id', 'title', 'type', 'order')->orderBy('order'),
        ]);

        $user = Auth::user();

        return view('shop.show', [
            'course' => $course,
            'isEnrolled' => $course->isEnrolledBy($user),
            'totalContents' => $course->lessons->sum(fn ($lesson) => $lesson->contents->count()),
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
}
