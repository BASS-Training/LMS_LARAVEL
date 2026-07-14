@extends('layouts.app')

@section('title', 'Status Pembayaran')

@section('content')
<div class="max-w-lg mx-auto px-4 sm:px-6 py-12">
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">

        @if ($order->isPaid())
            <div class="p-8 text-center">
                <div class="mx-auto w-16 h-16 rounded-full bg-emerald-100 flex items-center justify-center">
                    <svg class="w-8 h-8 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <h1 class="mt-5 text-xl font-bold text-gray-900">Pembayaran berhasil</h1>
                <p class="mt-2 text-sm text-gray-600">
                    Anda sekarang terdaftar di <strong>{{ $order->course->title }}</strong>.
                    Kursusnya juga langsung muncul di aplikasi mobile.
                </p>

                <a href="{{ route('courses.show', $order->course) }}"
                   class="mt-6 w-full inline-flex items-center justify-center min-h-[48px] rounded-lg bg-emerald-600 text-white font-semibold hover:bg-emerald-700 transition-colors">
                    Mulai Belajar
                </a>
            </div>

        @elseif ($order->isPending())
            <div class="p-8 text-center">
                <div class="mx-auto w-16 h-16 rounded-full bg-amber-100 flex items-center justify-center">
                    <svg class="w-8 h-8 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h1 class="mt-5 text-xl font-bold text-gray-900">Menunggu pembayaran</h1>
                <p class="mt-2 text-sm text-gray-600">
                    Pembayaran Anda belum kami terima. Kalau sudah membayar, tunggu sebentar
                    lalu muat ulang halaman ini — akses kursus terbuka otomatis.
                </p>

                @if ($order->isPayable())
                    <a href="{{ $order->snap_redirect_url }}"
                       class="mt-6 w-full inline-flex items-center justify-center min-h-[48px] rounded-lg bg-bass-red text-white font-semibold hover:bg-red-800 transition-colors">
                        Lanjutkan Pembayaran
                    </a>
                @endif

                <a href="{{ route('checkout.finish', $order) }}"
                   class="mt-3 w-full inline-flex items-center justify-center min-h-[44px] rounded-lg border border-gray-300 text-gray-700 font-medium hover:bg-gray-50 transition-colors">
                    Muat Ulang Status
                </a>
            </div>

        @else
            <div class="p-8 text-center">
                <div class="mx-auto w-16 h-16 rounded-full bg-red-100 flex items-center justify-center">
                    <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </div>
                <h1 class="mt-5 text-xl font-bold text-gray-900">Pembayaran {{ strtolower($order->status_label) }}</h1>
                <p class="mt-2 text-sm text-gray-600">
                    Pesanan ini tidak dapat dilanjutkan. Anda bisa memesan ulang kapan saja.
                </p>

                <a href="{{ route('shop.show', $order->course) }}"
                   class="mt-6 w-full inline-flex items-center justify-center min-h-[48px] rounded-lg bg-bass-red text-white font-semibold hover:bg-red-800 transition-colors">
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
            <div class="flex justify-between">
                <dt class="text-gray-500">Kursus</dt>
                <dd class="text-gray-900 text-right ml-4">{{ $order->course->title }}</dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-gray-500">Total</dt>
                <dd class="font-semibold text-gray-900">{{ $order->amount_label }}</dd>
            </div>
            @if ($order->payment_type)
                <div class="flex justify-between">
                    <dt class="text-gray-500">Metode</dt>
                    <dd class="text-gray-900">{{ str_replace('_', ' ', $order->payment_type) }}</dd>
                </div>
            @endif
        </dl>
    </div>

    <p class="mt-5 text-center text-sm">
        <a href="{{ route('checkout.index') }}" class="text-gray-500 hover:text-bass-red">Lihat semua pesanan saya</a>
    </p>
</div>
@endsection
