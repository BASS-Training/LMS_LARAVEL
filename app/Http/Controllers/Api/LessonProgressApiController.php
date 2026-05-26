<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Content;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LessonProgressApiController extends Controller
{
    public function complete(Request $request, Content $content)
    {
        $user = $request->user();
        Log::info('LessonProgressApiController.complete - called', ['content_id' => $content->id, 'user_id' => $user ? $user->id : null, 'ip' => $request->ip()]);

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

        $response = ['success' => true, 'contentId' => $content->id, 'lessonId' => $content->lesson?->id];
        Log::info('LessonProgressApiController.complete - success', $response + ['user_id' => $user ? $user->id : null]);
        return response()->json($response);
    }
}
