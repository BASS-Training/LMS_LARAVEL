<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pesanan pembelian kursus. Satu baris per percobaan bayar.
 *
 * `amount` adalah SNAPSHOT harga saat checkout — kalau admin mengubah harga
 * course besok, pesanan lama tetap mencatat harga yang benar-benar dibayar.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();

            // order_id yang dikirim ke Midtrans — wajib unik selamanya.
            $table->string('order_code')->unique();
            $table->unsignedBigInteger('amount');

            // pending | paid | failed | expired | cancelled
            $table->string('status', 20)->default('pending');

            $table->string('payment_type')->nullable();      // qris, gopay, bank_transfer, …
            $table->string('transaction_id')->nullable();    // ID transaksi dari Midtrans
            $table->string('snap_token')->nullable();
            $table->text('snap_redirect_url')->nullable();

            $table->timestamp('paid_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->json('raw_response')->nullable();        // payload terakhir dari Midtrans (audit)

            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['course_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
