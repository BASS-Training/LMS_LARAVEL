@extends('layouts.app')

@section('title', 'Metode pembayaran — ' . $productTitle)

@section('content')
@php
    // Data ringkas untuk Alpine (ringkasan biaya di panel kanan).
    $optionsJs = collect($options)->mapWithKeys(fn ($o) => [$o['key'] => [
        'label' => $o['label'],
        'fee'   => $o['fee'],
        'total' => $o['total'],
    ]])->toArray();
    $firstKey = $options[0]['key'] ?? '';
    $rupiah = fn ($n) => 'Rp ' . number_format((int) $n, 0, ',', '.');
    $checkoutData = [
        'selected' => $firstKey,
        'options' => $optionsJs,
        'base' => (int) $discountedBase,
    ];
@endphp

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8"
     x-data="{{ Js::from($checkoutData) }}">

    <a href="{{ $productUrl }}" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-bass-red mb-5">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        Kembali
    </a>

    <h1 class="text-2xl font-bold text-gray-900">Metode pembayaran</h1>

    @if ($errors->has('shop'))
        <div class="mt-5 rounded-lg bg-error-soft border border-error/40 px-4 py-3 text-sm text-error">
            {{ $errors->first('shop') }}
        </div>
    @endif
    @if (session('success'))
        <div class="mt-5 rounded-lg bg-success-soft border border-success/30 px-4 py-3 text-sm text-success">
            {{ session('success') }}
        </div>
    @endif
    @if ($couponError || $errors->has('coupon'))
        <div class="mt-5 rounded-lg bg-error-soft border border-error/40 px-4 py-3 text-sm text-error">
            {{ $errors->first('coupon') ?: $couponError }}
        </div>
    @endif

    @if ($couponsEnabled)
        <div class="mt-6 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            @if ($couponQuote)
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-success-soft text-success">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 13 4 4L19 7"/></svg>
                        </span>
                        <div>
                            <p class="text-sm font-semibold text-gray-900">Kupon {{ $couponQuote['code'] }} aktif</p>
                            <p class="text-xs text-gray-500">Anda menghemat {{ $rupiah($couponQuote['discount']) }}.</p>
                        </div>
                    </div>
                    <form method="POST" action="{{ $couponRemoveAction }}">
                        @csrf
                        @method('DELETE')
                        <button class="inline-flex min-h-[40px] items-center justify-center rounded-lg border border-gray-300 px-4 text-sm font-semibold text-gray-600 hover:bg-gray-50">Hapus Kupon</button>
                    </form>
                </div>
            @else
                <form method="POST" action="{{ $couponApplyAction }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
                    @csrf
                    <div class="flex-1">
                        <label for="coupon_code" class="block text-sm font-semibold text-gray-700">Punya kode kupon?</label>
                        <input id="coupon_code" name="coupon_code" value="{{ old('coupon_code') }}" maxlength="50" required
                               class="mt-1.5 w-full rounded-lg border-gray-300 uppercase focus:border-bass-red focus:ring-bass-red"
                               placeholder="Contoh: HEMAT10">
                    </div>
                    <button class="inline-flex min-h-[42px] items-center justify-center rounded-lg bg-navy px-5 text-sm font-semibold text-white hover:bg-navy-light">Gunakan Kupon</button>
                </form>
            @endif
        </div>
    @endif

    <form method="POST" action="{{ $checkoutAction }}" class="mt-6 grid grid-cols-1 lg:grid-cols-3 gap-6">
        @csrf
        <input type="hidden" name="refund_policy_mode" value="{{ $refundSettings->policy_mode }}">

        {{-- ─────────── KIRI: daftar metode ─────────── --}}
        <div class="lg:col-span-2 space-y-3">
            @forelse ($options as $i => $opt)
                <label class="flex items-center gap-4 rounded-xl border p-4 cursor-pointer transition-colors"
                       :class="selected === '{{ $opt['key'] }}'
                           ? 'border-bass-red ring-1 ring-bass-red bg-error-soft/40'
                           : 'border-gray-200 hover:border-gray-300'">
                    <input type="radio" name="method" value="{{ $opt['key'] }}" @disabled(! $methodsEnabled)
                           x-model="selected"
                           class="h-4 w-4 text-bass-red border-gray-300 focus:ring-bass-red">

                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <p class="font-semibold text-gray-900">{{ $opt['label'] }}</p>
                            @if ($i === 0 && $opt['fee'] > 0)
                                <span class="inline-flex items-center rounded-full bg-success-soft px-2 py-0.5 text-[11px] font-semibold text-success">
                                    Termurah
                                </span>
                            @endif
                        </div>
                        @if ($opt['description'])
                            <p class="text-sm text-gray-500 truncate">{{ $opt['description'] }}</p>
                        @endif
                    </div>

                    <div class="text-right flex-shrink-0">
                        @if ($opt['fee'] > 0)
                            <span class="text-sm text-gray-500">+{{ $rupiah($opt['fee']) }}</span>
                        @else
                            <span class="text-sm font-medium text-success">Gratis</span>
                        @endif
                    </div>
                </label>
            @empty
                <div class="rounded-xl border border-gray-200 p-6 text-center text-sm text-gray-500">
                    Metode pembayaran belum tersedia. Hubungi admin.
                </div>
            @endforelse
        </div>

        {{-- ─────────── KANAN: ringkasan (sticky) ─────────── --}}
        <div class="lg:col-span-1">
            <div class="lg:sticky lg:top-24 bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="flex items-center gap-3 p-4 border-b border-gray-100">
                    <div class="w-14 h-14 rounded-lg bg-gray-100 overflow-hidden flex-shrink-0">
                        @if ($thumbnail)
                            <img src="{{ asset('storage/' . $thumbnail) }}" alt="{{ $productTitle }}" class="w-full h-full object-cover">
                        @else
                            <div class="w-full h-full flex items-center justify-center">
                                <svg class="w-6 h-6 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                            </div>
                        @endif
                    </div>
                    <p class="font-semibold text-gray-900 text-sm line-clamp-2">{{ $productTitle }}</p>
                </div>

                <div class="p-5 space-y-4">
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between text-gray-600">
                            <dt>{{ $priceLabel }}</dt>
                            <dd>{{ $rupiah($base) }}</dd>
                        </div>
                        @if ($bundleDiscount > 0)
                            <div class="flex justify-between text-success">
                                <dt>Potongan kursus dimiliki</dt>
                                <dd>-{{ $rupiah($bundleDiscount) }}</dd>
                            </div>
                        @endif
                        @if ($couponQuote)
                            <div class="flex justify-between text-success">
                                <dt>Kupon {{ $couponQuote['code'] }}</dt>
                                <dd>-{{ $rupiah($couponQuote['discount']) }}</dd>
                            </div>
                        @endif
                        <div class="flex justify-between text-gray-600">
                            <dt>Biaya layanan</dt>
                            <dd x-text="(options[selected]?.fee ?? 0) > 0 ? 'Rp ' + Number(options[selected].fee).toLocaleString('id-ID') : 'Gratis'">{{ $rupiah($options[0]['fee'] ?? 0) }}</dd>
                        </div>
                        <div class="flex justify-between pt-3 border-t border-gray-100">
                            <dt class="font-semibold text-gray-900">Total</dt>
                            <dd class="text-lg font-bold text-bass-red" x-text="'Rp ' + Number(options[selected]?.total ?? base).toLocaleString('id-ID')">{{ $rupiah($options[0]['total'] ?? $base) }}</dd>
                        </div>
                    </dl>

                    @include('shop.partials.refund-consent')
                    @error('refund_consent')<p class="text-sm text-error">{{ $message }}</p>@enderror
                    @error('refund_policy_mode')<p class="text-sm text-error">{{ $message }}</p>@enderror

                    <button type="submit" @disabled(empty($options))
                            class="w-full inline-flex items-center justify-center min-h-[48px] rounded-lg bg-bass-red text-white font-semibold hover:bg-bass-red-hover transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                        Lanjutkan Pembayaran
                    </button>

                    <p class="flex items-center justify-center gap-1.5 text-xs text-gray-400">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        Transaksi Anda diproses dengan aman
                    </p>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
