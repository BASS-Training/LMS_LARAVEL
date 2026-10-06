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
    | Biaya layanan — TARIF GABUNGAN (fallback)
    |--------------------------------------------------------------------------
    | Dipakai HANYA jika pemilihan metode per-transaksi dimatikan
    | ('methods.enabled' = false), atau sebagai jaring pengaman kalau metode
    | yang dipilih tidak dikenali. Untuk penagihan transparan per metode, lihat
    | blok 'methods' di bawah.
    |
    | total = harga_kursus + biaya_layanan  → penjual menerima harga penuh.
    */
    'fee' => [
        'enabled'  => env('MIDTRANS_FEE_ENABLED', true),
        'percent'  => env('MIDTRANS_FEE_PERCENT', 2),      // 2 = 2%
        'fixed'    => env('MIDTRANS_FEE_FIXED', 2500),     // rupiah, ditambahkan datar
        'rounding' => env('MIDTRANS_FEE_ROUNDING', 100),   // bulatkan fee NAIK ke kelipatan ini
        'label'    => env('MIDTRANS_FEE_LABEL', 'Biaya layanan'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Biaya layanan PER METODE PEMBAYARAN (transparan)
    |--------------------------------------------------------------------------
    | Pembeli memilih metode di halaman kita SEBELUM popup Snap dibuka, sehingga
    | biaya yang ditampilkan = biaya persis untuk metode itu, lalu Snap dikunci
    | ke metode tersebut lewat parameter `enabled_payments`.
    |
    | biaya = bulatkan_naik( harga*percent/100 + fixed )  (opsional dibatasi 'cap')
    |
    | ⚠️  PENTING: angka 'percent'/'fixed' di bawah adalah TARIF UMUM Midtrans
    |     (MDR) sebagai default — WAJIB dicek & disesuaikan dengan tarif ASLI
    |     yang berlaku di akun kamu (dashboard Midtrans → Settings → mungkin
    |     berbeda per program/negosiasi). Ubah lewat file ini atau .env.
    |
    | 'channels' = daftar kode `enabled_payments` Snap yang tercakup metode ini.
    |     Kode valid a.l.: credit_card, bca_va, bni_va, bri_va, cimb_va,
    |     permata_va, other_va, echannel (Mandiri Bill), gopay, shopeepay, qris,
    |     alfamart, indomaret, akulaku, kredivo.
    */
    'methods' => [
        'enabled' => env('MIDTRANS_METHODS_ENABLED', true),

        // Pembulatan NAIK biaya ke kelipatan ini (rupiah) — berlaku semua metode.
        'rounding' => env('MIDTRANS_FEE_ROUNDING', 100),

        // Urutan di sini = urutan tampil di halaman pilih metode.
        'list' => [
            'qris' => [
                'enabled'     => true,
                'label'       => 'QRIS',
                'description' => 'Scan QR dari aplikasi apa pun',
                'percent'     => 0.7,   // MDR QRIS umum 0,7%
                'fixed'       => 0,
                'cap'         => null,  // batas biaya maksimum (rupiah), null = tanpa batas
                // Kode channel QRIS di Snap = 'other_qris' (di dashboard tampil
                // sbg "Other QRIS"). 'qris' disertakan sbg alias jaga-jaga; Snap
                // mengabaikan kode yg tak aktif. JANGAN masukkan 'gopay' di sini —
                // GoPay tarifnya beda (lihat metode 'ewallet'), bisa salah hitung.
                'channels'    => ['other_qris', 'qris'],
            ],
            'bank_transfer' => [
                'enabled'     => true,
                'label'       => 'Transfer Bank',
                'description' => 'BCA, BNI, BRI, Mandiri, Permata, CIMB',
                'percent'     => 0,
                'fixed'       => 4000,  // VA umumnya flat ±Rp4.000 per transaksi
                'cap'         => null,
                'channels'    => ['bca_va', 'bni_va', 'bri_va', 'permata_va', 'cimb_va', 'echannel', 'other_va'],
            ],
            'ewallet' => [
                'enabled'     => true,
                'label'       => 'E-Wallet',
                'description' => 'GoPay, ShopeePay',
                'percent'     => 2,     // e-wallet umum ±2%
                'fixed'       => 0,
                'cap'         => null,
                'channels'    => ['gopay', 'shopeepay'],
            ],
            'credit_card' => [
                'enabled'     => true,
                'label'       => 'Kartu Kredit / Debit',
                'description' => 'Visa, Mastercard, JCB',
                'percent'     => 2.9,   // kartu umum 2,9% + Rp2.000
                'fixed'       => 2000,
                'cap'         => null,
                'channels'    => ['credit_card'],
            ],
            // Contoh metode tambahan — aktifkan bila ingin menerima gerai ritel.
            'outlet' => [
                'enabled'     => false,
                'label'       => 'Gerai Retail',
                'description' => 'Bayar tunai di Alfamart / Indomaret',
                'percent'     => 0,
                'fixed'       => 5000,  // over-the-counter umumnya flat ±Rp5.000
                'cap'         => null,
                'channels'    => ['alfamart', 'indomaret'],
            ],
        ],
    ],
];
