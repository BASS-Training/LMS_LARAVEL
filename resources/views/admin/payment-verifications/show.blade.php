@extends('layouts.app')

@section('title', 'Tinjau Pembayaran ' . $order->order_code)

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="{ rejecting: false }">

    <a href="{{ route('admin.payment-verifications.index') }}"
       class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-bass-red mb-5">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        Kembali ke antrian
    </a>

    <h1 class="text-2xl font-bold text-gray-900">Tinjau Pembayaran</h1>
    <p class="mt-1 text-sm text-gray-500">Pastikan dana benar-benar masuk sebelum membuka akses.</p>

    @if ($errors->has('verify'))
        <div class="mt-4 rounded-lg bg-error-soft border border-error/40 px-4 py-3 text-sm text-error">
            {{ $errors->first('verify') }}
        </div>
    @endif

    @php [$bg, $text] = $order->status_colors; @endphp

    <div class="mt-5 bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <div>
                <div class="text-xs text-gray-400">Status</div>
                <span class="inline-flex mt-0.5 px-2.5 py-1 rounded-full text-xs font-bold {{ $bg }} {{ $text }}">
                    {{ $order->status_label }}
                </span>
            </div>
            <div class="text-right">
                <div class="text-xs text-gray-400">Nominal</div>
                <div class="text-xl font-bold text-gray-900">{{ $order->amount_label }}</div>
            </div>
        </div>

        <dl class="divide-y divide-gray-100 text-sm">
            <div class="px-6 py-3 flex justify-between gap-4">
                <dt class="text-gray-500">Kursus</dt>
                <dd class="font-medium text-gray-900 text-right">{{ $order->course->title }}</dd>
            </div>
            <div class="px-6 py-3 flex justify-between gap-4">
                <dt class="text-gray-500">Pembeli</dt>
                <dd class="text-right text-gray-900">{{ $order->user->name }}<br>
                    <span class="text-gray-500 text-xs">{{ $order->user->email }}</span></dd>
            </div>
            <div class="px-6 py-3 flex justify-between gap-4">
                <dt class="text-gray-500">Kode pesanan</dt>
                <dd class="text-gray-900">{{ $order->order_code }}</dd>
            </div>
            <div class="px-6 py-3 flex justify-between gap-4">
                <dt class="text-gray-500">No. Invoice</dt>
                <dd class="text-gray-900">{{ $order->invoice_number }}</dd>
            </div>
            <div class="px-6 py-3 flex justify-between gap-4">
                <dt class="text-gray-500">Metode</dt>
                <dd class="text-gray-900 capitalize">{{ str_replace('_', ' ', (string) $order->payment_type) }}</dd>
            </div>
            <div class="px-6 py-3 flex justify-between gap-4">
                <dt class="text-gray-500">ID transaksi Midtrans</dt>
                <dd class="text-gray-900 text-xs break-all">{{ $order->transaction_id ?: '-' }}</dd>
            </div>
            <div class="px-6 py-3 flex justify-between gap-4">
                <dt class="text-gray-500">Waktu pembayaran</dt>
                <dd class="text-gray-900">{{ optional($order->payment_confirmed_at)->format('d M Y, H:i') }}</dd>
            </div>
        </dl>

        <div class="px-6 py-3 border-t border-gray-100">
            <a href="{{ route('checkout.invoice', $order) }}"
               class="text-sm text-bass-red font-medium hover:underline">Unduh invoice (PDF)</a>
        </div>
    </div>

    {{-- Aksi --}}
    @if ($order->isAwaitingVerification())
        <div class="mt-5 bg-white rounded-xl border border-gray-200 p-6">
            <div x-show="!rejecting" class="flex flex-col sm:flex-row gap-3">
                <form method="POST" action="{{ route('admin.payment-verifications.approve', $order) }}" class="flex-1"
                      onsubmit="return confirm('Setujui pembayaran ini dan buka akses kursus untuk peserta?');">
                    @csrf
                    <button type="submit"
                            class="w-full inline-flex items-center justify-center gap-2 min-h-[48px] rounded-lg bg-bass-red text-white font-semibold hover:bg-bass-red-hover transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Setujui &amp; Buka Akses
                    </button>
                </form>
                <button type="button" @click="rejecting = true"
                        class="flex-1 inline-flex items-center justify-center min-h-[48px] rounded-lg border border-error/40 text-error font-semibold hover:bg-error-soft transition-colors">
                    Tolak
                </button>
            </div>

            <form x-show="rejecting" x-cloak method="POST"
                  action="{{ route('admin.payment-verifications.reject', $order) }}">
                @csrf
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Alasan penolakan</label>
                <textarea name="reason" rows="3" required minlength="3" maxlength="255"
                          class="w-full rounded-lg border-gray-300 focus:border-bass-red focus:ring-bass-red text-sm"
                          placeholder="Mis. dana tidak ditemukan saat rekonsiliasi rekening.">{{ old('reason') }}</textarea>
                @error('reason')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                <p class="mt-1.5 text-xs text-gray-500">Penolakan otomatis membuat full refund {{ $order->amount_label }}. Sistem memprosesnya lewat Midtrans jika metode pembayaran mendukung.</p>
                <div class="mt-3 flex gap-3">
                    <button type="submit"
                            class="inline-flex items-center justify-center px-5 min-h-[44px] rounded-lg bg-bass-red text-white font-semibold hover:bg-bass-red-hover transition-colors">
                        Konfirmasi Tolak
                    </button>
                    <button type="button" @click="rejecting = false"
                            class="inline-flex items-center justify-center px-5 min-h-[44px] rounded-lg border border-gray-300 text-gray-700 font-semibold hover:bg-gray-50 transition-colors">
                        Batal
                    </button>
                </div>
            </form>
        </div>
    @else
        <div class="mt-5 rounded-xl bg-gray-50 border border-gray-200 px-6 py-4 text-sm text-gray-600">
            Pesanan ini sudah diproses
            @if ($order->verifiedBy)
                oleh <strong>{{ $order->verifiedBy->name }}</strong>
                pada {{ optional($order->verified_at)->format('d M Y, H:i') }}.
            @endif
            @if ($order->isRejected() && $order->rejection_reason)
                <div class="mt-1 text-error">Alasan: {{ $order->rejection_reason }}</div>
            @endif
        </div>
    @endif
</div>
@endsection
