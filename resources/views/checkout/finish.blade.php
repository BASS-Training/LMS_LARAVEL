@extends('layouts.app')

@section('title', 'Status Pembayaran')

@php
    // Langkah aktif untuk stepper (hanya relevan bila course perlu verifikasi).
    $needsVerif = $order->course->requiresPaymentVerification();
    $activeStep = $order->isPaid() ? 3 : ($order->isPaymentConfirmed() ? 2 : 1);
@endphp

@section('content')
<div class="max-w-lg mx-auto px-4 sm:px-6 py-12">
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
                    Anda sekarang terdaftar di <strong>{{ $order->course->title }}</strong>.
                    Kursusnya juga langsung muncul di aplikasi mobile.
                </p>
                @if ($needsVerif)
                    <p class="mt-2 inline-flex items-center gap-1 text-xs text-success bg-success-soft px-2.5 py-1 rounded-full">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4"/></svg>
                        Pembayaran terverifikasi oleh tim kami
                    </p>
                @endif

                <a href="{{ route('courses.show', $order->course) }}"
                   class="mt-6 w-full inline-flex items-center justify-center min-h-[48px] rounded-lg bg-bass-red text-white font-semibold hover:bg-bass-red-hover transition-colors">
                    Mulai Belajar
                </a>
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
                    <p class="mt-2 text-xs text-gray-500">
                        Jika Anda merasa ini keliru atau ingin refund, hubungi admin dengan menyertakan kode pesanan.
                    </p>
                @else
                    <p class="mt-2 text-sm text-gray-600">
                        Pesanan ini tidak dapat dilanjutkan. Anda bisa memesan ulang kapan saja.
                    </p>
                @endif

                <a href="{{ route('shop.show', $order->course) }}"
                   class="mt-6 w-full inline-flex items-center justify-center min-h-[48px] rounded-lg bg-bass-red text-white font-semibold hover:bg-bass-red-hover transition-colors">
                    Pesan Ulang
                </a>
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
                <dt class="text-gray-500">Kursus</dt>
                <dd class="text-gray-900 text-right ml-4">{{ $order->course->title }}</dd>
            </div>
            @if ($order->hasFee())
                <div class="flex justify-between">
                    <dt class="text-gray-500">Harga kursus</dt>
                    <dd class="text-gray-900">{{ $order->base_amount_label }}</dd>
                </div>
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
