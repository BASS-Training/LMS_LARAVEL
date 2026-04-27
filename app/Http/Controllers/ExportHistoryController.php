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

        return Storage::download($export->file_path, $filename);
    }
}
