<?php

namespace App\Http\Controllers;

use App\Models\LearningPath;
use App\Services\LearningPathProgressService;
use Illuminate\Support\Facades\Auth;

class LearningPathController extends Controller
{
    public function show(LearningPath $learningPath, LearningPathProgressService $progressService)
    {
        $user = Auth::user();
        abort_unless($learningPath->isInCatalog($user), 404);

        $learningPath->load('courses.instructors:id,name');

        return view('shop.learning-path', [
            'learningPath' => $learningPath,
            'progress' => $user ? $progressService->forUser($learningPath, $user) : null,
        ]);
    }
}
