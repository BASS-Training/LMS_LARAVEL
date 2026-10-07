<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BundleRequest;
use App\Models\ActivityLog;
use App\Models\Bundle;
use App\Models\Course;
use App\Models\FeatureSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BundleController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->string('search')->trim()->toString();
        $query = Bundle::query()->withCount(['courses', 'orders'])->latest();
        if ($search !== '') {
            $query->where(fn ($query) => $query
                ->where('title', 'like', "%{$search}%")
                ->orWhere('slug', 'like', "%{$search}%"));
        }

        return view('admin.bundles.index', [
            'bundles' => $query->paginate(15)->withQueryString(),
            'search' => $search,
            'featureSettings' => FeatureSetting::current(),
        ]);
    }

    public function updateAvailability(Request $request)
    {
        $validated = $request->validate([
            'enabled' => ['required', 'boolean'],
        ]);
        $settings = FeatureSetting::current();
        $previous = $settings->bundles_enabled;
        $settings->update(['bundles_enabled' => $validated['enabled']]);

        ActivityLog::log('bundle_availability_updated', [
            'description' => 'Memperbarui ketersediaan publik Bundle',
            'metadata' => ['before' => $previous, 'after' => $settings->bundles_enabled],
        ]);

        return back()->with('success', 'Ketersediaan Bundle berhasil diperbarui.');
    }

    public function create()
    {
        return view('admin.bundles.create', [
            'bundle' => new Bundle,
            'selectedCourses' => $this->selectedCourses([]),
        ]);
    }

    public function courseOptions(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
        $search = trim((string) ($validated['q'] ?? ''));
        $query = $this->courseQuery();

        if ($search !== '') {
            $query->where('title', 'like', "%{$search}%");
        }

        $courses = $query->paginate(12);

        return response()->json([
            'data' => $courses->getCollection()->map(fn (Course $course) => $this->courseOption($course))->values(),
            'meta' => [
                'currentPage' => $courses->currentPage(),
                'lastPage' => $courses->lastPage(),
                'total' => $courses->total(),
            ],
        ]);
    }

    public function store(BundleRequest $request)
    {
        $bundle = Bundle::create($request->safe()->except('course_ids'));
        $this->syncCourses($bundle, $request->input('course_ids', []));
        $this->log('bundle_created', $bundle, 'Membuat bundle '.$bundle->title);

        return redirect()->route('admin.bundles.index')->with('success', 'Bundle berhasil dibuat.');
    }

    public function edit(Bundle $bundle)
    {
        return view('admin.bundles.edit', [
            'bundle' => $bundle,
            'selectedCourses' => $this->selectedCourses($bundle->courses()->pluck('courses.id')->all()),
        ]);
    }

    public function update(BundleRequest $request, Bundle $bundle)
    {
        $before = $bundle->only($bundle->getFillable());
        $bundle->update($request->safe()->except('course_ids'));
        $this->syncCourses($bundle, $request->input('course_ids', []));
        $this->log('bundle_updated', $bundle, 'Memperbarui bundle '.$bundle->title, ['before' => $before]);

        return redirect()->route('admin.bundles.index')->with('success', 'Bundle berhasil diperbarui.');
    }

    public function destroy(Bundle $bundle)
    {
        if ($bundle->orders()->exists()) {
            return back()->withErrors(['bundle' => 'Bundle yang sudah memiliki pesanan tidak dapat dihapus. Nonaktifkan sebagai gantinya.']);
        }

        $title = $bundle->title;
        $bundle->delete();
        $this->log('bundle_deleted', $bundle, 'Menghapus bundle '.$title);

        return back()->with('success', 'Bundle berhasil dihapus.');
    }

    private function courseQuery()
    {
        return Course::query()
            ->inCatalog()
            ->where('price', '>', 0)
            ->orderBy('title');
    }

    /** @param array<int|string> $defaultIds */
    private function selectedCourses(array $defaultIds)
    {
        $ids = array_values(array_unique(array_map(
            'intval',
            (array) session()->getOldInput('course_ids', $defaultIds),
        )));

        if ($ids === []) {
            return collect();
        }

        $courses = $this->courseQuery()->whereKey($ids)->get(['id', 'title', 'price'])->keyBy('id');

        return collect($ids)
            ->map(fn (int $id) => $courses->get($id))
            ->filter()
            ->values();
    }

    /** @return array{id:int,title:string,price:int,priceLabel:string} */
    private function courseOption(Course $course): array
    {
        return [
            'id' => (int) $course->id,
            'title' => $course->title,
            'price' => (int) $course->price,
            'priceLabel' => 'Rp '.number_format($course->price, 0, ',', '.'),
        ];
    }

    /** @param array<int|string> $courseIds */
    private function syncCourses(Bundle $bundle, array $courseIds): void
    {
        $sync = [];
        foreach (array_values($courseIds) as $index => $courseId) {
            $sync[(int) $courseId] = ['sort_order' => $index];
        }
        $bundle->courses()->sync($sync);
    }

    private function log(string $action, Bundle $bundle, string $description, array $extra = []): void
    {
        ActivityLog::log($action, [
            'description' => $description,
            'metadata' => [
                'bundle_id' => $bundle->id,
                'slug' => $bundle->slug,
                'course_ids' => $bundle->courses()->pluck('courses.id')->all(),
            ] + $extra,
        ]);
    }
}
