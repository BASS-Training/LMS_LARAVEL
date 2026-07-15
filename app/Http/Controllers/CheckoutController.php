<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Order;
use App\Services\Payment\MidtransGateway;
use App\Services\Payment\OrderService;
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
    ) {
        // notification() dipanggil server Midtrans, bukan pengguna — tanpa auth.
        $this->middleware('auth')->except('notification');
    }

    /**
     * Peserta menekan "Beli Sekarang" → buat pesanan → lempar ke halaman Midtrans.
     */
    public function store(Course $course)
    {
        abort_unless($course->isInCatalog(), 404);

        try {
            $order = $this->orders->checkout($course, Auth::user());
        } catch (RuntimeException $e) {
            return back()->withErrors(['shop' => $e->getMessage()]);
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
        $order->load('course');

        return view('checkout.finish', compact('order'));
    }

    /**
     * Pengguna ingin ganti metode pembayaran.
     *
     * Snap "mengunci" tampilan ke metode yang sudah dipilih saat transaksi
     * dilanjutkan, jadi cara bersih untuk berganti metode adalah: batalkan
     * pesanan lama, lalu buat pesanan baru (Snap fresh → daftar metode muncul).
     * Tidak ada risiko dobel bayar — order_code lama sudah dibatalkan di kedua
     * sisi (DB kita + Midtrans).
     */
    public function changeMethod(Order $order)
    {
        abort_unless($order->user_id === Auth::id(), 403);

        // Jangan-jangan sebenarnya sudah lunas (webhook belum masuk di
        // localhost). Cek dulu ke Midtrans sebelum membatalkan apa pun.
        $order = $this->orders->refreshFromGateway($order);

        if ($order->isPaid() || $order->course->isEnrolledBy(Auth::user())) {
            return redirect()->route('checkout.finish', $order);
        }

        $this->orders->abandon($order);

        try {
            $fresh = $this->orders->checkout($order->course, Auth::user());
        } catch (RuntimeException $e) {
            return redirect()->route('shop.show', $order->course)
                ->withErrors(['shop' => $e->getMessage()]);
        }

        return redirect()->away($fresh->snap_redirect_url);
    }

    /**
     * Daftar pesanan milik pengguna.
     */
    public function index()
    {
        $orders = Order::with('course')
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

        $this->orders->applyPaymentStatus($order, $payload);

        return response()->json(['message' => 'OK']);
    }
}
