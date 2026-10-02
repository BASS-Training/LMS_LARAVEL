<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Order;
use App\Services\Payment\OrderService;
use App\Services\Payment\RefundService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Antrian verifikasi pembayaran manual (khusus super-admin).
 *
 * Course dengan `requires_payment_verification=true` tidak langsung membuka
 * akses saat lunas — uangnya dikonfirmasi Midtrans, lalu order menunggu di sini
 * sampai super-admin menyetujui/menolak. Enrollment tetap dilakukan OrderService
 * (idempotent + lockForUpdate), controller ini hanya jalur keputusannya.
 */
class PaymentVerificationController extends Controller
{
    public function __construct(
        private OrderService $orders,
        private RefundService $refunds,
    ) {}

    /** Antrian pesanan yang menunggu verifikasi + riwayat keputusan terbaru. */
    public function index()
    {
        $orders = Order::with(['user', 'course'])
            ->where('status', 'awaiting_verification')
            ->latest('payment_confirmed_at')
            ->paginate(15);

        $recent = Order::with(['user', 'course', 'verifiedBy'])
            ->whereIn('status', ['paid', 'rejected'])
            ->whereNotNull('verified_at')
            ->latest('verified_at')
            ->limit(8)
            ->get();

        return view('admin.payment-verifications.index', compact('orders', 'recent'));
    }

    /** Detail satu pesanan + bukti pembayaran dari Midtrans. */
    public function show(Order $order)
    {
        $order->load(['user', 'course', 'verifiedBy']);

        return view('admin.payment-verifications.show', compact('order'));
    }

    public function approve(Order $order)
    {
        if (! $order->isAwaitingVerification()) {
            return back()->withErrors(['verify' => 'Pesanan ini sudah diproses.']);
        }

        $this->orders->approve($order, Auth::user());

        ActivityLog::log('payment_verified', [
            'description' => "Menyetujui pembayaran {$order->order_code} ({$order->course->title})",
            'metadata' => [
                'order_code' => $order->order_code,
                'user_id' => $order->user_id,
                'course_id' => $order->course_id,
                'amount' => $order->amount,
            ],
        ]);

        return redirect()->route('admin.payment-verifications.index')
            ->with('success', "Pembayaran {$order->order_code} disetujui — peserta kini punya akses.");
    }

    public function reject(Request $request, Order $order)
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:3', 'max:255'],
        ]);

        if (! $order->isAwaitingVerification()) {
            return back()->withErrors(['verify' => 'Pesanan ini sudah diproses.']);
        }

        $order = $this->orders->reject($order, Auth::user(), $validated['reason']);
        $refund = $this->refunds->createApprovedForRejectedOrder(
            $order,
            Auth::user(),
            'Refund otomatis: pembayaran ditolak saat verifikasi. '.$validated['reason']
        );

        ActivityLog::log('payment_rejected', [
            'description' => "Menolak pembayaran {$order->order_code} ({$order->course->title})",
            'metadata' => [
                'order_code' => $order->order_code,
                'reason' => $validated['reason'],
                'refund_id' => $refund->id,
                'refund_status' => $refund->status->value,
            ],
        ]);

        if ($refund->isFailed()) {
            return redirect()->route('admin.refunds.show', $refund)
                ->withErrors(['refund' => 'Pembayaran ditolak, tetapi refund Midtrans gagal. Silakan coba ulang.']);
        }

        return redirect()->route('admin.payment-verifications.index')
            ->with('success', "Pembayaran {$order->order_code} ditolak dan full refund mulai diproses.");
    }
}
