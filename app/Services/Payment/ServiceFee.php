<?php

namespace App\Services\Payment;

/**
 * Menghitung biaya layanan yang DIBEBANKAN KE PEMBELI untuk menutup ongkos
 * payment gateway (Midtrans).
 *
 * Kenapa tarif gabungan, bukan fee persis per metode?
 *  Snap tidak memberi tahu metode bayar di muka — pembeli baru memilih
 *  VA / QRIS / e-wallet / kartu SETELAH popup Snap terbuka. Jadi saat order
 *  dibuat kita belum tahu fee aslinya. Solusi lazim: satu tarif gabungan
 *  (persen + tetap) yang menutup mayoritas metode. Angkanya diatur di
 *  config/midtrans.php (lewat .env), tidak di-hardcode.
 *
 * Hasil hitungan DI-SNAPSHOT ke tabel orders (base_amount / fee_amount) saat
 * checkout, supaya invoice lama tetap akurat walau tarif diubah kemudian.
 */
class ServiceFee
{
    /**
     * Rincian biaya untuk sebuah harga dasar (harga kursus dari DB).
     *
     * @return array{base:int, fee:int, total:int}
     */
    public function forBase(int $base): array
    {
        if ($base <= 0 || ! config('midtrans.fee.enabled')) {
            return ['base' => $base, 'fee' => 0, 'total' => $base];
        }

        $percent = (float) config('midtrans.fee.percent', 0);
        $fixed   = (int) config('midtrans.fee.fixed', 0);
        $step    = max(1, (int) config('midtrans.fee.rounding', 1));

        $raw = ($base * $percent / 100) + $fixed;
        $fee = (int) (ceil($raw / $step) * $step);

        return ['base' => $base, 'fee' => $fee, 'total' => $base + $fee];
    }

    /** Label yang ditampilkan ke pengguna (mis. "Biaya layanan"). */
    public function label(): string
    {
        return (string) config('midtrans.fee.label', 'Biaya layanan');
    }
}
