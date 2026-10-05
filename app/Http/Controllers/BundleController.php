<?php

namespace App\Http\Controllers;

use App\Models\Bundle;
use App\Services\Payment\OrderService;
use App\Services\Payment\ServiceFee;
use Illuminate\Support\Facades\Auth;

class BundleController extends Controller
{
    public function index()
    {
        return view('bundles.index', [
            'bundles' => Bundle::inCatalog()
                ->visibleTo(Auth::user())
                ->with('courses:id,title,thumbnail,price')
                ->latest()
                ->paginate(12),
        ]);
    }

    public function show(Bundle $bundle, OrderService $orders, ServiceFee $fee)
    {
        $user = Auth::user();
        abort_unless($bundle->isVisibleInCatalog($user), 404);
        $bundle->load('courses.instructors');
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
