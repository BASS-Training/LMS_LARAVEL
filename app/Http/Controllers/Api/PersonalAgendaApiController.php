<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PersonalAgendaItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Mobile API agenda pribadi peserta. Murni per-user (tidak terkait course),
 * menggantikan penyimpanan lokal-saja sebelumnya agar tersinkron lintas device.
 */
class PersonalAgendaApiController extends Controller
{
    /** Daftar agenda pribadi user, diurutkan dari tanggal terdekat. */
    public function index(Request $request): JsonResponse
    {
        $items = $request->user()
            ->personalAgendaItems()
            ->orderBy('event_date')
            ->orderBy('hour')
            ->orderBy('minute')
            ->get()
            ->map(fn (PersonalAgendaItem $i) => $this->toPayload($i))
            ->values();

        return response()->json(['status' => 'success', 'data' => $items]);
    }

    /** Buat agenda baru. Mengembalikan item yang tersimpan (dengan id server). */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'note' => ['nullable', 'string'],
            'date' => ['required', 'date'],
            'hour' => ['nullable', 'integer', 'between:0,23'],
            'minute' => ['nullable', 'integer', 'between:0,59'],
        ]);

        $item = $request->user()->personalAgendaItems()->create([
            'title' => $data['title'],
            'note' => $data['note'] ?? null,
            'event_date' => $data['date'],
            'hour' => $data['hour'] ?? null,
            'minute' => $data['minute'] ?? null,
        ]);

        return response()->json(['status' => 'success', 'data' => $this->toPayload($item)]);
    }

    /** Hapus agenda milik user (scoped agar tidak bisa hapus milik orang lain). */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $deleted = $request->user()->personalAgendaItems()->whereKey($id)->delete();

        return response()->json(['status' => 'success', 'data' => ['deleted' => (bool) $deleted]]);
    }

    /** Bentuk JSON yang selaras dengan PersonalAgendaItem.fromMap di mobile. */
    private function toPayload(PersonalAgendaItem $item): array
    {
        return [
            'id' => (string) $item->id,
            'title' => $item->title,
            'note' => $item->note,
            'date' => $item->event_date?->toISOString(),
            'hour' => $item->hour,
            'minute' => $item->minute,
        ];
    }
}
