<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course; // Memanggil kerangka tabel Course

class CourseApiController extends Controller
{
    public function index()
    {
        $courses = Course::with(['lessons.contents.quiz', 'instructors'])->get()->map(function (Course $course) {
            return [
                'id' => (string) $course->id,
                'title' => $course->title,
                'description' => $course->description ?? '',
                'instructor' => $course->instructors->first()?->name ?? '',
                'color' => '#6C5CE7',
                'icon' => '📚',
                'chaptersCount' => $course->lessons->count(),
                'duration' => '0 min',
                'sections' => $course->lessons->values()->map(function ($lesson, $index) use ($course) {
                    return [
                        'id' => (string) $lesson->id,
                        'courseId' => (string) $course->id,
                        'sectionNumber' => $index + 1,
                        'title' => $lesson->title,
                        'description' => $lesson->description ?? '',
                        'lessons' => $lesson->contents->filter(function ($content) {
                            // Hide draft quizzes from peserta listing
                            if ($content->quiz_id && $content->quiz && $content->quiz->status === 'draft') {
                                return false;
                            }

                            return true;
                        })->map(function ($content) use ($course, $lesson) {
                            return [
                                'id' => (string) $content->id,
                                'courseId' => (string) $course->id,
                                'title' => $content->title,
                                'content' => in_array($content->type, ['video', 'text', 'document'])
                                    ? ($content->body ?? '')
                                    : ($content->description ?? ''),
                                'body' => $content->body ?? '',
                                'duration' => '0 min',
                                'type' => $content->type ?? 'text',
                                'quizId' => $content->quiz_id ? (string) $content->quiz_id : null,
                                'youtubeVideoId' => $content->youtube_video_id,
                                'isCompleted' => false,
                                'lessonId' => (string) $lesson->id,
                            ];
                        })->values(),
                    ];
                })->values(),
            ];
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil mengambil daftar course beserta section dan lesson',
            'data' => $courses
        ]);
    }
}
