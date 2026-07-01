<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Mobile API baseline perayaan achievement. Menyimpan tier tertinggi yang sudah
 * dirayakan per achievement, agar perayaan tidak muncul ulang di device baru.
 */
class AchievementApiController extends Controller
{
    /** Map { achievementId: tier } milik user. */
    public function index(Request $request): JsonResponse
    {
        $tiers = $request->user()
            ->achievementTiers()
            ->pluck('tier', 'achievement_id');

        return response()->json(['status' => 'success', 'data' => $tiers]);
    }

    /**
     * Upsert baseline: tier = max(server, kiriman). Mengembalikan map terkini.
     */
    public function sync(Request $request): JsonResponse
    {
        $data = $request->validate([
            'tiers' => ['required', 'array'],
            'tiers.*' => ['integer', 'min:0'],
        ]);

        $user = $request->user();
        foreach ($data['tiers'] as $achievementId => $tier) {
            $row = $user->achievementTiers()->firstOrNew([
                'achievement_id' => (string) $achievementId,
            ]);
            $row->tier = max($row->tier ?? 0, (int) $tier);
            $row->save();
        }

        $tiers = $user->achievementTiers()->pluck('tier', 'achievement_id');

        return response()->json(['status' => 'success', 'data' => $tiers]);
    }
}
