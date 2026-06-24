@php
    $isReset = $purpose === 'password_reset';
    $title = $isReset ? 'Reset Password' : 'Verifikasi Email';
    $intro = $isReset
        ? 'Kami menerima permintaan untuk mereset password akun BASS Academy kamu. Gunakan kode di bawah ini untuk melanjutkan.'
        : 'Terima kasih sudah bergabung di BASS Academy. Masukkan kode di bawah ini di aplikasi untuk memverifikasi email kamu.';
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
</head>
<body style="margin:0; padding:0; background-color:#f3f4f6; font-family:Arial, Helvetica, sans-serif; color:#111827;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f3f4f6; padding:24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:480px; background-color:#ffffff; border-radius:16px; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,0.08);">
                    <tr>
                        <td style="background-color:#DC0000; padding:24px; text-align:center;">
                            <span style="color:#ffffff; font-size:20px; font-weight:bold; letter-spacing:0.5px;">BASS Academy</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px 28px 8px 28px;">
                            <h1 style="margin:0 0 12px 0; font-size:20px; color:#111827;">{{ $title }}</h1>
                            <p style="margin:0 0 4px 0; font-size:14px; line-height:1.6; color:#4b5563;">
                                Halo{{ $name ? ' ' . $name : '' }},
                            </p>
                            <p style="margin:8px 0 0 0; font-size:14px; line-height:1.6; color:#4b5563;">
                                {{ $intro }}
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:20px 28px;">
                            <div style="background-color:#fef2f2; border:1px dashed #DC0000; border-radius:12px; padding:18px; text-align:center;">
                                <span style="font-size:34px; font-weight:bold; letter-spacing:10px; color:#DC0000;">{{ $code }}</span>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0 28px 8px 28px;">
                            <p style="margin:0; font-size:13px; line-height:1.6; color:#6b7280;">
                                Kode ini berlaku selama <strong>{{ $expiresMinutes }} menit</strong>. Jangan bagikan kode ini kepada siapa pun.
                            </p>
                            <p style="margin:12px 0 0 0; font-size:13px; line-height:1.6; color:#6b7280;">
                                Jika kamu tidak meminta {{ $isReset ? 'reset password' : 'verifikasi' }} ini, abaikan saja email ini.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:24px 28px; border-top:1px solid #f3f4f6;">
                            <p style="margin:0; font-size:12px; color:#9ca3af;">Salam, Tim BASS Academy</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
