<?php

namespace App\Http\Controllers;

use App\Models\ExportHistory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ExportHistoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function download(ExportHistory $export)
    {
        abort_if($export->user_id !== Auth::id(), 403);
        abort_if(! $export->isDone() || ! $export->fileExists(), 404);

        $filename = sprintf(
            'peserta-%s-%s.xlsx',
            $export->course_id,
            $export->created_at->format('Ymd_His')
        );

        return Storage::disk('local')->download($export->file_path, $filename);
    }

    public function pendingNotifications()
    {
        $user = Auth::user();

        $notifications = $user->unreadNotifications()
            ->where('type', \App\Notifications\ExportCompletedNotification::class)
            ->latest()
            ->get();

        $result = $notifications->map(function ($n) {
            $data = $n->data;
            return [
                'id'           => $n->id,
                'export_id'    => $data['export_id'] ?? null,
                'course_title' => $data['course_title'] ?? '',
                'filter'       => $data['filter'] ?? '',
                'exported_at'  => $data['exported_at'] ?? null,
                'download_url' => $data['export_id']
                    ? route('exports.download', ['export' => $data['export_id']])
                    : null,
            ];
        });

        // Mark them as read so they don't re-appear on next poll
        $notifications->each->markAsRead();

        return response()->json(['notifications' => $result]);
    }
}
