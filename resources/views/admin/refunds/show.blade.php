@extends('layouts.app')

@section('title', 'Refund ' . $refund->order->order_code)

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="{ rejecting: false }">
    <a href="{{ route('admin.refunds.index') }}" class="text-sm text-gray-500 hover:text-bass-red">&larr; Kembali ke daftar refund</a>

    <div class="mt-5 flex items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Refund Penuh</h1>
            <p class="mt-1 text-sm text-gray-500">{{ $refund->order->order_code }}</p>
        </div>
        @php [$bg, $text] = $refund->status_colors; @endphp
        <span class="px-2.5 py-1 rounded-full text-xs font-semibold {{ $bg }} {{ $text }}">{{ $refund->status_label }}</span>
    </div>

    @if (session('success'))
        <div class="mt-5 rounded-lg bg-success-soft border border-success/30 px-4 py-3 text-sm text-success">{{ session('success') }}</div>
    @endif
    @if ($errors->has('refund'))
        <div class="mt-5 rounded-lg bg-error-soft border border-error/40 px-4 py-3 text-sm text-error">{{ $errors->first('refund') }}</div>
    @endif

    <div class="mt-5 bg-white rounded-xl border border-gray-200 overflow-hidden">
        <dl class="divide-y divide-gray-100 text-sm">
            <div class="px-6 py-3 flex justify-between gap-4"><dt class="text-gray-500">Peserta</dt><dd class="text-right text-gray-900">{{ $refund->order->user->name }}<br><span class="text-xs text-gray-500">{{ $refund->order->user->email }}</span></dd></div>
            <div class="px-6 py-3 flex justify-between gap-4"><dt class="text-gray-500">Kursus</dt><dd class="font-medium text-right text-gray-900">{{ $refund->order->course->title }}</dd></div>
            <div class="px-6 py-3 flex justify-between gap-4"><dt class="text-gray-500">Nominal penuh</dt><dd class="font-bold text-gray-900">{{ $refund->amount_label }}</dd></div>
            <div class="px-6 py-3"><dt class="text-gray-500">Alasan</dt><dd class="mt-1 whitespace-pre-line text-gray-900">{{ $refund->reason }}</dd></div>
            @if ($refund->admin_note)
                <div class="px-6 py-3"><dt class="text-gray-500">Catatan admin</dt><dd class="mt-1 whitespace-pre-line text-gray-900">{{ $refund->admin_note }}</dd></div>
            @endif
            @if ($refund->provider_refund_id)
                <div class="px-6 py-3 flex justify-between gap-4"><dt class="text-gray-500">Referensi Midtrans</dt><dd class="text-gray-900 break-all">{{ $refund->provider_refund_id }}</dd></div>
            @endif
            @if ($refund->failure_message)
                <div class="px-6 py-3"><dt class="text-error">Pesan kegagalan</dt><dd class="mt-1 text-error">{{ $refund->failure_message }}</dd></div>
            @endif
        </dl>
    </div>

    @if ($refund->isRequested())
        <div class="mt-5 bg-white rounded-xl border border-gray-200 p-6">
            <form method="POST" action="{{ route('admin.refunds.approve', $refund) }}" onsubmit="return confirm('Setujui dan kirim full refund {{ $refund->amount_label }} ke Midtrans?');">
                @csrf
                <label class="block text-sm font-medium text-gray-700">Catatan admin (opsional)</label>
                <textarea name="note" rows="2" maxlength="1000" class="mt-1 w-full rounded-lg border-gray-300 focus:border-bass-red focus:ring-bass-red text-sm"></textarea>
                <button class="mt-3 w-full min-h-[48px] rounded-lg bg-bass-red text-white font-semibold hover:bg-bass-red-hover">Setujui &amp; Proses Refund</button>
            </form>
            <button type="button" @click="rejecting = !rejecting" class="mt-3 w-full min-h-[44px] rounded-lg border border-error/40 text-error font-semibold hover:bg-error-soft">Tolak Pengajuan</button>
            <form x-show="rejecting" x-cloak method="POST" action="{{ route('admin.refunds.reject', $refund) }}" class="mt-3">
                @csrf
                <textarea name="note" rows="3" required minlength="3" maxlength="1000" class="w-full rounded-lg border-gray-300 focus:border-bass-red focus:ring-bass-red text-sm" placeholder="Alasan penolakan"></textarea>
                <button class="mt-2 w-full min-h-[44px] rounded-lg bg-gray-900 text-white font-semibold">Konfirmasi Penolakan</button>
            </form>
        </div>
    @elseif ($refund->isFailed() || $refund->isApproved())
        <form method="POST" action="{{ route('admin.refunds.retry', $refund) }}" class="mt-5" onsubmit="return confirm('Kirim ulang refund ini ke Midtrans dengan kunci yang sama?');">
            @csrf
            <button class="w-full min-h-[48px] rounded-lg bg-bass-red text-white font-semibold hover:bg-bass-red-hover">{{ $refund->isApproved() ? 'Proses Refund' : 'Coba Proses Lagi' }}</button>
        </form>
    @elseif ($refund->requiresManualProcessing())
        <form method="POST" action="{{ route('admin.refunds.complete-manual', $refund) }}" class="mt-5 bg-white rounded-xl border border-gray-200 p-6">
            @csrf
            <p class="text-sm text-gray-600">Midtrans tidak mendukung refund otomatis untuk metode ini. Kembalikan dana penuh di luar API, lalu catat referensinya.</p>
            <label class="mt-4 block text-sm font-medium text-gray-700">Referensi pengembalian dana</label>
            <input name="reference" required minlength="3" maxlength="255" class="mt-1 w-full rounded-lg border-gray-300 focus:border-bass-red focus:ring-bass-red text-sm" placeholder="Nomor transfer / tiket bantuan">
            <label class="mt-3 block text-sm font-medium text-gray-700">Catatan (opsional)</label>
            <textarea name="note" rows="2" maxlength="1000" class="mt-1 w-full rounded-lg border-gray-300 focus:border-bass-red focus:ring-bass-red text-sm"></textarea>
            <button class="mt-4 w-full min-h-[48px] rounded-lg bg-bass-red text-white font-semibold hover:bg-bass-red-hover" onclick="return confirm('Pastikan dana sudah benar-benar dikembalikan. Tandai refund selesai?');">Tandai Dana Sudah Dikembalikan</button>
        </form>
    @elseif ($refund->status === \App\Enums\RefundStatus::Processing)
        <form method="POST" action="{{ route('admin.refunds.reconcile', $refund) }}" class="mt-5">
            @csrf
            <button class="w-full min-h-[48px] rounded-lg border border-gray-300 text-gray-700 font-semibold hover:bg-gray-50">Cek Status Terbaru di Midtrans</button>
        </form>
    @endif
</div>
@endsection
