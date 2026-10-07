<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LearningPathRequest;
use App\Models\ActivityLog;
use App\Models\Course;
use App\Models\FeatureSetting;
use App\Models\LearningPath;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LearningPathController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->string('search')->trim()->toString();
        $query = LearningPath::query()->withCount('courses')->latest();
        if ($search !== '') {
            $query->where(fn ($query) => $query
                ->where('title', 'like', "%{$search}%")
                ->orWhere('slug', 'like', "%{$search}%"));
        }

        return view('admin.learning-paths.index', [
            'learningPaths' => $query->paginate(15)->withQueryString(),
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
        $previous = $settings->learning_paths_enabled;
        $settings->update(['learning_paths_enabled' => $validated['enabled']]);

        ActivityLog::log('learning_path_availability_updated', [
            'description' => 'Memperbarui ketersediaan publik Learning Path',
            'metadata' => ['before' => $previous, 'after' => $settings->learning_paths_enabled],
        ]);

        return back()->with('success', 'Ketersediaan Learning Path berhasil diperbarui.');
    }

    public function create()
    {
        return view('admin.learning-paths.create', [
            'learningPath' => new LearningPath,
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

    public function store(LearningPathRequest $request)
    {
        $learningPath = LearningPath::create($request->safe()->except('course_ids'));
        $this->syncCourses($learningPath, $request->input('course_ids', []));
        $this->log('learning_path_created', $learningPath, 'Membuat learning path '.$learningPath->title);

        return redirect()->route('admin.learning-paths.index')->with('success', 'Learning path berhasil dibuat.');
    }

    public function edit(LearningPath $learningPath)
    {
        return view('admin.learning-paths.edit', [
            'learningPath' => $learningPath,
            'selectedCourses' => $this->selectedCourses($learningPath->courses()->pluck('courses.id')->all()),
        ]);
    }

    public function update(LearningPathRequest $request, LearningPath $learningPath)
    {
        $before = $learningPath->only($learningPath->getFillable());
        $learningPath->update($request->safe()->except('course_ids'));
        $this->syncCourses($learningPath, $request->input('course_ids', []));
        $this->log('learning_path_updated', $learningPath, 'Memperbarui learning path '.$learningPath->title, ['before' => $before]);

        return redirect()->route('admin.learning-paths.index')->with('success', 'Learning path berhasil diperbarui.');
    }

    public function destroy(LearningPath $learningPath)
    {
        $title = $learningPath->title;
        $learningPath->delete();
        $this->log('learning_path_deleted', $learningPath, 'Menghapus learning path '.$title);

        return back()->with('success', 'Learning path berhasil dihapus.');
    }

    private function courseQuery()
    {
        return Course::query()
            ->inCatalog()
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

        return collect($ids)->map(fn (int $id) => $courses->get($id))->filter()->values();
    }

    /** @return array{id:int,title:string,priceLabel:string} */
    private function courseOption(Course $course): array
    {
        return [
            'id' => (int) $course->id,
            'title' => $course->title,
            'priceLabel' => $course->price_label,
        ];
    }

    /** @param array<int|string> $courseIds */
    private function syncCourses(LearningPath $learningPath, array $courseIds): void
    {
        $sync = [];
        foreach (array_values($courseIds) as $index => $courseId) {
            $sync[(int) $courseId] = ['sort_order' => $index];
        }
        $learningPath->courses()->sync($sync);
    }

    private function log(string $action, LearningPath $learningPath, string $description, array $extra = []): void
    {
        ActivityLog::log($action, [
            'description' => $description,
            'metadata' => [
                'learning_path_id' => $learningPath->id,
                'slug' => $learningPath->slug,
                'course_ids' => $learningPath->courses()->pluck('courses.id')->all(),
            ] + $extra,
        ]);
    }
}
