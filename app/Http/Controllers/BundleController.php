<?php

namespace App\Http\Controllers;

use App\Models\Bundle;
use App\Services\Payment\OrderService;
use App\Services\Payment\ServiceFee;
use Illuminate\Support\Facades\Auth;

class BundleController extends Controller
{
    public function show(Bundle $bundle, OrderService $orders, ServiceFee $fee)
    {
        abort_unless($bundle->isInCatalog(), 404);
        $bundle->load('courses.instructors');
        $user = Auth::user();
        $pricing = $user ? $orders->bundlePricing($bundle, $user) : [
            'bundle_discount' => 0,
            'payable_base' => (int) $bundle->price,
            'owned_ids' => [],
            'all_owned' => false,
        ];
        $methodsEnabled = $fee->methodsEnabled();
        $breakdown = $methodsEnabled
            ? $fee->cheapest($pricing['payable_base'])
            : $fee->forBase($pricing['payable_base']);

        return view('shop.bundle', compact(
            'bundle',
            'pricing',
            'methodsEnabled',
            'breakdown',
        ));
    }
}
