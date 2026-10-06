<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupon_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('checkout_enabled')->default(false);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        DB::table('coupon_settings')->insert([
            'id' => 1,
            'checkout_enabled' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('discount_type', 20);
            $table->unsignedBigInteger('discount_value');
            $table->unsignedBigInteger('minimum_amount')->nullable();
            $table->unsignedBigInteger('usage_limit')->nullable();
            $table->unsignedInteger('per_user_limit')->nullable()->default(1);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('applies_to_all_courses')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'starts_at', 'expires_at']);
        });

        Schema::create('coupon_course', function (Blueprint $table) {
            $table->foreignId('coupon_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->unique(['coupon_id', 'course_id']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('coupon_code', 50)->nullable()->after('fee_amount');
            $table->unsignedBigInteger('discount_amount')->default(0)->after('coupon_code');
            $table->index('coupon_code');
        });

        Schema::create('coupon_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_id')->constrained()->restrictOnDelete();
            $table->foreignId('order_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('discount_amount');
            $table->timestamp('redeemed_at');
            $table->timestamps();

            $table->unique(['coupon_id', 'order_id']);
            $table->index(['coupon_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupon_redemptions');

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['coupon_code']);
            $table->dropColumn(['coupon_code', 'discount_amount']);
        });

        Schema::dropIfExists('coupon_course');
        Schema::dropIfExists('coupons');
        Schema::dropIfExists('coupon_settings');
    }
};
