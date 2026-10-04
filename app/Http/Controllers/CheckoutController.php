<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Order;
use App\Services\Payment\CouponService;
use App\Services\Payment\MidtransGateway;
use App\Services\Payment\OrderService;
use App\Services\Payment\RefundService;
use App\Services\Payment\ServiceFee;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Client\ConnectionException;
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
        private CouponService $coupons,
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

        $couponQuote = null;
        $couponError = null;
        $couponCode = session($this->couponSessionKey($course));

        if ($couponCode && $this->coupons->checkoutEnabled()) {
            try {
                $couponQuote = $this->coupons->quote($couponCode, (int) $course->price, $course, $user);
            } catch (RuntimeException $exception) {
                session()->forget($this->couponSessionKey($course));
                $couponError = $exception->getMessage();
            }
        } elseif ($couponCode) {
            session()->forget($this->couponSessionKey($course));
        }

        $discountedBase = (int) $course->price - ($couponQuote['discount'] ?? 0);
        $methodsEnabled = $this->fee->methodsEnabled();
        $options = $methodsEnabled
            ? $this->fee->options($discountedBase)
            : [[
                'key' => 'combined',
                'label' => 'Pembayaran Midtrans',
                'description' => 'Pilih metode pembayaran pada halaman Midtrans.',
                ...$this->fee->forBase($discountedBase),
            ]];

        return view('checkout.choose', [
            'course' => $course,
            'productTitle' => $course->title,
            'productUrl' => route('shop.show', $course),
            'checkoutAction' => route('checkout.store', $course),
            'couponApplyAction' => route('checkout.coupon.apply', $course),
            'couponRemoveAction' => route('checkout.coupon.remove', $course),
            'thumbnail' => $course->thumbnail,
            'priceLabel' => 'Harga kursus',
            'bundleDiscount' => 0,
            'base' => (int) $course->price,
            'discountedBase' => $discountedBase,
            'options' => $options,
            'feeLabel' => $this->fee->label(),
            'methodsEnabled' => $methodsEnabled,
            'couponsEnabled' => $this->coupons->checkoutEnabled(),
            'couponQuote' => $couponQuote,
            'couponError' => $couponError,
        ]);
    }

    public function applyCoupon(Request $request, Course $course)
    {
        abort_unless($course->isInCatalog() && ! $course->isFree(), 404);
        $validated = $request->validate(['coupon_code' => ['required', 'string', 'max:50']]);

        try {
            $quote = $this->coupons->quote($validated['coupon_code'], (int) $course->price, $course, Auth::user());
        } catch (RuntimeException $exception) {
            return back()->withErrors(['coupon' => $exception->getMessage()])->withInput();
        }

        session()->put($this->couponSessionKey($course), $quote['code']);

        return redirect()->route('checkout.choose', $course)
            ->with('success', "Kupon {$quote['code']} berhasil digunakan.");
    }

    public function removeCoupon(Course $course)
    {
        session()->forget($this->couponSessionKey($course));

        return redirect()->route('checkout.choose', $course)
            ->with('success', 'Kupon telah dihapus dari checkout.');
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

        return $this->createAndRedirect($course, $methodKey, session($this->couponSessionKey($course)));
    }

    /**
     * Buat pesanan + lempar ke Snap, atau balik dengan error yang ramah.
     */
    private function createAndRedirect(Course $course, ?string $methodKey, ?string $couponCode = null)
    {
        try {
            $order = $this->orders->checkout($course, Auth::user(), $methodKey, $couponCode);
        } catch (RuntimeException $e) {
            return redirect()->route('checkout.choose', $course)
                ->withErrors(['shop' => $e->getMessage()]);
        } catch (ConnectionException $e) {
            Log::error('Koneksi Midtrans gagal saat checkout', ['message' => $e->getMessage()]);

            return redirect()->route('checkout.choose', $course)
                ->withErrors(['shop' => 'Penyedia pembayaran sedang tidak dapat dihubungi. Silakan coba lagi.']);
        }

        if ($course->isEnrolledBy(Auth::user())) {
            session()->forget($this->couponSessionKey($course));

            return redirect()->route('courses.show', $course);
        }

        session()->forget($this->couponSessionKey($course));

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

        if ($order->hasDiscount() && ! $this->coupons->checkoutEnabled()) {
            return redirect()->route('checkout.finish', $order)
                ->withErrors(['cancel' => 'Metode pembayaran tidak dapat diganti saat fitur kupon dinonaktifkan.']);
        }

        if ($order->coupon_code) {
            session()->put($this->couponSessionKey($order->course), $order->coupon_code);
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

        return redirect()->route('checkout.choose', $order->course);
    }

    private function couponSessionKey(Course $course): string
    {
        return 'checkout.coupon.'.Auth::id().'.'.$course->id;
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
