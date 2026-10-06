<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('snap_status', 20)->nullable()->index();
            $table->text('snap_error')->nullable();
        });

        Schema::create('payment_webhook_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('payload_hash', 64)->unique();
            $table->json('payload');
            $table->timestamp('processed_at')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_webhook_receipts');
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['snap_status', 'snap_error']);
        });
    }
};
