@extends('layouts.app')

@section('title', 'Manajemen Kupon')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-bass-red">Commerce</p>
            <h1 class="mt-1 text-2xl font-bold text-gray-900 sm:text-3xl">Manajemen Kupon</h1>
            <p class="mt-1 text-sm text-gray-500">Siapkan diskon, cakupan kursus, periode, dan kuota penggunaan.</p>
        </div>
        <a href="{{ route('admin.coupons.create') }}" class="inline-flex min-h-[44px] items-center justify-center gap-2 rounded-xl bg-navy px-5 text-sm font-semibold text-white shadow-sm hover:bg-navy-light">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Buat Kupon
        </a>
    </div>

    @if (session('success'))
        <div class="mt-6 rounded-xl border border-success/30 bg-success-soft px-4 py-3 text-sm text-success">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="mt-6 rounded-xl border border-error/30 bg-error-soft px-4 py-3 text-sm text-error">{{ $errors->first() }}</div>
    @endif

    <section class="mt-7 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="grid gap-px bg-gray-100 lg:grid-cols-[1fr_1fr_auto]">
            <div class="bg-white p-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Kill switch server</p>
                <div class="mt-2 flex items-center gap-2">
                    <span class="h-2.5 w-2.5 rounded-full {{ $serverEnabled ? 'bg-success' : 'bg-error' }}"></span>
                    <p class="font-semibold text-gray-900">{{ $serverEnabled ? 'Aktif' : 'Dinonaktifkan' }}</p>
                </div>
                <p class="mt-1 text-xs text-gray-500">COUPONS_FEATURE_ENABLED={{ $serverEnabled ? 'true' : 'false' }}</p>
            </div>
            <div class="bg-white p-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Status checkout</p>
                <div class="mt-2 flex items-center gap-2">
                    <span class="h-2.5 w-2.5 rounded-full {{ $serverEnabled && $settings->checkout_enabled ? 'bg-success' : 'bg-gray-300' }}"></span>
                    <p class="font-semibold text-gray-900">{{ $serverEnabled && $settings->checkout_enabled ? 'Kupon dapat digunakan' : 'Kupon belum tersedia' }}</p>
                </div>
                <p class="mt-1 text-xs text-gray-500">Order biasa tetap berjalan saat kupon nonaktif.</p>
            </div>
            <form method="POST" action="{{ route('admin.coupons.checkout.update') }}" class="flex items-center bg-white p-5">
                @csrf
                @method('PATCH')
                <input type="hidden" name="checkout_enabled" value="{{ $settings->checkout_enabled ? 0 : 1 }}">
                <button @disabled(! $serverEnabled && ! $settings->checkout_enabled)
                        onclick="return confirm('{{ $settings->checkout_enabled ? 'Nonaktifkan kupon dari checkout?' : 'Aktifkan kupon untuk seluruh checkout web?' }}');"
                        class="inline-flex min-h-[42px] w-full items-center justify-center rounded-xl px-5 text-sm font-semibold transition {{ $settings->checkout_enabled ? 'border border-gray-300 text-gray-700 hover:bg-gray-50' : 'bg-bass-red text-white hover:bg-bass-red-hover' }} disabled:cursor-not-allowed disabled:opacity-40">
                    {{ $settings->checkout_enabled ? 'Nonaktifkan Checkout' : 'Aktifkan Checkout' }}
                </button>
            </form>
        </div>
    </section>

    <section class="mt-6 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="flex flex-col gap-3 border-b border-gray-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-semibold text-gray-900">Daftar Kupon</h2>
                <p class="text-xs text-gray-500">{{ $coupons->total() }} kupon ditemukan</p>
            </div>
            <form method="GET" class="flex gap-2">
                <input name="search" value="{{ $search }}" class="min-w-0 rounded-lg border-gray-300 text-sm focus:border-navy focus:ring-navy" placeholder="Cari kode kupon">
                <button class="rounded-lg border border-gray-300 px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50">Cari</button>
            </form>
        </div>

        <div class="divide-y divide-gray-100">
            @forelse ($coupons as $coupon)
                <div class="flex flex-col gap-4 px-5 py-4 lg:flex-row lg:items-center">
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="font-mono text-base font-bold text-gray-900">{{ $coupon->code }}</span>
                            <span class="rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $coupon->is_active ? 'bg-success-soft text-success' : 'bg-gray-100 text-gray-500' }}">{{ $coupon->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                            <span class="rounded-full bg-navy/10 px-2.5 py-1 text-[11px] font-semibold text-navy">{{ $coupon->applies_to_all_courses ? 'Semua course' : 'Course tertentu' }}</span>
                        </div>
                        <p class="mt-1.5 text-sm text-gray-600">Diskon {{ $coupon->discount_label }}@if($coupon->minimum_amount) &middot; Min. Rp {{ number_format($coupon->minimum_amount, 0, ',', '.') }}@endif</p>
                        <p class="mt-1 text-xs text-gray-400">
                            {{ $coupon->redemptions_count }} penggunaan
                            &middot; Kuota {{ $coupon->usage_limit ?? 'tanpa batas' }}
                            &middot; {{ $coupon->per_user_limit ?? 'Tanpa batas' }} per pengguna
                        </p>
                    </div>
                    <div class="text-sm text-gray-500 lg:min-w-[210px] lg:text-right">
                        @if ($coupon->starts_at || $coupon->expires_at)
                            <p>{{ $coupon->starts_at?->translatedFormat('d M Y') ?? 'Sekarang' }} - {{ $coupon->expires_at?->translatedFormat('d M Y') ?? 'Tanpa batas' }}</p>
                        @else
                            <p>Tanpa batas periode</p>
                        @endif
                    </div>
                    <div class="flex gap-2">
                        <a href="{{ route('admin.coupons.edit', $coupon) }}" class="inline-flex min-h-[40px] items-center justify-center rounded-lg border border-gray-300 px-4 text-sm font-semibold text-gray-700 hover:border-navy hover:text-navy">Edit</a>
                        <form method="POST" action="{{ route('admin.coupons.destroy', $coupon) }}" onsubmit="return confirm('Hapus kupon {{ $coupon->code }}?');">
                            @csrf
                            @method('DELETE')
                            <button class="inline-flex min-h-[40px] items-center justify-center rounded-lg border border-error/30 px-4 text-sm font-semibold text-error hover:bg-error-soft">Hapus</button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="px-5 py-14 text-center">
                    <p class="font-semibold text-gray-900">Belum ada kupon</p>
                    <p class="mt-1 text-sm text-gray-500">Buat kupon pertama sebelum mengaktifkan checkout.</p>
                </div>
            @endforelse
        </div>

        @if ($coupons->hasPages())
            <div class="border-t border-gray-100 px-5 py-4">{{ $coupons->links() }}</div>
        @endif
    </section>
</div>
@endsection
