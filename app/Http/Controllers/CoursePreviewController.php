<?php

namespace App\Http\Controllers;

use App\Models\Content;
use App\Models\Course;
use App\Models\CoursePreview;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class CoursePreviewController extends Controller
{
    public function show(Course $course, Content $content): View
    {
        $this->authorizePreview($course, $content);
        $content->load(['lesson:id,course_id,title', 'images:id,content_id,file_path,order']);

        return view('shop.preview', compact('course', 'content'));
    }

    public function apiShow(Course $course, Content $content): JsonResponse
    {
        $this->authorizePreview($course, $content);
        $content->load(['lesson:id,course_id,title', 'images:id,content_id,file_path,order']);

        $data = [
            'courseId' => (string) $course->id,
            'courseTitle' => $course->title,
            'contentId' => (string) $content->id,
            'lessonTitle' => $content->lesson->title,
            'title' => $content->title,
            'type' => $content->type,
            'descriptionHtml' => $content->description,
        ];

        if ($content->type === 'text') {
            $data['bodyHtml'] = $content->body;
        } elseif ($content->type === 'video') {
            $data['videoUrl'] = $content->body;
            $data['embedUrl'] = $content->youtube_embed_url;
            $data['thumbnailUrl'] = $content->youtube_thumbnail_url;
        } elseif ($content->type === 'image') {
            $paths = $content->images->pluck('file_path');

            if ($paths->isEmpty() && $content->file_path) {
                $paths = collect([$content->file_path]);
            }

            $data['images'] = $paths
                ->map(fn (string $path) => url(Storage::url($path)))
                ->values()
                ->all();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil mengambil preview materi',
            'data' => $data,
        ]);
    }

    private function authorizePreview(Course $course, Content $content): void
    {
        abort_unless($course->isInCatalog(), 404);
        abort_unless(in_array($content->type, CoursePreview::PREVIEWABLE_TYPES, true), 404);
        abort_unless(
            $content->lesson()->where('course_id', $course->id)->exists()
                && $course->previews()->where('content_id', $content->id)->exists(),
            404
        );
    }
}
