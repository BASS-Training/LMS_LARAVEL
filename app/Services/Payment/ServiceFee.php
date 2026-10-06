<?php

namespace App\Services\Payment;

/**
 * Menghitung biaya layanan yang DIBEBANKAN KE PEMBELI untuk menutup ongkos
 * payment gateway (Midtrans).
 *
 * Dua mode:
 *  1. PER METODE (disarankan) — pembeli memilih metode di halaman kita SEBELUM
 *     popup Snap dibuka, jadi biaya yang ditagih = biaya persis metode itu
 *     (transfer bank flat, QRIS 0,7%, kartu 2,9%+…). Transparan & adil: kursus
 *     mahal tidak ikut kena persen besar kalau bayar lewat VA. Konfigurasi ada
 *     di config/midtrans.php blok `methods`.
 *  2. TARIF GABUNGAN (fallback) — satu tarif persen+tetap untuk semua metode.
 *     Dipakai bila `methods.enabled=false` atau metode tak dikenali.
 *
 * Hasil hitungan DI-SNAPSHOT ke tabel orders (base_amount / fee_amount /
 * payment_method_key) saat checkout, supaya invoice lama tetap akurat walau
 * tarif diubah kemudian.
 */
class ServiceFee
{
    /**
     * Rincian biaya TARIF GABUNGAN untuk sebuah harga dasar.
     * Dipakai sebagai fallback & untuk estimasi "mulai dari".
     *
     * @return array{base:int, fee:int, total:int}
     */
    public function forBase(int $base): array
    {
        if ($base <= 0 || ! config('midtrans.fee.enabled')) {
            return ['base' => $base, 'fee' => 0, 'total' => $base];
        }

        $fee = $this->compute(
            $base,
            (float) config('midtrans.fee.percent', 0),
            (int) config('midtrans.fee.fixed', 0),
            (int) config('midtrans.fee.rounding', 1),
            null,
        );

        return ['base' => $base, 'fee' => $fee, 'total' => $base + $fee];
    }

    /**
     * Rincian biaya untuk sebuah METODE tertentu (mis. 'qris', 'bank_transfer').
     * Jika pemilihan per-metode dimatikan atau metode tak dikenal → fallback
     * ke tarif gabungan (forBase).
     *
     * @return array{base:int, fee:int, total:int, method:?string, label:string}
     */
    public function forMethod(int $base, ?string $methodKey): array
    {
        $method = $this->method($methodKey);

        if ($base <= 0 || ! $method) {
            $flat = $this->forBase($base);

            return $flat + ['method' => null, 'label' => $this->label()];
        }

        $fee = $this->compute(
            $base,
            (float) $method['percent'],
            (int) $method['fixed'],
            (int) config('midtrans.methods.rounding', 1),
            isset($method['cap']) ? ($method['cap'] === null ? null : (int) $method['cap']) : null,
        );

        return [
            'base'   => $base,
            'fee'    => $fee,
            'total'  => $base + $fee,
            'method' => $methodKey,
            'label'  => (string) $method['label'],
        ];
    }

    /**
     * Semua metode aktif + biaya masing-masing untuk harga dasar tertentu.
     * Dipakai halaman pilih metode. Diurutkan dari biaya termurah.
     *
     * @return list<array{key:string, label:string, description:string, fee:int, total:int}>
     */
    public function options(int $base): array
    {
        if (! $this->methodsEnabled()) {
            return [];
        }

        $out = [];

        foreach ((array) config('midtrans.methods.list', []) as $key => $method) {
            if (empty($method['enabled'])) {
                continue;
            }

            $detail = $this->forMethod($base, (string) $key);

            $out[] = [
                'key'         => (string) $key,
                'label'       => (string) ($method['label'] ?? $key),
                'description' => (string) ($method['description'] ?? ''),
                'fee'         => $detail['fee'],
                'total'       => $detail['total'],
            ];
        }

        usort($out, fn ($a, $b) => $a['fee'] <=> $b['fee']);

        return $out;
    }

    /**
     * Estimasi biaya TERMURAH di antara semua metode aktif (untuk teks
     * "biaya layanan mulai dari …" di kartu harga). Jatuh balik ke tarif
     * gabungan bila pemilihan per-metode tidak aktif.
     *
     * @return array{base:int, fee:int, total:int}
     */
    public function cheapest(int $base): array
    {
        $options = $this->options($base);

        if (empty($options)) {
            return $this->forBase($base);
        }

        $min = $options[0]; // sudah terurut termurah

        return ['base' => $base, 'fee' => $min['fee'], 'total' => $min['total']];
    }

    /**
     * Kode `enabled_payments` Snap untuk sebuah metode (mengunci popup Snap ke
     * metode itu saja). null bila metode tak dikenal → Snap tampilkan semua.
     *
     * @return list<string>|null
     */
    public function channelsFor(?string $methodKey): ?array
    {
        $method = $this->method($methodKey);

        if (! $method || empty($method['channels'])) {
            return null;
        }

        return array_values((array) $method['channels']);
    }

    /** True bila fitur pilih-metode-per-transaksi aktif & ada metode terdaftar. */
    public function methodsEnabled(): bool
    {
        return (bool) config('midtrans.methods.enabled', false)
            && ! empty(config('midtrans.methods.list', []));
    }

    /** Label default (mis. "Biaya layanan"). */
    public function label(): string
    {
        return (string) config('midtrans.fee.label', 'Biaya layanan');
    }

    /**
     * Ambil config satu metode AKTIF berdasarkan key, atau null.
     *
     * @return array<string, mixed>|null
     */
    private function method(?string $key): ?array
    {
        if ($key === null || $key === '' || ! $this->methodsEnabled()) {
            return null;
        }

        $method = config('midtrans.methods.list.' . $key);

        if (! is_array($method) || empty($method['enabled'])) {
            return null;
        }

        return $method;
    }

    /**
     * Rumus biaya: bulatkan NAIK( base*percent/100 + fixed ), lalu batasi 'cap'.
     */
    private function compute(int $base, float $percent, int $fixed, int $rounding, ?int $cap): int
    {
        $step = max(1, $rounding);
        $raw  = ($base * $percent / 100) + $fixed;
        $fee  = (int) (ceil($raw / $step) * $step);

        if ($cap !== null && $fee > $cap) {
            $fee = $cap;
        }

        return $fee;
    }
}
