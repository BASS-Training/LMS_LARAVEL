<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserGameScore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Mobile API best score mini-game per peserta. Board/resume mid-game tetap
 * lokal di perangkat; di sini hanya best score & jumlah main yang disinkronkan.
 */
class GameScoreApiController extends Controller
{
    /** Semua best score milik user. */
    public function index(Request $request): JsonResponse
    {
        $scores = $request->user()
            ->gameScores()
            ->get()
            ->map(fn (UserGameScore $s) => $this->toPayload($s))
            ->values();

        return response()->json(['status' => 'success', 'data' => $scores]);
    }

    /** Catat hasil satu ronde: best = max, plays + 1. */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'gameId' => ['required', 'string', 'max:64'],
            'score' => ['required', 'integer', 'min:0'],
        ]);

        $row = $request->user()->gameScores()->firstOrNew(['game_id' => $data['gameId']]);
        $row->best_score = max($row->best_score ?? 0, $data['score']);
        $row->plays = ($row->plays ?? 0) + 1;
        $row->last_played_at = now();
        $row->save();

        return response()->json(['status' => 'success', 'data' => $this->toPayload($row)]);
    }

    /**
     * Merge agregat (mis. migrasi best score lokal lama): untuk tiap entri,
     * best = max(server, kiriman), plays = max(server, kiriman). Mengembalikan
     * seluruh daftar terkini.
     */
    public function merge(Request $request): JsonResponse
    {
        $data = $request->validate([
            'scores' => ['present', 'array'],
            'scores.*.gameId' => ['required', 'string', 'max:64'],
            'scores.*.highScore' => ['required', 'integer', 'min:0'],
            'scores.*.timesPlayed' => ['nullable', 'integer', 'min:0'],
            'scores.*.lastPlayedAt' => ['nullable', 'date'],
        ]);

        $user = $request->user();
        foreach ($data['scores'] as $entry) {
            $row = $user->gameScores()->firstOrNew(['game_id' => $entry['gameId']]);
            $row->best_score = max($row->best_score ?? 0, $entry['highScore']);
            $row->plays = max($row->plays ?? 0, $entry['timesPlayed'] ?? 0);
            if (! empty($entry['lastPlayedAt'])) {
                $incoming = \Illuminate\Support\Carbon::parse($entry['lastPlayedAt']);
                if (! $row->last_played_at || $incoming->gt($row->last_played_at)) {
                    $row->last_played_at = $incoming;
                }
            }
            $row->save();
        }

        $scores = $user->gameScores()->get()
            ->map(fn (UserGameScore $s) => $this->toPayload($s))
            ->values();

        return response()->json(['status' => 'success', 'data' => $scores]);
    }

    private function toPayload(UserGameScore $s): array
    {
        return [
            'gameId' => $s->game_id,
            'highScore' => (int) $s->best_score,
            'timesPlayed' => (int) $s->plays,
            'lastPlayedAt' => $s->last_played_at?->toISOString(),
        ];
    }
}
