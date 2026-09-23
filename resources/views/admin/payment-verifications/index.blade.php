@extends('layouts.app')

@section('title', 'Verifikasi Pembayaran')

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Verifikasi Pembayaran</h1>
        <p class="mt-1 text-sm text-gray-500">
            Pesanan berikut sudah dibayar (uang dikonfirmasi Midtrans) tapi menunggu persetujuanmu
            sebelum akses kursus dibuka.
        </p>
    </div>

    @if (session('success'))
        <div class="mb-5 rounded-lg bg-success-soft border border-success/30 px-4 py-3 text-sm text-success">
            {{ session('success') }}
        </div>
    @endif
    @if ($errors->has('verify'))
        <div class="mb-5 rounded-lg bg-error-soft border border-error/40 px-4 py-3 text-sm text-error">
            {{ $errors->first('verify') }}
        </div>
    @endif

    {{-- Antrian menunggu --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="font-semibold text-gray-900">Menunggu verifikasi</h2>
            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-warning-soft text-warning">
                {{ $orders->total() }} pesanan
            </span>
        </div>

        @forelse ($orders as $order)
            <div class="px-5 py-4 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center gap-3">
                <div class="flex-1 min-w-0">
                    <div class="font-semibold text-gray-900 truncate">{{ $order->course->title }}</div>
                    <div class="text-sm text-gray-500">
                        {{ $order->user->name }} &middot; {{ $order->user->email }}
                    </div>
                    <div class="text-xs text-gray-400 mt-0.5">
                        {{ $order->order_code }} &middot; dibayar {{ optional($order->payment_confirmed_at)->diffForHumans() }}
                    </div>
                </div>
                <div class="text-right">
                    <div class="font-bold text-gray-900">{{ $order->amount_label }}</div>
                    <div class="text-xs text-gray-400 capitalize">{{ str_replace('_', ' ', (string) $order->payment_type) }}</div>
                </div>
                <a href="{{ route('admin.payment-verifications.show', $order) }}"
                   class="inline-flex items-center justify-center px-4 py-2 rounded-lg bg-bass-red text-white text-sm font-semibold hover:bg-red-800 transition-colors">
                    Tinjau
                </a>
            </div>
        @empty
            <div class="px-5 py-12 text-center">
                <svg class="mx-auto w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="mt-3 text-sm font-medium text-gray-900">Tidak ada yang menunggu verifikasi</p>
                <p class="text-sm text-gray-500">Semua pembayaran sudah diproses.</p>
            </div>
        @endforelse

        @if ($orders->hasPages())
            <div class="px-5 py-4">{{ $orders->links() }}</div>
        @endif
    </div>

    {{-- Riwayat keputusan terbaru --}}
    @if ($recent->isNotEmpty())
        <h2 class="mt-8 mb-3 font-semibold text-gray-900">Keputusan terbaru</h2>
        <div class="bg-white rounded-xl border border-gray-200 divide-y divide-gray-100">
            @foreach ($recent as $order)
                @php [$bg, $text] = $order->status_colors; @endphp
                <div class="px-5 py-3 flex items-center gap-3 text-sm">
                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold {{ $bg }} {{ $text }}">
                        {{ $order->status_label }}
                    </span>
                    <span class="flex-1 min-w-0 truncate text-gray-700">
                        {{ $order->course->title }} — {{ $order->user->name }}
                    </span>
                    <span class="text-xs text-gray-400 whitespace-nowrap">
                        oleh {{ optional($order->verifiedBy)->name ?? 'sistem' }} &middot;
                        {{ optional($order->verified_at)->diffForHumans() }}
                    </span>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
