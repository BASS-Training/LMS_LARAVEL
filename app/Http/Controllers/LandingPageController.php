<?php

namespace App\Http\Controllers;

use App\Models\Bundle;
use App\Models\Course;
use App\Models\LearningPath;
use App\Services\FeatureAvailability;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LandingPageController extends Controller
{
    public function __invoke(FeatureAvailability $features): View
    {
        $courses = Course::inCatalog()
            ->with('instructors:id,name')
            ->withCount('lessons')
            ->latest()
            ->limit(8)
            ->get();

        $bundles = $features->bundlesEnabled()
            ? Bundle::inCatalog()
                ->visibleTo(Auth::user())
                ->with('courses:id,title,thumbnail,price')
                ->latest()
                ->limit(3)
                ->get()
            : collect();

        $learningPaths = $features->learningPathsEnabled()
            ? LearningPath::inCatalog()
                ->visibleTo(Auth::user())
                ->with('courses:id,title')
                ->latest()
                ->limit(3)
                ->get()
            : collect();

        return view('landing-page.index', [
            'courses' => $courses,
            'bundles' => $bundles,
            'learningPaths' => $learningPaths,
            'bundlesEnabled' => $features->bundlesEnabled(),
            'learningPathsEnabled' => $features->learningPathsEnabled(),
        ]);
    }
}
