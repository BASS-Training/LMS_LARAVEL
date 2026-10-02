@extends('layouts.app')

@section('title', 'Manajemen Refund')

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Manajemen Refund</h1>
            <p class="mt-1 text-sm text-gray-500">Tinjau pengajuan dan pantau pengembalian dana penuh melalui Midtrans.</p>
        </div>
        <form method="GET">
            <select name="status" onchange="this.form.submit()" class="rounded-lg border-gray-300 text-sm focus:border-bass-red focus:ring-bass-red">
                <option value="">Semua status</option>
                @foreach (\App\Enums\RefundStatus::cases() as $option)
                    <option value="{{ $option->value }}" @selected($status === $option->value)>{{ ucfirst(str_replace('_', ' ', $option->value)) }}</option>
                @endforeach
            </select>
        </form>
    </div>

    @if (session('success'))
        <div class="mb-5 rounded-lg bg-success-soft border border-success/30 px-4 py-3 text-sm text-success">{{ session('success') }}</div>
    @endif

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden divide-y divide-gray-100">
        @forelse ($refunds as $refund)
            @php [$bg, $text] = $refund->status_colors; @endphp
            <a href="{{ route('admin.refunds.show', $refund) }}" class="flex flex-col sm:flex-row sm:items-center gap-3 px-5 py-4 hover:bg-gray-50">
                <div class="flex-1 min-w-0">
                    <p class="font-semibold text-gray-900 truncate">{{ $refund->order->course->title }}</p>
                    <p class="text-sm text-gray-500">{{ $refund->order->user->name }} &middot; {{ $refund->order->order_code }}</p>
                    <p class="mt-0.5 text-xs text-gray-400">Diajukan {{ $refund->requested_at->diffForHumans() }}</p>
                </div>
                <div class="sm:text-right">
                    <p class="font-bold text-gray-900">{{ $refund->amount_label }}</p>
                    <span class="inline-flex mt-1 px-2 py-0.5 rounded-full text-xs font-semibold {{ $bg }} {{ $text }}">{{ $refund->status_label }}</span>
                </div>
            </a>
        @empty
            <div class="px-5 py-12 text-center text-sm text-gray-500">Belum ada pengajuan refund.</div>
        @endforelse
    </div>

    <div class="mt-6">{{ $refunds->links() }}</div>
</div>
@endsection
