<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bundle;
use App\Models\Course;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BundleApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min(50, max(1, (int) $request->integer('perPage', 20)));
        $query = Bundle::query()->inCatalog()->with('courses')->latest();
        $bundles = $query->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil mengambil paket kursus',
            'data' => $bundles->getCollection()->map(fn (Bundle $bundle) => $this->transform($bundle, $request->user())),
            'meta' => [
                'currentPage' => $bundles->currentPage(),
                'lastPage' => $bundles->lastPage(),
                'perPage' => $bundles->perPage(),
                'total' => $bundles->total(),
                'showPrice' => (bool) config('shop.mobile_show_price', false),
            ],
        ]);
    }

    public function show(Request $request, Bundle $bundle): JsonResponse
    {
        abort_unless($bundle->isInCatalog(), 404);
        $bundle->load('courses');

        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil mengambil detail paket kursus',
            'data' => $this->transform($bundle, $request->user()) + [
                'description' => $bundle->description,
                'courses' => $bundle->courses->map(fn (Course $course) => [
                    'id' => (string) $course->id,
                    'title' => $course->title,
                    'thumbnailUrl' => $course->thumbnail ? asset('storage/'.$course->thumbnail) : null,
                    'price' => config('shop.mobile_show_price') ? $course->price : null,
                    'priceLabel' => config('shop.mobile_show_price') ? $course->price_label : 'Berbayar',
                ])->values(),
            ],
            'meta' => ['showPrice' => (bool) config('shop.mobile_show_price', false)],
        ]);
    }

    private function transform(Bundle $bundle, $user): array
    {
        $courseIds = $bundle->courses->pluck('id');
        $ownedCount = $user ? $user->courses()->whereIn('courses.id', $courseIds)->count() : 0;

        return [
            'id' => (string) $bundle->id,
            'slug' => $bundle->slug,
            'title' => $bundle->title,
            'thumbnailUrl' => $bundle->courses->first()?->thumbnail
                ? asset('storage/'.$bundle->courses->first()->thumbnail)
                : null,
            'price' => config('shop.mobile_show_price') ? $bundle->price : null,
            'priceLabel' => config('shop.mobile_show_price') ? $bundle->price_label : 'Berbayar',
            'originalPriceLabel' => config('shop.mobile_show_price') ? $bundle->original_price_label : null,
            'savingsLabel' => config('shop.mobile_show_price') ? $bundle->savings_label : null,
            'coursesCount' => $bundle->courses->count(),
            'ownedCoursesCount' => $ownedCount,
            'isOwned' => $courseIds->isNotEmpty() && $ownedCount === $courseIds->count(),
        ];
    }
}
