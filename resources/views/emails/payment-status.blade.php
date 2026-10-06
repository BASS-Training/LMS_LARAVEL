@php
    [$title, $message] = match ($event) {
        'paid' => ['Pembayaran lunas', 'Pembayaran berhasil dikonfirmasi. Akses kursus Anda sudah tersedia.'],
        'awaiting_verification' => ['Pembayaran diterima', 'Pembayaran sudah diterima dan sedang menunggu verifikasi admin. Akses kursus akan tersedia setelah disetujui.'],
        'approved' => ['Pembayaran disetujui', 'Verifikasi admin selesai. Akses kursus Anda sekarang tersedia.'],
        'rejected' => ['Pembayaran ditolak', 'Verifikasi admin tidak disetujui. Silakan lihat status pesanan untuk informasi terbaru.'],
    };
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
</head>
<body style="margin:0;padding:24px;background:#f3f4f6;font-family:Arial,Helvetica,sans-serif;color:#111827;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
        <tr><td align="center">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:520px;background:#fff;border-radius:16px;overflow:hidden;">
                <tr><td style="padding:24px;background:#DC0000;color:#fff;font-size:20px;font-weight:bold;text-align:center;">BASS Academy</td></tr>
                <tr><td style="padding:28px;">
                    <h1 style="margin:0 0 16px;font-size:22px;">{{ $title }}</h1>
                    <p style="line-height:1.6;">Halo {{ $name }},</p>
                    <p style="line-height:1.6;">{{ $message }}</p>
                    <table role="presentation" width="100%" cellpadding="6" cellspacing="0" style="margin:20px 0;background:#f9fafb;border-radius:8px;font-size:14px;">
                        <tr><td>Kode pesanan</td><td style="font-weight:bold;">{{ $orderCode }}</td></tr>
                        <tr><td>Produk</td><td style="font-weight:bold;">{{ $productTitle }}</td></tr>
                        <tr><td>Total pembayaran</td><td style="font-weight:bold;">{{ $amount }}</td></tr>
                    </table>
                    @if ($event === 'rejected' && $reason)
                        <p style="line-height:1.6;"><strong>Alasan penolakan:</strong> {{ $reason }}</p>
                    @endif
                    <p style="margin:24px 0 12px;"><a href="{{ $orderUrl }}" style="color:#DC0000;font-weight:bold;">Lihat status pesanan</a></p>
                    @if ($invoiceUrl)
                        <p style="margin:0 0 20px;"><a href="{{ $invoiceUrl }}" style="color:#DC0000;">Unduh invoice</a></p>
                    @endif
                    <p style="font-size:12px;color:#6b7280;line-height:1.6;">Tautan pesanan dan invoice memerlukan login ke akun pemilik pesanan.</p>
                </td></tr>
            </table>
        </td></tr>
    </table>
</body>
</html>
