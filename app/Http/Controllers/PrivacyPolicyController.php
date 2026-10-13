<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class PrivacyPolicyController extends Controller
{
    public function __invoke(Request $request): View
    {
        $language = $request->query('lang') === 'en' ? 'en' : 'id';

        return view('legal.privacy-policy', [
            'language' => $language,
            'policy' => trans('privacy', [], $language),
        ]);
    }
}
