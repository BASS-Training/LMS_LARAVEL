<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Order;
use App\Services\Payment\MidtransGateway;
use App\Services\Payment\OrderService;
use App\Services\Payment\RefundService;
use App\Services\Payment\ServiceFee;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class CheckoutController extends Controller
{
    public function __construct(
        private OrderService $orders,
        private MidtransGateway $gateway,
        private ServiceFee $fee,
        private RefundService $refunds,
    ) {
        // notification() dipanggil server Midtrans, bukan pengguna — tanpa auth.
        $this->middleware('auth')->except('notification');
    }

    /**
     * Peserta menekan "Beli" → halaman PILIH METODE pembayaran (biaya layanan
     * ditampilkan persis per metode). Jika fitur per-metode dimatikan, langsung
     * buat pesanan tarif-gabungan dan lempar ke Snap (perilaku lama, 1 klik).
     */
    public function choose(Course $course)
    {
        abort_unless($course->isInCatalog(), 404);

        $user = Auth::user();

        if ($course->isFree() || $course->isManagedBy($user) || $course->isEnrolledBy($user)) {
            return redirect()->route('shop.show', $course);
        }

        if (! $this->gateway->isConfigured()) {
            return redirect()->route('shop.show', $course)
                ->withErrors(['shop' => 'Pembayaran belum dikonfigurasi. Hubungi admin.']);
        }

        // Mode tarif gabungan (tanpa pemilihan metode) → checkout langsung.
        if (! $this->fee->methodsEnabled()) {
            return $this->createAndRedirect($course, null);
        }

        return view('checkout.choose', [
            'course' => $course,
            'base' => (int) $course->price,
            'options' => $this->fee->options((int) $course->price),
            'feeLabel' => $this->fee->label(),
        ]);
    }

    /**
     * Peserta memilih metode → buat pesanan dengan biaya metode itu → Snap
     * (dikunci ke metode terpilih).
     */
    public function store(Course $course, Request $request)
    {
        abort_unless($course->isInCatalog(), 404);

        $methodKey = null;

        if ($this->fee->methodsEnabled()) {
            $validated = $request->validate([
                'method' => 'required|string',
            ]);
            $methodKey = $validated['method'];

            // Metode harus salah satu yang aktif di config.
            if (! $this->fee->channelsFor($methodKey)) {
                return redirect()->route('checkout.choose', $course)
                    ->withErrors(['shop' => 'Metode pembayaran tidak valid. Silakan pilih lagi.']);
            }
        }

        return $this->createAndRedirect($course, $methodKey);
    }

    /**
     * Buat pesanan + lempar ke Snap, atau balik dengan error yang ramah.
     */
    private function createAndRedirect(Course $course, ?string $methodKey)
    {
        try {
            $order = $this->orders->checkout($course, Auth::user(), $methodKey);
        } catch (RuntimeException $e) {
            return redirect()->route('shop.show', $course)
                ->withErrors(['shop' => $e->getMessage()]);
        }

        if ($course->isEnrolledBy(Auth::user())) {
            return redirect()->route('courses.show', $course);
        }

        return redirect()->away($order->snap_redirect_url);
    }

    /**
     * Pengguna kembali dari halaman pembayaran Midtrans.
     *
     * Halaman ini TIDAK dipercaya untuk memberi akses — ia hanya menanyakan
     * status sebenarnya ke Midtrans lalu menampilkan hasilnya. Yang benar-benar
     * meng-enroll tetap OrderService setelah Midtrans bilang lunas.
     */
    public function finish(Order $order)
    {
        abort_unless($order->user_id === Auth::id(), 403);

        $order = $this->orders->refreshFromGateway($order);
        $order->load(['course', 'refund']);
        $refundEligibility = $order->isPaid() && ! $order->refund
            ? $this->refunds->eligibility($order, Auth::user())
            : null;

        return view('checkout.finish', compact('order', 'refundEligibility'));
    }

    /**
     * Unduh invoice (PDF) sebuah pesanan.
     *
     * Akses hanya untuk PEMILIK order atau super-admin, dan hanya setelah
     * pembayaran dikonfirmasi Midtrans (saat itulah nomor invoice dibuat).
     */
    public function invoice(Order $order)
    {
        abort_unless(
            $order->user_id === Auth::id() || Auth::user()->hasRole('super-admin'),
            403
        );
        abort_unless($order->isPaymentConfirmed(), 404);

        $order->load(['course', 'user']);

        $pdf = Pdf::loadView('invoices.pdf', ['order' => $order])->setPaper('a4');

        return $pdf->download(
            'Invoice-'.str_replace('/', '-', (string) $order->invoice_number).'.pdf'
        );
    }

    /**
     * Pengguna ingin ganti metode pembayaran.
     *
     * Karena metode kini dipilih di halaman kita (dan biaya layanan mengikuti
     * metode), berganti metode = batalkan pesanan lama lalu kembali ke halaman
     * pilih metode. Tidak ada risiko dobel bayar — order_code lama dibatalkan di
     * kedua sisi (DB kita + Midtrans). Pesanan yang sudah lunas tak pernah
     * dibatalkan.
     */
    public function changeMethod(Order $order)
    {
        abort_unless($order->user_id === Auth::id(), 403);

        // Jangan-jangan sebenarnya sudah lunas (webhook belum masuk di
        // localhost). Cek dulu ke Midtrans sebelum membatalkan apa pun.
        $order = $this->orders->refreshFromGateway($order);

        // Uang sudah masuk (lunas atau menunggu verifikasi) → jangan buat tagihan
        // baru; cukup tampilkan statusnya.
        if ($order->isPaymentConfirmed() || $order->course->isEnrolledBy(Auth::user())) {
            return redirect()->route('checkout.finish', $order);
        }

        try {
            $order = $this->orders->abandon($order, Auth::user());
        } catch (RuntimeException $exception) {
            return redirect()->route('checkout.finish', $order)
                ->withErrors(['cancel' => $exception->getMessage()]);
        }

        if ($order->isCancellationPending()) {
            return redirect()->route('checkout.finish', $order)
                ->with('success', 'Pembatalan sedang diproses oleh penyedia pembayaran.');
        }

        // Mode tarif gabungan (1 klik) → buat langsung; jika per-metode → pilih.
        if (! $this->fee->methodsEnabled()) {
            return $this->createAndRedirect($order->course, null);
        }

        return redirect()->route('checkout.choose', $order->course);
    }

    public function cancel(Request $request, Order $order)
    {
        abort_unless($order->user_id === Auth::id(), 403);

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $order = $this->orders->cancelPending(
                $order,
                Auth::user(),
                $validated['reason'] ?? 'Dibatalkan oleh pembeli.',
            );
        } catch (RuntimeException $exception) {
            return back()->withErrors(['cancel' => $exception->getMessage()]);
        }

        $message = $order->isCancellationPending()
            ? 'Pembatalan sedang diproses oleh penyedia pembayaran.'
            : 'Pesanan berhasil dibatalkan.';

        return redirect()->route('checkout.finish', $order)->with('success', $message);
    }

    /**
     * Daftar pesanan milik pengguna.
     */
    public function index()
    {
        $orders = Order::with(['course', 'refund'])
            ->where('user_id', Auth::id())
            ->latest()
            ->paginate(10);

        return view('checkout.index', compact('orders'));
    }

    /**
     * Webhook Midtrans (Payment Notification URL).
     *
     * Ini SATU-SATUNYA jalur resmi yang memberi akses kursus. Wajib:
     *  - verifikasi signature_key (kalau tidak, siapa pun bisa mengaku lunas)
     *  - idempotent (Midtrans mengirim ulang notifikasi yang sama)
     */
    public function notification(Request $request): JsonResponse
    {
        $payload = $request->all();

        if (! $this->gateway->verifySignature($payload)) {
            Log::warning('Notifikasi Midtrans dengan signature tidak valid ditolak', [
                'order_id' => $payload['order_id'] ?? null,
                'ip' => $request->ip(),
            ]);

            return response()->json(['message' => 'Invalid signature'], 403);
        }

        $order = Order::where('order_code', $payload['order_id'] ?? '')->first();

        if (! $order) {
            // 200 supaya Midtrans berhenti mengirim ulang notifikasi yatim ini.
            Log::warning('Notifikasi Midtrans untuk pesanan tak dikenal', [
                'order_id' => $payload['order_id'] ?? null,
            ]);

            return response()->json(['message' => 'Order not found'], 200);
        }

        if (in_array($payload['transaction_status'] ?? null, ['refund', 'partial_refund'], true)) {
            try {
                $this->refunds->applyProviderNotification($order, $payload);
            } catch (RuntimeException $exception) {
                Log::warning('Notifikasi refund Midtrans ditolak', [
                    'order_id' => $order->id,
                    'reason' => $exception->getMessage(),
                ]);

                return response()->json(['message' => $exception->getMessage()], 422);
            }

            return response()->json(['message' => 'OK']);
        }

        try {
            $this->orders->applyPaymentStatus($order, $payload);
        } catch (RuntimeException $exception) {
            Log::warning('Notifikasi Midtrans ditolak', [
                'order_id' => $order->id,
                'reason' => $exception->getMessage(),
            ]);

            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json(['message' => 'OK']);
    }
}
