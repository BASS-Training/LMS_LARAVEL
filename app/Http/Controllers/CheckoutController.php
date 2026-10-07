<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessPaymentWebhook;
use App\Jobs\ReconcilePaymentOrder;
use App\Models\Bundle;
use App\Models\Course;
use App\Models\Order;
use App\Models\PaymentWebhookReceipt;
use App\Models\RefundSetting;
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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use RuntimeException;
use Throwable;

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
            'refundSettings' => RefundSetting::current(),
        ]);
    }

    public function chooseBundle(Bundle $bundle)
    {
        abort_unless($bundle->isVisibleInCatalog(Auth::user()), 404);
        $bundle->load('courses');
        $user = Auth::user();
        $pricing = $this->orders->bundlePricing($bundle, $user);

        if ($pricing['payable_base'] <= 0) {
            return redirect()->route('bundles.show', $bundle)->withErrors([
                'shop' => $pricing['all_owned']
                    ? 'Anda sudah memiliki seluruh isi paket kursus ini.'
                    : 'Tidak ada nominal yang dapat ditagihkan setelah potongan kepemilikan kursus.',
            ]);
        }

        if (! $this->gateway->isConfigured()) {
            return redirect()->route('bundles.show', $bundle)
                ->withErrors(['shop' => 'Pembayaran belum dikonfigurasi. Hubungi admin.']);
        }

        $couponQuote = null;
        $couponError = null;
        $couponCode = session($this->bundleCouponSessionKey($bundle));
        if ($couponCode && $this->coupons->checkoutEnabled()) {
            try {
                $couponQuote = $this->coupons->quoteForBundle($couponCode, $pricing['payable_base'], $bundle, $user);
            } catch (RuntimeException $exception) {
                session()->forget($this->bundleCouponSessionKey($bundle));
                $couponError = $exception->getMessage();
            }
        } elseif ($couponCode) {
            session()->forget($this->bundleCouponSessionKey($bundle));
        }

        $discountedBase = $pricing['payable_base'] - ($couponQuote['discount'] ?? 0);
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
            'course' => null,
            'bundle' => $bundle,
            'productTitle' => $bundle->title,
            'productUrl' => route('bundles.show', $bundle),
            'checkoutAction' => route('checkout.bundle.store', $bundle),
            'couponApplyAction' => route('checkout.bundle.coupon.apply', $bundle),
            'couponRemoveAction' => route('checkout.bundle.coupon.remove', $bundle),
            'thumbnail' => $bundle->courses->first()?->thumbnail,
            'priceLabel' => 'Harga paket',
            'bundleDiscount' => $pricing['bundle_discount'],
            'base' => (int) $bundle->price,
            'discountedBase' => $discountedBase,
            'options' => $options,
            'feeLabel' => $this->fee->label(),
            'methodsEnabled' => $methodsEnabled,
            'couponsEnabled' => $this->coupons->checkoutEnabled(),
            'couponQuote' => $couponQuote,
            'couponError' => $couponError,
            'refundSettings' => RefundSetting::current(),
        ]);
    }

    public function applyBundleCoupon(Request $request, Bundle $bundle)
    {
        abort_unless($bundle->isVisibleInCatalog(Auth::user()), 404);
        $validated = $request->validate(['coupon_code' => ['required', 'string', 'max:50']]);
        $pricing = $this->orders->bundlePricing($bundle, Auth::user());

        try {
            $quote = $this->coupons->quoteForBundle(
                $validated['coupon_code'],
                $pricing['payable_base'],
                $bundle,
                Auth::user(),
            );
        } catch (RuntimeException $exception) {
            return back()->withErrors(['coupon' => $exception->getMessage()])->withInput();
        }

        session()->put($this->bundleCouponSessionKey($bundle), $quote['code']);

        return redirect()->route('checkout.bundle.choose', $bundle)
            ->with('success', "Kupon {$quote['code']} berhasil digunakan.");
    }

    public function removeBundleCoupon(Bundle $bundle)
    {
        abort_unless($bundle->isVisibleInCatalog(Auth::user()), 404);
        session()->forget($this->bundleCouponSessionKey($bundle));

        return redirect()->route('checkout.bundle.choose', $bundle)
            ->with('success', 'Kupon telah dihapus dari checkout.');
    }

    public function applyCoupon(Request $request, Course $course)
    {
        abort_unless($course->isInCatalog() && ! $course->isFree(), 404);

        $validated = $request->validate([
            'coupon_code' => ['required', 'string', 'max:50'],
        ]);

        try {
            $quote = $this->coupons->quote(
                $validated['coupon_code'],
                (int) $course->price,
                $course,
                Auth::user(),
            );
        } catch (RuntimeException $exception) {
            return back()->withErrors(['coupon' => $exception->getMessage()])->withInput();
        }

        session()->put($this->couponSessionKey($course), $quote['code']);

        return redirect()->route('checkout.choose', $course)
            ->with('success', "Kupon {$quote['code']} berhasil digunakan.");
    }

    public function removeCoupon(Course $course)
    {
        abort_unless($course->isInCatalog() && ! $course->isFree(), 404);
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
        $this->validateRefundConsent($request);

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

        return $this->createAndRedirect(
            $course,
            $methodKey,
            session($this->couponSessionKey($course)),
            $request->input('refund_policy_mode'),
        );
    }

    public function storeBundle(Bundle $bundle, Request $request)
    {
        abort_unless($bundle->isVisibleInCatalog(Auth::user()), 404);
        $this->validateRefundConsent($request);
        $methodKey = null;

        if ($this->fee->methodsEnabled()) {
            $validated = $request->validate(['method' => 'required|string']);
            $methodKey = $validated['method'];
            if (! $this->fee->channelsFor($methodKey)) {
                return redirect()->route('checkout.bundle.choose', $bundle)
                    ->withErrors(['shop' => 'Metode pembayaran tidak valid. Silakan pilih lagi.']);
            }
        }

        try {
            $order = $this->orders->checkoutBundle(
                $bundle,
                Auth::user(),
                $methodKey,
                session($this->bundleCouponSessionKey($bundle)),
                $request->input('refund_policy_mode'),
            );
        } catch (RuntimeException $exception) {
            return redirect()->route('checkout.bundle.choose', $bundle)
                ->withErrors(['shop' => $exception->getMessage()]);
        } catch (ConnectionException $exception) {
            Log::error('Koneksi Midtrans gagal saat checkout bundle', ['message' => $exception->getMessage()]);

            return redirect()->route('checkout.bundle.choose', $bundle)
                ->withErrors(['shop' => 'Penyedia pembayaran sedang tidak dapat dihubungi. Silakan coba lagi.']);
        }

        session()->forget($this->bundleCouponSessionKey($bundle));

        return config('payment_queue.enabled') && ! $order->snap_redirect_url
            ? redirect()->route('checkout.finish', $order)
            : redirect()->away($order->snap_redirect_url);
    }

    /**
     * Buat pesanan + lempar ke Snap, atau balik dengan error yang ramah.
     */
    private function createAndRedirect(Course $course, ?string $methodKey, ?string $couponCode = null, ?string $acceptedPolicyMode = null)
    {
        try {
            $order = $this->orders->checkout($course, Auth::user(), $methodKey, $couponCode, $acceptedPolicyMode);
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

        return config('payment_queue.enabled') && ! $order->snap_redirect_url
            ? redirect()->route('checkout.finish', $order)
            : redirect()->away($order->snap_redirect_url);
    }

    private function validateRefundConsent(Request $request): void
    {
        $settings = RefundSetting::current();
        $request->validate([
            'refund_consent' => ['accepted'],
            'refund_policy_mode' => ['required', Rule::in([$settings->policy_mode])],
        ], [
            'refund_consent.accepted' => 'Anda harus menyetujui kebijakan refund sebelum melanjutkan.',
            'refund_policy_mode.in' => 'Kebijakan refund berubah. Muat ulang halaman dan tinjau ketentuan terbaru.',
        ]);
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

        $order = config('payment_queue.enabled') ? $order->fresh() : $this->orders->refreshFromGateway($order);
        if (config('payment_queue.enabled') && $order->isPayable()
            && Cache::add('payment-reconcile:'.$order->id, true, now()->addSeconds(30))) {
            ReconcilePaymentOrder::dispatch($order->id);
        }
        $order->load(['course', 'bundle', 'items.course', 'refund']);
        $refundEligibility = $order->isPaid() && ! $order->refund
            ? $this->refunds->eligibility($order, Auth::user())
            : null;

        return view('checkout.finish', compact('order', 'refundEligibility'));
    }

    public function snapStatus(Order $order): JsonResponse
    {
        abort_unless($order->user_id === Auth::id(), 403);
        $order->refresh();

        return response()->json([
            'status' => $order->status,
            'snap_status' => $order->snap_status,
            'redirect_url' => $order->isPayable() ? $order->snap_redirect_url : null,
        ]);
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

        $order->load(['course', 'bundle', 'items.course', 'user']);

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
        if ($order->isPaymentConfirmed() || (! $order->isBundleOrder() && $order->course->isEnrolledBy(Auth::user()))) {
            return redirect()->route('checkout.finish', $order);
        }

        if ($order->hasDiscount() && ! $this->coupons->checkoutEnabled()) {
            return redirect()->route('checkout.finish', $order)
                ->withErrors(['cancel' => 'Metode pembayaran tidak dapat diganti saat fitur kupon dinonaktifkan.']);
        }

        if ($order->coupon_code) {
            $key = $order->isBundleOrder()
                ? $this->bundleCouponSessionKey($order->bundle)
                : $this->couponSessionKey($order->course);
            session()->put($key, $order->coupon_code);
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

        return $order->isBundleOrder()
            ? redirect()->route('checkout.bundle.choose', $order->bundle)
            : redirect()->route('checkout.choose', $order->course);
    }

    private function couponSessionKey(Course $course): string
    {
        return 'checkout.coupon.'.Auth::id().'.'.$course->id;
    }

    private function bundleCouponSessionKey(Bundle $bundle): string
    {
        return 'checkout.bundle.coupon.'.Auth::id().'.'.$bundle->id;
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
        $orders = Order::with(['course', 'bundle', 'items', 'refund'])
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

            return response()->json(['message' => 'Order not found'], config('payment_queue.enabled') ? 503 : 200);
        }

        if (config('payment_queue.enabled')) {
            if (! isset($payload['gross_amount'])
                || (int) round((float) $payload['gross_amount']) !== $order->amount
                || (isset($payload['currency']) && strtoupper((string) $payload['currency']) !== 'IDR')) {
                return response()->json(['message' => 'Invalid amount or currency'], 422);
            }

            try {
                DB::transaction(function () use ($order, $payload) {
                    $receipt = PaymentWebhookReceipt::firstOrCreate(
                        ['payload_hash' => hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR))],
                        ['order_id' => $order->id, 'payload' => $payload]
                    );
                    if (! $receipt->processed_at) {
                        ProcessPaymentWebhook::dispatch($receipt->id);
                    }
                });
            } catch (Throwable $exception) {
                Log::error('Webhook Midtrans gagal disimpan ke antrean', [
                    'order_id' => $order->id,
                    'message' => $exception->getMessage(),
                ]);

                return response()->json(['message' => 'Queue unavailable'], 503);
            }

            return response()->json(['message' => 'OK']);
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
