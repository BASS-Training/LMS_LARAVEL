<?php

return [
    /* Emergency kill switch. Admin checkout toggle only works when this is true. */
    'coupons_feature_enabled' => env('COUPONS_FEATURE_ENABLED', false),

    /*
    | Apakah harga kursus berbayar boleh ditampilkan di APLIKASI MOBILE.
    |
    | Sengaja dibuat flag server (bukan hardcode di Flutter) supaya bisa
    | dinyalakan/dimatikan tanpa merilis ulang aplikasi ke Google Play —
    | menghemat satu siklus review kalau kebijakan berubah.
    |
    | Default FALSE selama production access Play masih direview. Mobile tidak
    | pernah menjual apa pun (tidak ada tombol beli / link ke pembayaran luar)
    | karena kebijakan anti-steering Google Play; menyembunyikan harga adalah
    | posisi paling aman. Web TIDAK terpengaruh flag ini.
    */
    'mobile_show_price' => env('MOBILE_CATALOG_SHOW_PRICE', false),
];
