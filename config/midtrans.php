<?php

return [
    /*
    | Kunci dari dashboard Midtrans (Settings → Access Keys).
    | JANGAN pernah commit nilainya — isi di .env.
    */
    'server_key' => env('MIDTRANS_SERVER_KEY'),
    'client_key' => env('MIDTRANS_CLIENT_KEY'),

    // false = sandbox (uji coba), true = produksi (uang sungguhan).
    'is_production' => env('MIDTRANS_IS_PRODUCTION', false),

    // Berapa lama link pembayaran berlaku.
    'expiry_hours' => env('MIDTRANS_EXPIRY_HOURS', 24),

    /*
    |--------------------------------------------------------------------------
    | Biaya layanan (dibebankan ke PEMBELI)
    |--------------------------------------------------------------------------
    | Menutup ongkos payment gateway. Snap tidak memberi tahu metode bayar di
    | muka (pembeli baru memilih setelah popup terbuka), jadi kita tidak bisa
    | membebankan fee persis-per-metode. Sebagai gantinya dipakai satu tarif
    | gabungan (persen + tetap) yang menutup mayoritas metode.
    |
    | total = harga_kursus + biaya_layanan  → penjual menerima harga penuh.
    |
    | Contoh (persen 2, fixed 2500, rounding 100) untuk harga Rp99.000:
    |   fee = ceil((99000*2/100) + 2500) → 4480 → dibulatkan naik ke 4500
    |   total dibayar pembeli = 103.500
    */
    'fee' => [
        'enabled'  => env('MIDTRANS_FEE_ENABLED', true),
        'percent'  => env('MIDTRANS_FEE_PERCENT', 2),      // 2 = 2%
        'fixed'    => env('MIDTRANS_FEE_FIXED', 2500),     // rupiah, ditambahkan datar
        'rounding' => env('MIDTRANS_FEE_ROUNDING', 100),   // bulatkan fee NAIK ke kelipatan ini
        'label'    => env('MIDTRANS_FEE_LABEL', 'Biaya layanan'),
    ],
];
