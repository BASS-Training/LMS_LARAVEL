<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Simpan METODE PEMBAYARAN yang dipilih pembeli saat checkout
 * (mis. 'qris', 'bank_transfer', 'credit_card').
 *
 * Berbeda dari `payment_type` (yang diisi dari respons Midtrans setelah bayar):
 * kolom ini adalah metode yang DIPILIH DI AWAL, dasar penghitungan biaya
 * layanan per metode — jadi rincian & invoice tetap konsisten.
 *
 * Nullable: pesanan lama / mode tarif gabungan tidak punya nilai ini.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('payment_method_key', 50)->nullable()->after('payment_type');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('payment_method_key');
        });
    }
};
