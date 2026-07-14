@extends('layouts.app')

@section('title', 'Pesanan Saya')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <h1 class="text-2xl font-bold text-gray-900 mb-6">Pesanan Saya</h1>

    @if ($orders->isEmpty())
        <div class="text-center py-16 bg-white rounded-xl border border-gray-200">
            <p class="text-sm font-medium text-gray-900">Belum ada pesanan</p>
            <p class="mt-1 text-sm text-gray-500">Pembelian kursus Anda akan tercatat di sini.</p>
            <a href="{{ route('shop.index') }}"
               class="mt-5 inline-flex items-center justify-center min-h-[44px] px-6 rounded-lg bg-bass-red text-white text-sm font-semibold hover:bg-red-800 transition-colors">
                Jelajahi Katalog
            </a>
        </div>
    @else
        <div class="bg-white rounded-xl border border-gray-200 divide-y divide-gray-100 overflow-hidden">
            @foreach ($orders as $order)
                @php
                    $badge = match ($order->status) {
                        'paid' => 'bg-emerald-100 text-emerald-700',
                        'pending' => 'bg-amber-100 text-amber-700',
                        default => 'bg-gray-100 text-gray-600',
                    };
                @endphp

                <a href="{{ route('checkout.finish', $order) }}"
                   class="flex items-center gap-4 p-4 hover:bg-gray-50 transition-colors">
                    <div class="min-w-0 flex-1">
                        <p class="font-medium text-gray-900 truncate">{{ $order->course->title }}</p>
                        <p class="mt-0.5 text-xs text-gray-500 font-mono">{{ $order->order_code }}</p>
                        <p class="mt-0.5 text-xs text-gray-400">{{ $order->created_at->translatedFormat('d M Y, H:i') }}</p>
                    </div>

                    <div class="text-right flex-shrink-0">
                        <p class="font-semibold text-gray-900">{{ $order->amount_label }}</p>
                        <span class="mt-1 inline-block px-2 py-0.5 rounded text-xs font-medium {{ $badge }}">
                            {{ $order->status_label }}
                        </span>
                    </div>
                </a>
            @endforeach
        </div>

        <div class="mt-6">{{ $orders->links() }}</div>
    @endif
</div>
@endsection
