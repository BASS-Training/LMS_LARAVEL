<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('cancelled_by')->nullable()->after('rejection_reason')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable()->after('cancelled_by');
            $table->string('cancellation_reason')->nullable()->after('cancelled_at');
        });

        Schema::table('course_user', function (Blueprint $table) {
            $table->foreignId('order_id')->nullable()->after('user_id')
                ->constrained('orders')->nullOnDelete();
            $table->boolean('has_independent_access')->default(true)->after('order_id');
            $table->index('order_id');
        });

        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('amount');
            $table->text('reason');
            $table->text('admin_note')->nullable();
            $table->string('status', 24)->default('requested');
            $table->string('provider_refund_id')->nullable()->index();
            $table->uuid('idempotency_key')->unique();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->text('failure_message')->nullable();
            $table->timestamp('requested_at');
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->json('raw_response')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });

        // Enrollment dari pembayaran dibuat dalam transaksi yang sama dengan
        // paid_at. Hanya pasangan waktu yang dekat yang aman diatribusikan;
        // enrollment lain tetap dianggap memiliki sumber akses independen.
        DB::table('course_user')->orderBy('id')->eachById(function ($pivot) {
            if (! $pivot->created_at) {
                return;
            }

            $createdAt = Carbon::parse($pivot->created_at);
            $orderId = DB::table('orders')
                ->where('user_id', $pivot->user_id)
                ->where('course_id', $pivot->course_id)
                ->where('status', 'paid')
                ->whereNotNull('paid_at')
                ->whereBetween('paid_at', [$createdAt->copy()->subMinutes(5), $createdAt->copy()->addMinutes(5)])
                ->latest('id')
                ->value('id');

            if ($orderId) {
                DB::table('course_user')->where('id', $pivot->id)->update([
                    'order_id' => $orderId,
                    'has_independent_access' => false,
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refunds');

        Schema::table('course_user', function (Blueprint $table) {
            $table->dropForeign(['order_id']);
            $table->dropIndex(['order_id']);
            $table->dropColumn(['order_id', 'has_independent_access']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['cancelled_by']);
            $table->dropColumn(['cancelled_by', 'cancelled_at', 'cancellation_reason']);
        });
    }
};
