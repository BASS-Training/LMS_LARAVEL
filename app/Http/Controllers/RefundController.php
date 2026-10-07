<?php

namespace App\Http\Controllers;

use App\Enums\RefundReason;
use App\Models\ActivityLog;
use App\Models\Order;
use App\Services\Payment\RefundService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
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

        $companyIssue = $order->refund_policy_mode === 'company_issue';

        $validated = $request->validate([
            'reason_type' => ['required', Rule::enum(RefundReason::class), ...($companyIssue ? [Rule::in([
                RefundReason::TechnicalIssue->value,
                RefundReason::Other->value,
            ])] : [])],
            'reason_other' => [
                Rule::requiredIf($companyIssue || $request->input('reason_type') === RefundReason::Other->value),
                'nullable',
                'string',
                'min:10',
                'max:1000',
            ],
        ]);

        $reasonType = RefundReason::from($validated['reason_type']);
        $reason = $companyIssue || $reasonType === RefundReason::Other
            ? $reasonType->label().': '.trim($validated['reason_other'])
            : $reasonType->label();

        try {
            $refund = $this->refunds->request($order, Auth::user(), $reason);
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
