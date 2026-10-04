@extends('layouts.app')

@section('title', 'Refund ' . $refund->order->order_code)

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="{ rejecting: @js($errors->has('note')) }">
    <a href="{{ route('admin.refunds.index') }}"
       class="inline-flex min-h-[40px] items-center gap-2 rounded-lg px-1 text-sm font-semibold text-gray-500 transition hover:text-navy">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15 19-7-7 7-7"/></svg>
        Kembali ke manajemen refund
    </a>

    @php [$bg, $text] = $refund->status_colors; @endphp
    <div class="relative mt-4 overflow-hidden rounded-2xl bg-navy px-5 py-6 text-white shadow-sm sm:px-7 sm:py-7">
        <div class="pointer-events-none absolute -right-12 -top-16 h-52 w-52 rounded-full bg-white/5"></div>
        <div class="pointer-events-none absolute -bottom-20 right-24 h-40 w-40 rounded-full border-[24px] border-white/5"></div>
        <div class="relative flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <div class="flex flex-wrap items-center gap-2.5">
                    <span class="rounded-full bg-white/10 px-2.5 py-1 text-[11px] font-bold uppercase tracking-wider text-white/80">Refund penuh</span>
                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $bg }} {{ $text }}">{{ $refund->status_label }}</span>
                </div>
                <h1 class="mt-4 text-2xl font-bold tracking-tight sm:text-3xl">{{ $refund->order->order_title }}</h1>
                <p class="mt-2 font-mono text-sm text-white/60">{{ $refund->order->order_code }}</p>
            </div>
            <div class="sm:text-right">
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-white/55">Nominal dikembalikan</p>
                <p class="mt-1 text-3xl font-bold tracking-tight sm:text-4xl">{{ $refund->amount_label }}</p>
                <p class="mt-1 text-xs text-white/55">Termasuk biaya layanan</p>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="mt-5 flex items-start gap-3 rounded-xl border border-success/30 bg-success-soft px-4 py-3 text-sm text-success">
            <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            {{ session('success') }}
        </div>
    @endif
    @if ($errors->any())
        <div class="mt-5 flex items-start gap-3 rounded-xl border border-error/30 bg-error-soft px-4 py-3 text-sm text-error">
            <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5.07 19h13.86a2 2 0 001.74-3L13.74 4a2 2 0 00-3.48 0L3.33 16a2 2 0 001.74 3z"/></svg>
            {{ $errors->first() }}
        </div>
    @endif

    <div class="mt-6 grid gap-6 lg:grid-cols-[minmax(0,1fr)_360px] lg:items-start">
        <div class="order-2 space-y-5 lg:order-1">
            <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                <div class="flex items-center gap-3 border-b border-gray-100 px-5 py-4 sm:px-6">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-navy/10 text-navy">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2m7-10a4 4 0 100-8 4 4 0 000 8zm13 10v-2a4 4 0 00-3-3.9m-1-12a4 4 0 010 7.8"/></svg>
                    </span>
                    <div>
                        <h2 class="font-semibold text-gray-900">Peserta dan Pembelian</h2>
                        <p class="text-xs text-gray-500">Informasi pemilik transaksi</p>
                    </div>
                </div>
                <div class="grid gap-px bg-gray-100 sm:grid-cols-2">
                    <div class="bg-white px-5 py-4 sm:px-6">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Peserta</p>
                        <div class="mt-2 flex items-center gap-3">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-navy text-sm font-bold text-white">{{ strtoupper(substr($refund->order->user->name, 0, 2)) }}</span>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-gray-900">{{ $refund->order->user->name }}</p>
                                <p class="truncate text-xs text-gray-500">{{ $refund->order->user->email }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white px-5 py-4 sm:px-6">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Metode pembayaran</p>
                        <p class="mt-2 text-sm font-semibold capitalize text-gray-900">{{ str_replace('_', ' ', (string) $refund->order->payment_type) ?: '-' }}</p>
                        <p class="mt-1 text-xs text-gray-500">Invoice {{ $refund->order->invoice_number ?: '-' }}</p>
                    </div>
                    <div class="bg-white px-5 py-4 sm:px-6">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Diajukan</p>
                        <p class="mt-2 text-sm font-semibold text-gray-900">{{ $refund->requested_at->translatedFormat('d F Y, H:i') }}</p>
                        <p class="mt-1 text-xs text-gray-500">{{ $refund->requested_at->diffForHumans() }}</p>
                    </div>
                    <div class="bg-white px-5 py-4 sm:px-6">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Ditinjau oleh</p>
                        <p class="mt-2 text-sm font-semibold text-gray-900">{{ optional($refund->reviewer)->name ?? 'Belum ditinjau' }}</p>
                        <p class="mt-1 text-xs text-gray-500">{{ optional($refund->reviewed_at)?->translatedFormat('d M Y, H:i') ?? 'Menunggu keputusan admin' }}</p>
                    </div>
                </div>
            </section>

            <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
                <div class="flex items-center gap-3">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-bass-red-soft text-bass-red">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 4v-4z"/></svg>
                    </span>
                    <div>
                        <h2 class="font-semibold text-gray-900">Alasan Pengajuan</h2>
                        <p class="text-xs text-gray-500">Disampaikan oleh peserta</p>
                    </div>
                </div>
                <blockquote class="mt-4 rounded-xl border-l-4 border-bass-red bg-gray-50 px-4 py-3 text-sm leading-6 text-gray-700">{{ $refund->reason }}</blockquote>
                @if ($refund->admin_note)
                    <div class="mt-4 border-t border-gray-100 pt-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Catatan admin</p>
                        <p class="mt-2 whitespace-pre-line text-sm leading-6 text-gray-700">{{ $refund->admin_note }}</p>
                    </div>
                @endif
            </section>

            @if ($refund->provider_refund_id || $refund->failure_message || $refund->attempts > 0)
                <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                    <div class="border-b border-gray-100 px-5 py-4 sm:px-6">
                        <h2 class="font-semibold text-gray-900">Informasi Pemrosesan</h2>
                        <p class="mt-0.5 text-xs text-gray-500">Referensi teknis untuk penelusuran transaksi</p>
                    </div>
                    <dl class="divide-y divide-gray-100 text-sm">
                        <div class="flex flex-col gap-1 px-5 py-3.5 sm:flex-row sm:justify-between sm:gap-4 sm:px-6">
                            <dt class="text-gray-500">Percobaan pemrosesan</dt>
                            <dd class="font-semibold text-gray-900">{{ $refund->attempts }} kali</dd>
                        </div>
                        @if ($refund->provider_refund_id)
                            <div class="flex flex-col gap-1 px-5 py-3.5 sm:flex-row sm:justify-between sm:gap-4 sm:px-6">
                                <dt class="text-gray-500">Referensi Midtrans</dt>
                                <dd class="break-all font-mono text-xs text-gray-900">{{ $refund->provider_refund_id }}</dd>
                            </div>
                        @endif
                        @if ($refund->failure_message)
                            <div class="bg-error-soft/50 px-5 py-4 sm:px-6">
                                <dt class="font-semibold text-error">Pesan kegagalan</dt>
                                <dd class="mt-1 text-sm leading-6 text-error">{{ $refund->failure_message }}</dd>
                            </div>
                        @endif
                    </dl>
                </section>
            @endif
        </div>

        <aside class="order-1 lg:order-2 lg:sticky lg:top-24">
            @if ($refund->isRequested())
                <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                    <div class="border-b border-gray-100 px-5 py-4">
                        <div class="flex items-center gap-3">
                            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-warning-soft text-warning">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M10.3 3.6 2.8 17a2 2 0 001.7 3h15a2 2 0 001.7-3L13.7 3.6a2 2 0 00-3.4 0z"/></svg>
                            </span>
                            <div>
                                <h2 class="font-semibold text-gray-900">Keputusan Admin</h2>
                                <p class="text-xs text-gray-500">Periksa detail sebelum memproses</p>
                            </div>
                        </div>
                    </div>
                    <div class="p-5">
                        <form x-show="!rejecting" method="POST" action="{{ route('admin.refunds.approve', $refund) }}"
                              onsubmit="return confirm('Setujui dan kirim full refund {{ $refund->amount_label }} ke Midtrans?');">
                            @csrf
                            <label for="approval_note" class="block text-sm font-semibold text-gray-700">Catatan internal <span class="font-normal text-gray-400">(opsional)</span></label>
                            <textarea id="approval_note" name="note" rows="3" maxlength="1000"
                                      class="mt-2 w-full rounded-xl border-gray-300 text-sm focus:border-navy focus:ring-navy"
                                      placeholder="Tambahkan catatan keputusan...">{{ old('note') }}</textarea>
                            <button class="mt-4 inline-flex min-h-[48px] w-full items-center justify-center gap-2 rounded-xl bg-bass-red px-5 font-semibold text-white shadow-sm transition hover:bg-bass-red-hover focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-bass-red/40">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Setujui &amp; Proses
                            </button>
                        </form>
                        <button x-show="!rejecting" type="button" @click="rejecting = true"
                                class="mt-2.5 inline-flex min-h-[44px] w-full items-center justify-center gap-2 rounded-xl border border-gray-300 bg-white px-5 text-sm font-semibold text-gray-700 transition hover:border-error/40 hover:bg-error-soft hover:text-error">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12"/></svg>
                            Tolak Pengajuan
                        </button>

                        <form x-show="rejecting" x-cloak method="POST" action="{{ route('admin.refunds.reject', $refund) }}">
                            @csrf
                            <div class="rounded-xl border border-error/20 bg-error-soft/50 p-3 text-xs leading-5 text-error">Pengajuan akan ditutup dan tidak dikirim ke Midtrans.</div>
                            <label for="rejection_note" class="mt-4 block text-sm font-semibold text-gray-700">Alasan penolakan</label>
                            <textarea id="rejection_note" name="note" rows="4" required minlength="3" maxlength="1000"
                                      class="mt-2 w-full rounded-xl border-gray-300 text-sm focus:border-error focus:ring-error"
                                      placeholder="Jelaskan alasan pengajuan ditolak...">{{ old('note') }}</textarea>
                            <button class="mt-4 inline-flex min-h-[46px] w-full items-center justify-center gap-2 rounded-xl bg-error px-5 text-sm font-semibold text-white transition hover:opacity-90">
                                Konfirmasi Penolakan
                            </button>
                            <button type="button" @click="rejecting = false" class="mt-2 inline-flex min-h-[42px] w-full items-center justify-center rounded-xl text-sm font-semibold text-gray-500 hover:bg-gray-50 hover:text-gray-700">Batal</button>
                        </form>
                    </div>
                </div>
            @elseif ($refund->isFailed() || $refund->isApproved())
                <div class="rounded-2xl border {{ $refund->isFailed() ? 'border-error/25' : 'border-blue-200' }} bg-white p-5 shadow-sm">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl {{ $refund->isFailed() ? 'bg-error-soft text-error' : 'bg-info-soft text-navy' }}">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.6m14.8 2A8 8 0 004.6 9m0 0H9m11 11v-5h-.6m0 0A8 8 0 014.6 15m14.8 0H15"/></svg>
                    </span>
                    <h2 class="mt-4 font-semibold text-gray-900">{{ $refund->isApproved() ? 'Siap diproses' : 'Proses sebelumnya gagal' }}</h2>
                    <p class="mt-1.5 text-sm leading-6 text-gray-500">{{ $refund->isApproved() ? 'Kirim permintaan refund penuh ke Midtrans.' : 'Periksa pesan kegagalan, lalu coba kembali dengan kunci refund yang sama.' }}</p>
                    <form method="POST" action="{{ route('admin.refunds.retry', $refund) }}" class="mt-5" onsubmit="return confirm('Kirim refund ini ke Midtrans dengan kunci yang sama?');">
                        @csrf
                        <button class="inline-flex min-h-[48px] w-full items-center justify-center gap-2 rounded-xl bg-bass-red px-5 font-semibold text-white shadow-sm transition hover:bg-bass-red-hover">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h5M20 20v-5h-5M5.5 17.5A8 8 0 0018 16m.5-9.5A8 8 0 006 8"/></svg>
                            {{ $refund->isApproved() ? 'Proses Refund' : 'Coba Proses Lagi' }}
                        </button>
                    </form>
                </div>
            @elseif ($refund->requiresManualProcessing())
                <form method="POST" action="{{ route('admin.refunds.complete-manual', $refund) }}" class="overflow-hidden rounded-2xl border border-orange-200 bg-white shadow-sm">
                    @csrf
                    <div class="border-b border-orange-100 bg-orange-50 px-5 py-4">
                        <div class="flex items-center gap-3">
                            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-white text-warning shadow-sm">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a5 5 0 00-10 0v2m-2 0h14l-1 12H6L5 9z"/></svg>
                            </span>
                            <div>
                                <h2 class="font-semibold text-gray-900">Refund Manual</h2>
                                <p class="text-xs text-gray-500">Dana dikembalikan di luar Midtrans</p>
                            </div>
                        </div>
                    </div>
                    <div class="p-5">
                        <p class="text-sm leading-6 text-gray-600">Transfer dana penuh kepada peserta, lalu simpan referensinya sebagai bukti.</p>
                        <label for="manual_reference" class="mt-4 block text-sm font-semibold text-gray-700">Referensi pengembalian</label>
                        <input id="manual_reference" name="reference" value="{{ old('reference') }}" required minlength="3" maxlength="255"
                               class="mt-2 w-full rounded-xl border-gray-300 text-sm focus:border-navy focus:ring-navy" placeholder="Nomor transfer / tiket bantuan">
                        <label for="manual_note" class="mt-4 block text-sm font-semibold text-gray-700">Catatan <span class="font-normal text-gray-400">(opsional)</span></label>
                        <textarea id="manual_note" name="note" rows="3" maxlength="1000" class="mt-2 w-full rounded-xl border-gray-300 text-sm focus:border-navy focus:ring-navy">{{ old('note') }}</textarea>
                        <button class="mt-5 inline-flex min-h-[48px] w-full items-center justify-center gap-2 rounded-xl bg-navy px-5 font-semibold text-white shadow-sm transition hover:bg-navy-light"
                                onclick="return confirm('Pastikan dana sudah benar-benar dikembalikan. Tandai refund selesai?');">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Tandai Dana Dikembalikan
                        </button>
                    </div>
                </form>
            @elseif ($refund->status === \App\Enums\RefundStatus::Processing)
                <div class="rounded-2xl border border-blue-200 bg-white p-5 shadow-sm">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-info-soft text-navy">
                        <svg class="h-5 w-5 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </span>
                    <h2 class="mt-4 font-semibold text-gray-900">Menunggu konfirmasi</h2>
                    <p class="mt-1.5 text-sm leading-6 text-gray-500">Midtrans sudah menerima permintaan. Periksa status terbaru jika konfirmasi belum masuk.</p>
                    <form method="POST" action="{{ route('admin.refunds.reconcile', $refund) }}" class="mt-5">
                        @csrf
                        <button class="inline-flex min-h-[46px] w-full items-center justify-center gap-2 rounded-xl border border-navy/25 bg-navy/5 px-5 text-sm font-semibold text-navy transition hover:bg-navy hover:text-white">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h5M20 20v-5h-5M5.5 17.5A8 8 0 0018 16m.5-9.5A8 8 0 006 8"/></svg>
                            Cek Status Midtrans
                        </button>
                    </form>
                </div>
            @else
                <div class="rounded-2xl border {{ $refund->isRefunded() ? 'border-success/25 bg-success-soft/40' : 'border-gray-200 bg-white' }} p-5 shadow-sm">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl {{ $refund->isRefunded() ? 'bg-success text-white' : 'bg-gray-100 text-gray-500' }}">
                        @if ($refund->isRefunded())
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        @else
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12"/></svg>
                        @endif
                    </span>
                    <h2 class="mt-4 font-semibold text-gray-900">{{ $refund->status_label }}</h2>
                    <p class="mt-1.5 text-sm leading-6 text-gray-500">
                        {{ $refund->isRefunded() ? 'Proses refund telah selesai dan akses pembelian sudah diperbarui.' : 'Pengajuan ini sudah ditutup dan tidak memerlukan tindakan lanjutan.' }}
                    </p>
                    @if ($refund->processed_at)
                        <p class="mt-4 border-t border-gray-200/70 pt-3 text-xs text-gray-500">Selesai {{ $refund->processed_at->translatedFormat('d F Y, H:i') }}</p>
                    @endif
                </div>
            @endif

            <div class="mt-4 rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-xs leading-5 text-gray-500">
                Akses kursus hanya dicabut setelah pengembalian dana dikonfirmasi selesai.
            </div>
        </aside>
    </div>
</div>
@endsection
