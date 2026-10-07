@extends('layouts.app')

@section('title', 'Riwayat Pembelian')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <h1 class="text-2xl font-bold text-gray-900 mb-1">Riwayat Pembelian</h1>
    <p class="text-sm text-gray-500 mb-6">Semua pesanan kursus Anda beserta status &amp; invoice-nya.</p>

    @if ($orders->isEmpty())
        <div class="text-center py-16 bg-white rounded-xl border border-gray-200">
            <p class="text-sm font-medium text-gray-900">Belum ada pesanan</p>
            <p class="mt-1 text-sm text-gray-500">Pembelian kursus Anda akan tercatat di sini.</p>
            <a href="{{ route('shop.index') }}"
               class="mt-5 inline-flex items-center justify-center min-h-[44px] px-6 rounded-lg bg-bass-red text-white text-sm font-semibold hover:bg-bass-red-hover transition-colors">
                Jelajahi Katalog
            </a>
        </div>
    @else
        <div class="bg-white rounded-xl border border-gray-200 divide-y divide-gray-100 overflow-hidden">
            @foreach ($orders as $order)
                @php [$bg, $text] = $order->status_colors; @endphp

                <div class="flex items-center gap-4 p-4 hover:bg-gray-50 transition-colors">
                    <a href="{{ route('checkout.finish', $order) }}" class="min-w-0 flex-1">
                        <p class="font-medium text-gray-900 truncate">{{ $order->order_title }}</p>
                        <p class="mt-0.5 text-xs text-gray-500 font-mono">{{ $order->order_code }}</p>
                        <p class="mt-0.5 text-xs text-gray-400">{{ $order->created_at->translatedFormat('d M Y, H:i') }}</p>
                    </a>

                    <div class="text-right flex-shrink-0">
                        <p class="font-semibold text-gray-900">{{ $order->amount_label }}</p>
                        @if ($order->hasDiscount())
                            <span class="mt-1 block text-xs font-medium text-success">{{ $order->coupon_code }} &middot; Hemat {{ $order->discount_amount_label }}</span>
                        @endif
                        @if ($order->hasBundleDiscount())
                            <span class="mt-1 block text-xs font-medium text-success">Potongan kepemilikan {{ $order->bundle_discount_amount_label }}</span>
                        @endif
                        <span class="mt-1 inline-block px-2 py-0.5 rounded text-xs font-medium {{ $bg }} {{ $text }}">
                            {{ $order->status_label }}
                        </span>
                        @if ($order->refund && ! $order->refund->isRefunded())
                            <span class="block mt-1 text-xs text-violet-700">Refund: {{ $order->refund->status_label }}</span>
                        @endif
                        @if ($order->isPaymentConfirmed())
                            <a href="{{ route('checkout.invoice', $order) }}"
                               class="block mt-1 text-xs text-bass-red hover:underline">Invoice (PDF)</a>
                        @endif
                        @if ($order->isPending())
                            <form method="POST" action="{{ route('checkout.cancel', $order) }}" class="mt-2"
                                  onsubmit="return confirm('Batalkan pesanan ini? Tagihan Midtrans tidak dapat digunakan lagi.');">
                                @csrf
                                <button type="submit" class="text-xs font-medium text-bass-red hover:underline">
                                    Batalkan
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-6">{{ $orders->links() }}</div>
    @endif
</div>
@endsection
