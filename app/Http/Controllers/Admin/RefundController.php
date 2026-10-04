<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RefundStatus;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Refund;
use App\Models\RefundSetting;
use App\Services\Payment\RefundService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

class RefundController extends Controller
{
    public function __construct(private RefundService $refunds) {}

    public function index(Request $request)
    {
        $status = $request->string('status')->toString();
        $statusCounts = Refund::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');
        $query = Refund::with(['order.user', 'order.course', 'reviewer'])->latest();

        if ($status && in_array($status, array_column(RefundStatus::cases(), 'value'), true)) {
            $query->where('status', $status);
        }

        return view('admin.refunds.index', [
            'refunds' => $query->paginate(15)->withQueryString(),
            'status' => $status,
            'statusCounts' => $statusCounts,
            'attentionCount' => collect([
                RefundStatus::Requested,
                RefundStatus::Approved,
                RefundStatus::Failed,
                RefundStatus::ManualRequired,
            ])->sum(fn (RefundStatus $item) => (int) ($statusCounts[$item->value] ?? 0)),
            'processingCount' => (int) ($statusCounts[RefundStatus::Processing->value] ?? 0),
            'refundedAmount' => Refund::query()
                ->where('status', RefundStatus::Refunded)
                ->sum('amount'),
            'settings' => RefundSetting::current(),
        ]);
    }

    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'request_window_days' => ['required', 'integer', 'min:1', 'max:365'],
            'max_progress_percentage' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        $settings = RefundSetting::current();
        $previous = $settings->only(['request_window_days', 'max_progress_percentage']);
        $settings->update($validated);

        ActivityLog::log('refund_settings_updated', [
            'description' => 'Memperbarui kebijakan global refund',
            'metadata' => ['before' => $previous, 'after' => $settings->fresh()->only(array_keys($previous))],
        ]);

        return back()->with('success', 'Kebijakan refund berhasil diperbarui.');
    }

    public function show(Refund $refund)
    {
        $refund->load(['order.user', 'order.course', 'requester', 'reviewer']);

        return view('admin.refunds.show', compact('refund'));
    }

    public function approve(Request $request, Refund $refund)
    {
        $validated = $request->validate(['note' => ['nullable', 'string', 'max:1000']]);

        return $this->runAction(
            fn () => $this->refunds->approve($refund, Auth::user(), $validated['note'] ?? null),
            'refund_approved',
            'Refund disetujui dan dikirim ke Midtrans.'
        );
    }

    public function reject(Request $request, Refund $refund)
    {
        $validated = $request->validate(['note' => ['required', 'string', 'min:3', 'max:1000']]);

        return $this->runAction(
            fn () => $this->refunds->reject($refund, Auth::user(), $validated['note']),
            'refund_rejected',
            'Pengajuan refund ditolak.'
        );
    }

    public function retry(Refund $refund)
    {
        return $this->runAction(
            fn () => $this->refunds->retry($refund, Auth::user()),
            'refund_retried',
            'Refund dikirim ulang ke Midtrans.'
        );
    }

    public function completeManual(Request $request, Refund $refund)
    {
        $validated = $request->validate([
            'reference' => ['required', 'string', 'min:3', 'max:255'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        return $this->runAction(
            fn () => $this->refunds->completeManually(
                $refund,
                Auth::user(),
                $validated['reference'],
                $validated['note'] ?? null,
            ),
            'refund_completed_manually',
            'Refund manual dikonfirmasi selesai dan akses pembelian telah dicabut.'
        );
    }

    public function reconcile(Refund $refund)
    {
        return $this->runAction(
            fn () => $this->refunds->reconcile($refund),
            'refund_reconciled',
            'Status refund berhasil diperbarui dari Midtrans.'
        );
    }

    private function runAction(callable $action, string $activity, string $message)
    {
        try {
            $refund = $action();
        } catch (RuntimeException $exception) {
            return back()->withErrors(['refund' => $exception->getMessage()]);
        }

        ActivityLog::log($activity, [
            'description' => "Memproses refund order {$refund->order->order_code}",
            'metadata' => [
                'refund_id' => $refund->id,
                'order_id' => $refund->order_id,
                'amount' => $refund->amount,
                'status' => $refund->status->value,
            ],
        ]);

        if ($refund->isFailed()) {
            return redirect()->route('admin.refunds.show', $refund)
                ->withErrors(['refund' => 'Midtrans belum berhasil memproses refund. Silakan periksa pesan gagal dan coba ulang.']);
        }

        if ($refund->requiresManualProcessing()) {
            $message = 'Metode pembayaran ini memerlukan pengembalian dana manual.';
        } elseif ($refund->status === RefundStatus::Processing) {
            $message = 'Permintaan diterima Midtrans dan menunggu konfirmasi bank.';
        }

        return redirect()->route('admin.refunds.show', $refund)->with('success', $message);
    }
}
