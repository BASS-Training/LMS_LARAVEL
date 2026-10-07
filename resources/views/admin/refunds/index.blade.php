@extends('layouts.app')

@section('title', 'Manajemen Refund')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="{ policyOpen: false }">
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-5">
        <div class="flex items-start gap-4">
            <div class="hidden sm:flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-navy text-white shadow-sm">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 10h11a4 4 0 010 8H9m-6-8 4-4m-4 4 4 4M17 6h4m-2-2v4"/>
                </svg>
            </div>
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-bass-red">Keuangan</p>
                <h1 class="mt-1 text-2xl font-bold tracking-tight text-gray-900 sm:text-3xl">Manajemen Refund</h1>
                <p class="mt-1.5 max-w-2xl text-sm text-gray-500">Tinjau permintaan peserta, pantau proses Midtrans, dan selesaikan pengembalian dana manual.</p>
            </div>
        </div>
        <div class="flex flex-col sm:flex-row gap-2.5">
            <a href="{{ route('admin.payment-verifications.index') }}"
               class="inline-flex min-h-[44px] items-center justify-center gap-2 rounded-xl border border-gray-300 bg-white px-4 text-sm font-semibold text-gray-700 shadow-sm transition hover:border-gray-400 hover:bg-gray-50">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Verifikasi Pembayaran
            </a>
            <button type="button" @click="policyOpen = !policyOpen"
                    class="inline-flex min-h-[44px] items-center justify-center gap-2 rounded-xl bg-navy px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-navy-light focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-navy/40">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>
                Atur Kebijakan
            </button>
        </div>
    </div>

    @if (session('success'))
        <div class="mt-6 flex items-start gap-3 rounded-xl border border-success/30 bg-success-soft px-4 py-3 text-sm text-success">
            <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            {{ session('success') }}
        </div>
    @endif
    @if ($errors->any())
        <div class="mt-6 flex items-start gap-3 rounded-xl border border-error/30 bg-error-soft px-4 py-3 text-sm text-error">
            <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5.07 19h13.86a2 2 0 001.74-3L13.74 4a2 2 0 00-3.48 0L3.33 16a2 2 0 001.74 3z"/></svg>
            {{ $errors->first() }}
        </div>
    @endif

    <div class="mt-7 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-2xl border border-amber-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-gray-600">Perlu tindakan</span>
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-warning-soft text-warning">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M10.3 3.6 2.8 17a2 2 0 001.7 3h15a2 2 0 001.7-3L13.7 3.6a2 2 0 00-3.4 0z"/></svg>
                </span>
            </div>
            <p class="mt-4 text-3xl font-bold tracking-tight text-gray-900">{{ $attentionCount }}</p>
            <p class="mt-1 text-xs text-gray-500">Menunggu keputusan atau tindak lanjut admin</p>
        </div>
        <div class="rounded-2xl border border-blue-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-gray-600">Diproses Midtrans</span>
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-info-soft text-navy">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.6m14.8 2A8 8 0 004.6 9m0 0H9m11 11v-5h-.6m0 0A8 8 0 014.6 15m14.8 0H15"/></svg>
                </span>
            </div>
            <p class="mt-4 text-3xl font-bold tracking-tight text-gray-900">{{ $processingCount }}</p>
            <p class="mt-1 text-xs text-gray-500">Menunggu konfirmasi pengembalian dari provider</p>
        </div>
        <div class="rounded-2xl border border-emerald-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-gray-600">Dana dikembalikan</span>
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-success-soft text-success">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.7 0-3 .9-3 2s1.3 2 3 2 3 .9 3 2-1.3 2-3 2m0-8V6m0 10v2m9-6a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
            </div>
            <p class="mt-4 text-2xl font-bold tracking-tight text-gray-900">Rp {{ number_format($refundedAmount, 0, ',', '.') }}</p>
            <p class="mt-1 text-xs text-gray-500">Total refund yang sudah diselesaikan</p>
        </div>
    </div>

    <div x-show="policyOpen" x-collapse x-cloak class="mt-5">
        <form method="POST" action="{{ route('admin.refunds.settings.update') }}" class="overflow-hidden rounded-2xl border border-navy/15 bg-white shadow-sm">
            @csrf
            @method('PATCH')
            <div class="grid lg:grid-cols-[1fr_auto]">
                <div class="bg-navy px-5 py-5 text-white sm:px-6">
                    <div class="flex items-start gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/10">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5-7A11 11 0 0112 3a11 11 0 01-8 2c0 5.5 3.5 10.7 8 12 4.5-1.3 8-6.5 8-12z"/></svg>
                        </span>
                        <div>
                            <h2 class="font-semibold">Kebijakan Refund Global</h2>
                            <p class="mt-1 text-sm text-white/70">Aturan baru berlaku untuk pembelian berikutnya. Sertifikat yang sudah terbit selalu memblokir refund.</p>
                        </div>
                    </div>
                </div>
                <div class="grid gap-4 p-5 sm:grid-cols-2 lg:min-w-[840px] lg:grid-cols-[1.3fr_1fr_1fr_1.2fr_auto] lg:items-end lg:p-6">
                    <div>
                        <label for="policy_mode" class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Aturan refund</label>
                        <select id="policy_mode" name="policy_mode" class="mt-1.5 w-full rounded-xl border-gray-300 text-sm font-semibold focus:border-navy focus:ring-navy">
                            <option value="company_issue" @selected(old('policy_mode', $settings->policy_mode) === 'company_issue')>Kendala pihak kami</option>
                            <option value="seven_day" @selected(old('policy_mode', $settings->policy_mode) === 'seven_day')>Refund berbatas hari</option>
                        </select>
                    </div>
                    <div>
                        <label for="request_window_days" class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Batas pengajuan</label>
                        <div class="relative mt-1.5">
                            <input id="request_window_days" name="request_window_days" type="number" min="1" max="365" required
                                   value="{{ old('request_window_days', $settings->request_window_days) }}"
                                   class="w-full rounded-xl border-gray-300 pr-14 text-sm font-semibold focus:border-navy focus:ring-navy">
                            <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-xs text-gray-400">hari</span>
                        </div>
                    </div>
                    <div>
                        <label for="max_progress_percentage" class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Progres maksimum</label>
                        <div class="relative mt-1.5">
                            <input id="max_progress_percentage" name="max_progress_percentage" type="number" min="0" max="100" required
                                   value="{{ old('max_progress_percentage', $settings->max_progress_percentage) }}"
                                   class="w-full rounded-xl border-gray-300 pr-10 text-sm font-semibold focus:border-navy focus:ring-navy">
                            <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-xs text-gray-400">%</span>
                        </div>
                    </div>
                    <label class="flex min-h-[42px] cursor-pointer items-center gap-3 rounded-xl border border-gray-200 px-4 py-2">
                        <input type="hidden" name="requests_enabled" value="0">
                        <input type="checkbox" name="requests_enabled" value="1" @checked(old('requests_enabled', $featureSettings->refund_requests_enabled)) class="rounded border-gray-300 text-bass-red focus:ring-bass-red">
                        <span><span class="block text-sm font-semibold text-gray-800">Pengajuan baru</span><span class="block text-xs text-gray-500">Tampilkan form refund peserta</span></span>
                    </label>
                    <button class="inline-flex min-h-[42px] items-center justify-center gap-2 rounded-xl bg-bass-red px-5 text-sm font-semibold text-white shadow-sm transition hover:bg-bass-red-hover focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-bass-red/40 sm:col-span-2 lg:col-span-1">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Simpan
                    </button>
                </div>
            </div>
        </form>
    </div>

    <div class="mt-7 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-100 px-4 py-4 sm:px-5">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h2 class="font-semibold text-gray-900">Daftar Pengajuan</h2>
                    <p class="mt-0.5 text-xs text-gray-500">{{ $refunds->total() }} refund pada filter ini</p>
                </div>
                <nav class="-mx-1 flex gap-1 overflow-x-auto px-1 pb-1 lg:pb-0" aria-label="Filter status refund">
                    <a href="{{ route('admin.refunds.index') }}"
                       class="inline-flex shrink-0 items-center gap-1.5 rounded-lg px-3 py-2 text-xs font-semibold transition {{ $status === '' ? 'bg-navy text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100' }}">
                        Semua
                        <span class="{{ $status === '' ? 'text-white/70' : 'text-gray-400' }}">{{ $statusCounts->sum() }}</span>
                    </a>
                    @foreach (\App\Enums\RefundStatus::cases() as $option)
                        <a href="{{ route('admin.refunds.index', ['status' => $option->value]) }}"
                           class="inline-flex shrink-0 items-center gap-1.5 rounded-lg px-3 py-2 text-xs font-semibold transition {{ $status === $option->value ? 'bg-navy text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100' }}">
                            {{ $option->label() }}
                            <span class="{{ $status === $option->value ? 'text-white/70' : 'text-gray-400' }}">{{ $statusCounts[$option->value] ?? 0 }}</span>
                        </a>
                    @endforeach
                </nav>
            </div>
        </div>

        <div class="divide-y divide-gray-100">
            @forelse ($refunds as $refund)
                @php [$bg, $text] = $refund->status_colors; @endphp
                <div class="group px-4 py-4 transition hover:bg-gray-50/80 sm:px-5">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center">
                        <div class="flex min-w-0 flex-1 items-start gap-3.5">
                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-navy/10 text-sm font-bold text-navy">
                                {{ strtoupper(substr($refund->order->user->name, 0, 2)) }}
                            </div>
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="truncate font-semibold text-gray-900">{{ $refund->order->order_title }}</p>
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $bg }} {{ $text }}">{{ $refund->status_label }}</span>
                                </div>
                                <p class="mt-1 truncate text-sm text-gray-600">{{ $refund->order->user->name }} <span class="text-gray-300">&middot;</span> {{ $refund->order->order_code }}</p>
                                <p class="mt-1 line-clamp-1 text-xs text-gray-400">{{ $refund->reason }}</p>
                            </div>
                        </div>
                        <div class="flex items-center justify-between gap-4 border-t border-gray-100 pt-3 lg:min-w-[290px] lg:border-0 lg:pt-0">
                            <div class="lg:text-right">
                                <p class="font-bold text-gray-900">{{ $refund->amount_label }}</p>
                                <p class="mt-0.5 text-xs text-gray-400">{{ $refund->requested_at->translatedFormat('d M Y, H:i') }}</p>
                            </div>
                            <a href="{{ route('admin.refunds.show', $refund) }}"
                               class="inline-flex min-h-[40px] shrink-0 items-center justify-center gap-2 rounded-xl border border-gray-300 bg-white px-4 text-sm font-semibold text-gray-700 shadow-sm transition group-hover:border-navy/30 group-hover:text-navy hover:!border-navy hover:!bg-navy hover:!text-white">
                                Tinjau
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 5 7 7-7 7"/></svg>
                            </a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="px-5 py-16 text-center">
                    <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-gray-100 text-gray-400">
                        <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h11a4 4 0 010 8H9m-6-8 4-4m-4 4 4 4"/></svg>
                    </span>
                    <p class="mt-4 font-semibold text-gray-900">Tidak ada refund pada status ini</p>
                    <p class="mt-1 text-sm text-gray-500">Pengajuan baru akan muncul di sini saat peserta mengirim permintaan.</p>
                    @if ($status !== '')
                        <a href="{{ route('admin.refunds.index') }}" class="mt-4 inline-flex text-sm font-semibold text-bass-red hover:underline">Lihat semua refund</a>
                    @endif
                </div>
            @endforelse
        </div>

        @if ($refunds->hasPages())
            <div class="border-t border-gray-100 px-5 py-4">{{ $refunds->links() }}</div>
        @endif
    </div>
</div>
@endsection
