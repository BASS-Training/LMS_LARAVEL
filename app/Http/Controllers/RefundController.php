<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Order;
use App\Services\Payment\RefundService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

class RefundController extends Controller
{
    public function __construct(private RefundService $refunds)
    {
        $this->middleware('auth');
    }

    public function store(Request $request, Order $order)
    {
        abort_unless($order->user_id === Auth::id(), 403);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        try {
            $refund = $this->refunds->request($order, Auth::user(), $validated['reason']);
        } catch (RuntimeException $exception) {
            return back()->withErrors(['refund' => $exception->getMessage()]);
        }

        ActivityLog::log('refund_requested', [
            'description' => "Mengajukan refund penuh untuk {$order->order_code}",
            'metadata' => ['order_id' => $order->id, 'refund_id' => $refund->id, 'amount' => $refund->amount],
        ]);

        return redirect()->route('checkout.finish', $order)
            ->with('success', 'Pengajuan refund berhasil dikirim dan menunggu keputusan admin.');
    }
}
