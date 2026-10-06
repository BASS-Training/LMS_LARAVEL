<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $order->invoice_number }}</title>
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { margin: 0; color: #1f2937; font-size: 12px; }
        .wrap { padding: 36px 40px; }
        .row { width: 100%; }
        .row:after { content: ""; display: table; clear: both; }
        .col-left { float: left; width: 50%; }
        .col-right { float: right; width: 45%; text-align: right; }
        .brand { font-size: 20px; font-weight: bold; color: #dc0000; }
        .muted { color: #6b7280; }
        .title { font-size: 26px; font-weight: bold; letter-spacing: 1px; color: #111827; }
        .pill { display: inline-block; padding: 4px 12px; border-radius: 999px; font-size: 11px; font-weight: bold; }
        .pill-paid { background: #d1fae5; color: #065f46; }
        .pill-wait { background: #fef3c7; color: #92400e; }
        .pill-other { background: #f3f4f6; color: #374151; }
        .sep { border: none; border-top: 2px solid #dc0000; margin: 20px 0; }
        .box { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 14px 15px; }
        .label { font-size: 10px; text-transform: uppercase; letter-spacing: .5px; color: #9ca3af; margin-bottom: 3px; }
        table.items { width: 100%; border-collapse: collapse; margin-top: 22px; }
        table.items th { background: #111827; color: #fff; text-align: left; padding: 10px 12px; font-size: 11px; }
        table.items th.num, table.items td.num { text-align: right; }
        table.items td { padding: 12px; border-bottom: 1px solid #e5e7eb; }
        .totals { width: 45%; float: right; margin-top: 14px; }
        .totals td { padding: 6px 12px; }
        .totals .grand td { border-top: 2px solid #111827; font-size: 15px; font-weight: bold; }
        .foot { margin-top: 60px; font-size: 10px; color: #9ca3af; text-align: center; }
        .note { margin-top: 14px; font-size: 11px; color: #6b7280; }
    </style>
</head>
<body>
@php
    $pillClass = match ($order->status) {
        'paid' => 'pill-paid',
        'awaiting_verification' => 'pill-wait',
        default => 'pill-other',
    };
@endphp
<div class="wrap">

    <div class="row">
        <div class="col-left">
            <div class="brand">BASS Training</div>
            <div class="muted">Bintang Anugrah Surya Semesta</div>
            <div class="muted">Learning Management System</div>
        </div>
        <div class="col-right">
            <div class="title">INVOICE</div>
            <div class="muted">{{ $order->invoice_number }}</div>
            <div style="margin-top:8px">
                <span class="pill {{ $pillClass }}">{{ $order->status_label }}</span>
            </div>
        </div>
    </div>

    <hr class="sep">

    <div class="row">
        <div class="col-left">
            <div class="box">
                <div class="label">Ditagihkan kepada</div>
                <div style="font-weight:bold; font-size:13px;">{{ $order->user->name }}</div>
                <div class="muted">{{ $order->user->email }}</div>
            </div>
        </div>
        <div class="col-right">
            <table style="width:100%; font-size:12px;">
                <tr>
                    <td class="muted" style="text-align:left;">Tanggal pembayaran</td>
                    <td style="text-align:right; font-weight:bold;">
                        {{ optional($order->payment_confirmed_at)->format('d M Y, H:i') }}
                    </td>
                </tr>
                <tr>
                    <td class="muted" style="text-align:left;">Kode pesanan</td>
                    <td style="text-align:right;">{{ $order->order_code }}</td>
                </tr>
                <tr>
                    <td class="muted" style="text-align:left;">Metode pembayaran</td>
                    <td style="text-align:right; text-transform:capitalize;">
                        {{ $order->payment_method_label }}
                    </td>
                </tr>
                @if ($order->transaction_id)
                <tr>
                    <td class="muted" style="text-align:left;">ID transaksi</td>
                    <td style="text-align:right; font-size:10px;">{{ $order->transaction_id }}</td>
                </tr>
                @endif
            </table>
        </div>
    </div>

    <table class="items">
        <thead>
            <tr>
                <th>Deskripsi</th>
                <th class="num">Jumlah</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <div style="font-weight:bold;">{{ $order->order_title }}</div>
                    <div class="muted" style="font-size:11px;">{{ $order->isBundleOrder() ? 'Akses paket kursus' : 'Akses kursus' }} — selamanya</div>
                    @if ($order->isBundleOrder())
                        <div class="muted" style="font-size:10px; margin-top:4px;">Termasuk: {{ $order->items->pluck('course_title')->join(', ') }}</div>
                    @endif
                </td>
                <td class="num">{{ $order->original_base_amount_label }}</td>
            </tr>
            @if ($order->hasDiscount())
            <tr>
                <td>
                    <div style="font-weight:bold;">Kupon {{ $order->coupon_code }}</div>
                    <div class="muted" style="font-size:11px;">Potongan harga</div>
                </td>
                <td class="num">-{{ $order->discount_amount_label }}</td>
            </tr>
            @endif
            @if ($order->hasBundleDiscount())
            <tr>
                <td><div style="font-weight:bold;">Potongan kursus dimiliki</div></td>
                <td class="num">-{{ $order->bundle_discount_amount_label }}</td>
            </tr>
            @endif
            @if ($order->hasFee())
            <tr>
                <td>
                    <div style="font-weight:bold;">{{ config('midtrans.fee.label', 'Biaya layanan') }}</div>
                    <div class="muted" style="font-size:11px;">Biaya pemrosesan pembayaran</div>
                </td>
                <td class="num">{{ $order->fee_amount_label }}</td>
            </tr>
            @endif
        </tbody>
    </table>

    <table class="totals">
        <tr>
            <td class="muted">Subtotal</td>
            <td style="text-align:right;">{{ $order->original_base_amount_label }}</td>
        </tr>
        @if ($order->hasDiscount())
        <tr>
            <td class="muted">Diskon ({{ $order->coupon_code }})</td>
            <td style="text-align:right;">-{{ $order->discount_amount_label }}</td>
        </tr>
        @endif
        @if ($order->hasBundleDiscount())
        <tr>
            <td class="muted">Potongan kepemilikan</td>
            <td style="text-align:right;">-{{ $order->bundle_discount_amount_label }}</td>
        </tr>
        @endif
        @if ($order->hasFee())
        <tr>
            <td class="muted">{{ config('midtrans.fee.label', 'Biaya layanan') }}</td>
            <td style="text-align:right;">{{ $order->fee_amount_label }}</td>
        </tr>
        @endif
        <tr class="grand">
            <td>Total</td>
            <td style="text-align:right;">{{ $order->amount_label }}</td>
        </tr>
    </table>

    <div style="clear:both;"></div>

    @if ($order->isAwaitingVerification())
        <div class="note">
            * Pembayaran Anda sudah kami terima dan sedang <strong>menunggu verifikasi</strong>.
            Akses kursus akan otomatis terbuka setelah tim kami memvalidasi pembayaran ini.
        </div>
    @elseif ($order->isPaid())
        <div class="note">* Terima kasih. Pembayaran lunas dan akses kursus sudah aktif.</div>
    @endif

    <div class="foot">
        Invoice ini dibuat otomatis oleh sistem BASS Training dan sah tanpa tanda tangan.<br>
        Dokumen dibuat pada {{ now()->format('d M Y, H:i') }} WIB.
    </div>
</div>
</body>
</html>
