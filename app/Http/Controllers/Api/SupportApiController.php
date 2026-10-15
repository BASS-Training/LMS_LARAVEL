<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\CustomerServiceContent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupportApiController extends Controller
{
    public function privacyPolicy(Request $request): JsonResponse
    {
        $language = $request->query('lang') === 'en' ? 'en' : 'id';

        return response()->json([
            'status' => 'success',
            'data' => [
                'language' => $language,
                'url' => route('privacy-policy', ['lang' => $language]),
                'policy' => trans('privacy', [], $language),
                'contact_email' => 'admin@basstrainingacademy.com',
            ],
        ]);
    }

    public function customerService(): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => CustomerServiceContent::data(),
        ]);
    }
}
