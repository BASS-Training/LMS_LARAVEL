<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class DocumentController extends Controller
{
    /**
     * Serve a document file from the public disk with inline disposition.
     *
     * @param string $path
     * @return \Symfony\Component\HttpFoundation\StreamedResponse|\Illuminate\Http\Response
     */
    public function show($path)
    {
        // Prevent directory traversal
        $path = ltrim($path, '/\\');

        if (!Storage::disk('public')->exists($path)) {
            Log::info('DocumentController.show - file not found', ['path' => $path, 'ip' => request()->ip(), 'user_id' => auth()->id()]);
            return response()->json(['message' => 'File not found.'], 404);
        }

        // Use Storage::response to stream with inline disposition when possible
        try {
            Log::info('DocumentController.show - serving file', ['path' => $path, 'ip' => request()->ip(), 'user_id' => auth()->id()]);
            return Storage::disk('public')->response($path);
        } catch (\Exception $e) {
            Log::error('DocumentController.show - exception', ['path' => $path, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Unable to open file.'], 500);
        }
    }
}
