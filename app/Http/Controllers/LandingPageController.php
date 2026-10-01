<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\View\View;

class LandingPageController extends Controller
{
    public function __invoke(): View
    {
        $courses = Course::inCatalog()
            ->with('instructors:id,name')
            ->withCount('lessons')
            ->latest()
            ->limit(8)
            ->get();

        return view('landing-page.index', [
            'courses' => $courses,
        ]);
    }
}
