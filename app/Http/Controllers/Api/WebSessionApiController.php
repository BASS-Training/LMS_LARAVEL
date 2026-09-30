<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Membuat tautan sekali-pakai untuk membuka halaman DETAIL KATALOG di browser
 * dalam keadaan sudah login.
 *
 * Prinsip keamanan: `api_token` mobile TIDAK PERNAH ikut ke URL/browser.
 * Server hanya menyimpan SHA-256 dari token acak di cache dengan TTL pendek,
 * lalu browser menukarnya menjadi session Laravel lewat HandoffController.
 *
 * URL tujuan dibangun dari `course_id` di sisi server (route('shop.show')),
 * sehingga klien tidak pernah menentukan tujuan — tidak mungkin open redirect.
 */
class WebSessionApiController extends Controller
{
    /** Masa berlaku tautan handoff, dalam detik. */
    public const TTL_SECONDS = 120;

    public function __invoke(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'course_id' => ['required', 'integer'],
        ]);

        $course = Course::find($payload['course_id']);

        if (! $course || ! $course->isInCatalog()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kursus tidak ditemukan.',
            ], 422);
        }

        $token = Str::random(64);

        Cache::put(
            self::cacheKey($token),
            [
                'user_id' => $request->user()->getAuthIdentifier(),
                // Path relatif — redirect selalu same-origin.
                'target' => route('shop.show', $course, false),
            ],
            now()->addSeconds(self::TTL_SECONDS)
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil membuat tautan website.',
            'data' => [
                'url' => rtrim(config('app.url'), '/').'/auth/handoff/'.$token,
                'expires_in' => self::TTL_SECONDS,
            ],
        ]);
    }

    /** Key cache disimpan dalam bentuk hash — token mentah tidak pernah disimpan. */
    public static function cacheKey(string $token): string
    {
        return 'web_handoff:'.hash('sha256', $token);
    }
}
