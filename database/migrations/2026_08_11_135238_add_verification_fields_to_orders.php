<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dukungan verifikasi manual + invoice pada pesanan.
 *
 * - payment_confirmed_at : saat Midtrans memastikan UANG masuk (beda dari
 *   paid_at yang kini berarti "akses diberikan").
 * - verified_by/verified_at : jejak siapa (super-admin) yang menyetujui akses.
 * - rejection_reason : alasan bila pembayaran ditolak setelah ditinjau.
 * - invoice_number : nomor invoice unik, dibuat saat uang dikonfirmasi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('payment_confirmed_at')->nullable()->after('paid_at');
            $table->foreignId('verified_by')->nullable()->after('payment_confirmed_at')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable()->after('verified_by');
            $table->string('rejection_reason')->nullable()->after('verified_at');
            $table->string('invoice_number')->nullable()->unique()->after('order_code');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('verified_by');
            $table->dropColumn([
                'payment_confirmed_at',
                'verified_at',
                'rejection_reason',
                'invoice_number',
            ]);
        });
    }
};
