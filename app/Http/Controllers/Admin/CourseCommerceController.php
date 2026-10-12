<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CourseCommerceRequest;
use App\Models\ActivityLog;
use App\Models\Course;
use App\Models\CoursePreview;
use App\Models\CourseSalesProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CourseCommerceController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['private', 'draft', 'published', 'hidden'])],
        ]);
        $search = trim((string) ($validated['q'] ?? ''));
        $status = $validated['status'] ?? null;
        $query = Course::query()
            ->where('program_type', 'regular')
            ->with('salesProfile')
            ->latest();

        if ($search !== '') {
            $query->where('title', 'like', "%{$search}%");
        }

        if ($status === 'private') {
            $query->where('visibility', 'private');
        } elseif ($status !== null) {
            $query->where('visibility', 'catalog')
                ->whereHas('salesProfile', fn ($query) => $query->where('sales_status', $status));
        }

        return view('admin.course-commerce.index', [
            'courses' => $query->paginate(15)->withQueryString(),
            'search' => $search,
            'status' => $status,
        ]);
    }

    public function edit(Course $course)
    {
        abort_unless($course->isRegularProgram(), 404);
        $course->load([
            'salesProfile',
            'previews:id,course_id,content_id,sort_order',
            'lessons.contents' => fn ($query) => $query
                ->whereIn('type', CoursePreview::PREVIEWABLE_TYPES)
                ->orderBy('order'),
        ]);

        return view('admin.course-commerce.edit', compact('course'));
    }

    public function update(CourseCommerceRequest $request, Course $course)
    {
        $validated = $request->validated();
        $profileData = $validated['sales_profile'];
        $previewContentIds = collect($validated['preview_content_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->values();
        unset($validated['sales_profile']);
        unset($validated['preview_content_ids']);

        if ($validated['visibility'] !== 'catalog') {
            $validated['visibility'] = 'private';
            $validated['price'] = null;
            $validated['short_description'] = null;
            $validated['requires_payment_verification'] = false;
            $profileData['sales_status'] = 'hidden';
        } else {
            $validated['price'] = (int) ($validated['price'] ?? 0);
        }

        DB::transaction(function () use ($course, $validated, $profileData, $previewContentIds) {
            $before = $course->only(['visibility', 'price', 'short_description', 'requires_payment_verification']);
            $beforePreviewIds = $course->previews()->pluck('content_id')->all();
            $course->update($validated);
            $this->syncSalesProfile($course, $profileData);
            $this->syncPreviews($course, $previewContentIds->all());

            ActivityLog::log('course_commerce_updated', [
                'description' => 'Memperbarui penjualan course: '.$course->title,
                'metadata' => [
                    'course_id' => $course->id,
                    'before' => $before,
                    'after' => $course->only(array_keys($before)),
                    'sales_status' => $profileData['sales_status'],
                    'preview_content_ids_before' => $beforePreviewIds,
                    'preview_content_ids_after' => $previewContentIds->all(),
                ],
            ]);
        });

        return redirect()->route('admin.course-commerce.index')
            ->with('success', 'Pengaturan penjualan course berhasil diperbarui.');
    }

    private function syncSalesProfile(Course $course, array $data): void
    {
        $existing = $course->salesProfile;
        $faq = collect($data['faq'] ?? [])
            ->map(fn (array $item) => [
                'question' => trim((string) ($item['question'] ?? '')),
                'answer' => trim((string) ($item['answer'] ?? '')),
            ])
            ->filter(fn (array $item) => $item['question'] !== '' && $item['answer'] !== '')
            ->values()
            ->all();

        $data['slug'] = $data['slug'] ?? $existing?->slug ?? $this->uniqueSlug($course->title, $existing?->id);
        $data['faq'] = $faq ?: null;
        $data['published_at'] = $data['sales_status'] === 'published'
            ? ($existing?->published_at ?? now())
            : $existing?->published_at;

        $course->salesProfile()->updateOrCreate([], $data);
    }

    private function syncPreviews(Course $course, array $contentIds): void
    {
        $course->previews()->delete();

        if ($contentIds === []) {
            return;
        }

        $course->previews()->createMany(
            collect($contentIds)->values()->map(fn (int $contentId, int $index) => [
                'content_id' => $contentId,
                'sort_order' => $index,
            ])->all()
        );
    }

    private function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'course';
        $slug = $base;
        $suffix = 2;

        while (CourseSalesProfile::query()
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->where('slug', $slug)
            ->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}
