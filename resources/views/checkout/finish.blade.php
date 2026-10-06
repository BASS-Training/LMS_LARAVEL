@inject('features', 'App\Services\FeatureAvailability')
@extends('layouts.app')

@section('title', 'Status Pembayaran')

@php
    // Langkah aktif untuk stepper (hanya relevan bila course perlu verifikasi).
    $needsVerif = $order->requires_payment_verification;
    $activeStep = $order->isPaid() ? 3 : ($order->isPaymentConfirmed() ? 2 : 1);
@endphp

@section('content')
<div class="max-w-lg mx-auto px-4 sm:px-6 py-12">
    @if (session('success'))
        <div class="mb-4 rounded-lg bg-success-soft border border-success/30 px-4 py-3 text-sm text-success">
            {{ session('success') }}
        </div>
    @endif
    @if ($errors->has('cancel') || $errors->has('refund'))
        <div class="mb-4 rounded-lg bg-error-soft border border-error/40 px-4 py-3 text-sm text-error">
            {{ $errors->first('cancel') ?: $errors->first('refund') }}
        </div>
    @endif

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">

        @if ($order->isPaid())
            <div class="p-8 text-center">
                <div class="mx-auto w-16 h-16 rounded-full bg-success-soft flex items-center justify-center">
                    <svg class="w-8 h-8 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <h1 class="mt-5 text-xl font-bold text-gray-900">Pembayaran berhasil</h1>
                <p class="mt-2 text-sm text-gray-600">
                    Anda sekarang memiliki akses ke <strong>{{ $order->order_title }}</strong>.
                    Materinya juga langsung muncul di aplikasi mobile.
                </p>
                @if ($needsVerif)
                    <p class="mt-2 inline-flex items-center gap-1 text-xs text-success bg-success-soft px-2.5 py-1 rounded-full">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4"/></svg>
                        Pembayaran terverifikasi oleh tim kami
                    </p>
                @endif

                @if ($order->isBundleOrder())
                    <div class="mt-6 grid gap-2">
                        @foreach ($order->items as $item)
                            @if ($item->course)
                                <a href="{{ route('courses.show', $item->course) }}" class="inline-flex min-h-[44px] items-center justify-center rounded-lg border border-bass-red px-4 font-semibold text-bass-red hover:bg-error-soft">Buka {{ $item->course_title }}</a>
                            @endif
                        @endforeach
                    </div>
                @else
                    <a href="{{ route('courses.show', $order->course) }}"
                       class="mt-6 w-full inline-flex items-center justify-center min-h-[48px] rounded-lg bg-bass-red text-white font-semibold hover:bg-bass-red-hover transition-colors">
                         Mulai Belajar
                    </a>
                @endif

                @if (! $order->refund && $features->refundRequestsEnabled())
                    <div class="mt-5 pt-5 border-t border-gray-100 text-left"
                         x-data="{ open: @js($errors->has('reason_type') || $errors->has('reason_other')), reasonType: @js(old('reason_type', '')) }">
                        @if ($refundEligibility['eligible'])
                            <button type="button" @click="open = !open"
                                    class="text-sm font-medium text-gray-500 hover:text-bass-red">
                                Ajukan refund penuh
                            </button>
                            <form x-show="open" x-cloak method="POST" action="{{ route('refunds.store', $order) }}" class="mt-3">
                                @csrf
                                <label for="reason_type" class="block text-sm font-medium text-gray-700">Alasan pengajuan</label>
                                <select id="reason_type" name="reason_type" x-model="reasonType" required
                                        class="mt-1 w-full rounded-lg border-gray-300 focus:border-bass-red focus:ring-bass-red text-sm">
                                    <option value="">Pilih alasan</option>
                                    @foreach (\App\Enums\RefundReason::cases() as $reasonOption)
                                        <option value="{{ $reasonOption->value }}">{{ $reasonOption->label() }}</option>
                                    @endforeach
                                </select>
                                <div x-show="reasonType === 'other'" x-cloak class="mt-3">
                                    <label for="reason_other" class="block text-sm font-medium text-gray-700">Jelaskan alasan lainnya</label>
                                    <textarea id="reason_other" name="reason_other" rows="3" minlength="10" maxlength="1000"
                                              :required="reasonType === 'other'"
                                              class="mt-1 w-full rounded-lg border-gray-300 focus:border-bass-red focus:ring-bass-red text-sm"
                                              placeholder="Jelaskan alasan refund untuk ditinjau admin.">{{ old('reason_other') }}</textarea>
                                </div>
                                <p class="mt-2 text-xs text-gray-500">
                                    Maksimal {{ $refundEligibility['settings']->request_window_days }} hari setelah akses diberikan dan progres maksimal {{ $refundEligibility['settings']->max_progress_percentage }}%.
                                    Batas pesanan ini {{ $refundEligibility['deadline']->translatedFormat('d F Y H:i') }}.
                                </p>
                                <p class="mt-1 text-xs text-gray-500">Nominal refund penuh: {{ $order->amount_label }}. Akses tetap aktif sampai refund berhasil.</p>
                                <button type="submit" class="mt-3 w-full min-h-[44px] rounded-lg border border-bass-red text-bass-red text-sm font-semibold hover:bg-red-50"
                                        onclick="return confirm('Ajukan refund penuh untuk pesanan ini?');">
                                    Kirim Pengajuan
                                </button>
                            </form>
                        @else
                            <p class="text-sm font-medium text-gray-700">Refund tidak tersedia</p>
                            <p class="mt-1 text-xs text-gray-500">{{ $refundEligibility['message'] }}</p>
                        @endif
                    </div>
                @endif
            </div>

        @elseif ($order->isAwaitingVerification())
            <div class="p-8 text-center">
                <div class="mx-auto w-16 h-16 rounded-full bg-warning-soft flex items-center justify-center">
                    <svg class="w-8 h-8 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h1 class="mt-5 text-xl font-bold text-gray-900">Pembayaran diterima</h1>
                <p class="mt-2 text-sm text-gray-600">
                    Terima kasih! Dana Anda sudah kami terima dan sedang
                    <strong>menunggu verifikasi</strong> tim kami. Akses kursus akan
                    <strong>terbuka otomatis</strong> setelah pembayaran divalidasi —
                    Anda tak perlu melakukan apa pun lagi.
                </p>

                @include('checkout.partials.verif-stepper', ['active' => $activeStep])

                <a href="{{ route('checkout.finish', $order) }}"
                   class="mt-6 w-full inline-flex items-center justify-center min-h-[44px] rounded-lg border border-gray-300 text-gray-700 font-medium hover:bg-gray-50 transition-colors">
                    Muat Ulang Status
                </a>
            </div>

        @elseif ($order->isPending() && in_array($order->snap_status, ['queued', 'processing', 'needs_review'], true))
            <div class="p-8 text-center">
                <h1 class="text-xl font-bold text-gray-900">Menyiapkan pembayaran</h1>
                @if ($order->snap_status === 'needs_review')
                    <p class="mt-2 text-sm text-gray-600">Transaksi perlu diperiksa oleh admin. Jangan buat pembayaran baru untuk pesanan ini.</p>
                @else
                    <p class="mt-2 text-sm text-gray-600">Tautan pembayaran sedang dibuat. Halaman ini akan membuka Midtrans secara otomatis.</p>
                    <p id="snap-waiting" class="mt-3 text-xs text-gray-500">Menunggu pekerja antrean pembayaran...</p>
                    <script>
                        (() => {
                            const url = @json(route('checkout.snap-status', $order));
                            const check = async () => {
                                try {
                                    const response = await fetch(url, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' });
                                    if (!response.ok) return;
                                    const result = await response.json();
                                    if (result.redirect_url) { window.location.assign(result.redirect_url); return; }
                                    if (result.snap_status === 'needs_review' || result.status !== 'pending') { window.location.reload(); }
                                } catch (error) { /* Retry on the next poll. */ }
                            };
                            check();
                            setInterval(check, 3000);
                        })();
                    </script>
                @endif
                <a href="{{ route('checkout.index') }}" class="mt-6 inline-block text-sm text-bass-red">Lihat riwayat pesanan</a>
            </div>

        @elseif ($order->isPending())
            <div class="p-8 text-center">
                <div class="mx-auto w-16 h-16 rounded-full bg-warning-soft flex items-center justify-center">
                    <svg class="w-8 h-8 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h1 class="mt-5 text-xl font-bold text-gray-900">Menunggu pembayaran</h1>
                <p class="mt-2 text-sm text-gray-600">
                    Pembayaran Anda belum kami terima. Kalau sudah membayar, tunggu sebentar
                    lalu muat ulang halaman ini.
                </p>

                @if ($order->isPayable())
                    <a href="{{ $order->snap_redirect_url }}"
                       class="mt-6 w-full inline-flex items-center justify-center min-h-[48px] rounded-lg bg-bass-red text-white font-semibold hover:bg-bass-red-hover transition-colors">
                        Lanjutkan Pembayaran
                    </a>
                @endif

                <a href="{{ route('checkout.finish', $order) }}"
                   class="mt-3 w-full inline-flex items-center justify-center min-h-[44px] rounded-lg border border-gray-300 text-gray-700 font-medium hover:bg-gray-50 transition-colors">
                    Muat Ulang Status
                </a>

                <form method="POST" action="{{ route('checkout.change-method', $order) }}" class="mt-3">
                    @csrf
                    <button type="submit"
                            class="w-full inline-flex items-center justify-center min-h-[44px] rounded-lg text-gray-500 text-sm font-medium hover:text-bass-red transition-colors">
                        Ganti metode pembayaran
                    </button>
                </form>
                <p class="mt-1 text-center text-xs text-gray-400">
                    Membuat tagihan baru dan membatalkan yang ini — tidak ada dobel bayar.
                </p>
                <form method="POST" action="{{ route('checkout.cancel', $order) }}" class="mt-3"
                      onsubmit="return confirm('Batalkan pesanan ini? Tagihan Midtrans tidak dapat digunakan lagi.');">
                    @csrf
                    <button type="submit" class="w-full inline-flex items-center justify-center min-h-[44px] rounded-lg border border-gray-300 text-gray-600 text-sm font-medium hover:bg-gray-50">
                        Batalkan Pesanan
                    </button>
                </form>
            </div>

        @elseif ($order->isCancellationPending())
            <div class="p-8 text-center">
                <div class="mx-auto w-16 h-16 rounded-full bg-warning-soft flex items-center justify-center">
                    <svg class="w-8 h-8 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h1 class="mt-5 text-xl font-bold text-gray-900">Pembatalan sedang diproses</h1>
                <p class="mt-2 text-sm text-gray-600">
                    Permintaan pembatalan sudah dicatat dan akan dikirim ulang otomatis sampai dikonfirmasi Midtrans.
                </p>
                <a href="{{ route('checkout.finish', $order) }}"
                   class="mt-6 w-full inline-flex items-center justify-center min-h-[44px] rounded-lg border border-gray-300 text-gray-700 font-medium hover:bg-gray-50 transition-colors">
                    Muat Ulang Status
                </a>
            </div>

        @else
            <div class="p-8 text-center">
                <div class="mx-auto w-16 h-16 rounded-full bg-error-soft flex items-center justify-center">
                    <svg class="w-8 h-8 text-error" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </div>
                <h1 class="mt-5 text-xl font-bold text-gray-900">Pembayaran {{ strtolower($order->status_label) }}</h1>
                @if ($order->isRejected())
                    <p class="mt-2 text-sm text-gray-600">
                        Maaf, pembayaran ini <strong>tidak dapat kami verifikasi</strong>.
                    </p>
                    @if ($order->rejection_reason)
                        <p class="mt-2 text-sm text-error bg-error-soft rounded-lg px-3 py-2">
                            Alasan: {{ $order->rejection_reason }}
                        </p>
                    @endif
                    <p class="mt-2 text-xs text-gray-500">Refund penuh otomatis diajukan dan diproses setelah penolakan ini.</p>
                @else
                    <p class="mt-2 text-sm text-gray-600">
                        Pesanan ini tidak dapat dilanjutkan. Anda bisa memesan ulang kapan saja.
                    </p>
                @endif

                @if (! $order->isBundleOrder() || $features->bundlesEnabled())
                    <a href="{{ $order->isBundleOrder() ? route('bundles.show', $order->bundle) : route('shop.show', $order->course) }}"
                       class="mt-6 w-full inline-flex items-center justify-center min-h-[48px] rounded-lg bg-bass-red text-white font-semibold hover:bg-bass-red-hover transition-colors">
                         Pesan Ulang
                    </a>
                @endif
            </div>
        @endif

        @if ($order->refund)
            @php [$refundBg, $refundText] = $order->refund->status_colors; @endphp
            <div class="border-t border-gray-100 px-8 py-5 bg-white">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold text-gray-900">Refund penuh</p>
                        <p class="mt-0.5 text-xs text-gray-500">{{ $order->refund->amount_label }}</p>
                    </div>
                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold {{ $refundBg }} {{ $refundText }}">
                        {{ $order->refund->status_label }}
                    </span>
                </div>
                @if ($order->refund->admin_note)
                    <p class="mt-3 text-xs text-gray-600">Catatan admin: {{ $order->refund->admin_note }}</p>
                @endif
                @if ($order->refund->isFailed())
                    <p class="mt-3 text-xs text-error">Proses ke Midtrans gagal dan akan ditangani admin.</p>
                @elseif ($order->refund->requiresManualProcessing())
                    <p class="mt-3 text-xs text-gray-600">Admin sedang memproses pengembalian dana secara manual.</p>
                @endif
            </div>
        @endif

        {{-- Rincian --}}
        <dl class="border-t border-gray-100 px-8 py-5 space-y-2 text-sm bg-gray-50">
            <div class="flex justify-between">
                <dt class="text-gray-500">Kode pesanan</dt>
                <dd class="font-mono text-gray-900">{{ $order->order_code }}</dd>
            </div>
            @if ($order->invoice_number)
                <div class="flex justify-between">
                    <dt class="text-gray-500">No. Invoice</dt>
                    <dd class="text-gray-900">{{ $order->invoice_number }}</dd>
                </div>
            @endif
            <div class="flex justify-between">
                <dt class="text-gray-500">{{ $order->isBundleOrder() ? 'Bundle' : 'Kursus' }}</dt>
                <dd class="text-gray-900 text-right ml-4">{{ $order->order_title }}</dd>
            </div>
            @if ($order->hasFee() || $order->hasDiscount() || $order->hasBundleDiscount())
                <div class="flex justify-between">
                    <dt class="text-gray-500">{{ $order->isBundleOrder() ? 'Harga paket' : 'Harga kursus' }}</dt>
                    <dd class="text-gray-900">{{ $order->original_base_amount_label }}</dd>
                </div>
            @endif
            @if ($order->hasDiscount())
                <div class="flex justify-between text-success">
                    <dt>Kupon {{ $order->coupon_code }}</dt>
                    <dd>-{{ $order->discount_amount_label }}</dd>
                </div>
            @endif
            @if ($order->hasBundleDiscount())
                <div class="flex justify-between text-success">
                    <dt>Potongan kursus dimiliki</dt>
                    <dd>-{{ $order->bundle_discount_amount_label }}</dd>
                </div>
            @endif
            @if ($order->hasFee())
                <div class="flex justify-between">
                    <dt class="text-gray-500">{{ config('midtrans.fee.label', 'Biaya layanan') }}</dt>
                    <dd class="text-gray-900">{{ $order->fee_amount_label }}</dd>
                </div>
            @endif
            <div class="flex justify-between">
                <dt class="text-gray-500">Total</dt>
                <dd class="font-semibold text-gray-900">{{ $order->amount_label }}</dd>
            </div>
            @if ($order->payment_type || $order->payment_method_key)
                <div class="flex justify-between">
                    <dt class="text-gray-500">Metode</dt>
                    <dd class="text-gray-900">{{ $order->payment_method_label }}</dd>
                </div>
            @endif

            @if ($order->isPaymentConfirmed())
                <div class="pt-3 mt-1 border-t border-gray-200">
                    <a href="{{ route('checkout.invoice', $order) }}"
                       class="inline-flex items-center gap-1.5 text-bass-red font-medium hover:underline">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Unduh Invoice (PDF)
                    </a>
                </div>
            @endif
        </dl>
    </div>

    <p class="mt-5 text-center text-sm">
        <a href="{{ route('checkout.index') }}" class="text-gray-500 hover:text-bass-red">Lihat semua pesanan saya</a>
    </p>
</div>
@endsection
