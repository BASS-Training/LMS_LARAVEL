<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Content;
use Illuminate\Http\Request;

class LessonProgressApiController extends Controller
{
    public function complete(Request $request, Content $content)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $content->loadMissing('lesson.course');

        $user->completedContents()->syncWithoutDetaching([
            $content->id => [
                'completed' => true,
                'completed_at' => now(),
            ],
        ]);

        if ($content->lesson) {
            $content->lesson->loadMissing('contents');

            $allCompleted = $content->lesson->contents->every(function ($lessonContent) use ($user) {
                return $user->completedContents()
                    ->wherePivot('content_id', $lessonContent->id)
                    ->wherePivot('completed', true)
                    ->exists();
            });

            if ($allCompleted) {
                $user->lessons()->syncWithoutDetaching([
                    $content->lesson->id => [
                        'completed' => true,
                        'completed_at' => now(),
                    ],
                ]);
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Lesson marked as completed.',
            'data' => [
                'contentId' => (string) $content->id,
                'lessonId' => (string) ($content->lesson?->id ?? ''),
            ],
        ]);
    }
}
