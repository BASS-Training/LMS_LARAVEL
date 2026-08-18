<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Pisahkan komponen harga pada pesanan:
 *   amount       = TOTAL yang dibayar pembeli (harga + biaya layanan)
 *   base_amount  = harga kursus (pendapatan penjual)
 *   fee_amount   = biaya layanan gateway yang dibebankan ke pembeli
 *
 * Untuk pesanan lama (dibuat sebelum fitur ini) belum ada biaya layanan:
 * base_amount = amount, fee_amount = 0 — sehingga invoice lama tetap konsisten.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedInteger('base_amount')->default(0)->after('amount');
            $table->unsignedInteger('fee_amount')->default(0)->after('base_amount');
        });

        // Backfill: samakan base_amount dengan amount yang sudah ada.
        DB::table('orders')->update(['base_amount' => DB::raw('amount')]);
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['base_amount', 'fee_amount']);
        });
    }
};
