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
];
