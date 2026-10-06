<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bundles', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('price');
            $table->boolean('is_active')->default(false)->index();
            $table->boolean('requires_payment_verification')->default(false);
            $table->timestamps();
        });

        Schema::create('bundle_course', function (Blueprint $table) {
            $table->foreignId('bundle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->unique(['bundle_id', 'course_id']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('bundle_id')->nullable()->after('course_id')->constrained()->nullOnDelete();
            $table->string('product_title')->nullable()->after('bundle_id');
            $table->boolean('requires_payment_verification')->default(false)->after('product_title');
            $table->unsignedBigInteger('bundle_discount_amount')->default(0)->after('discount_amount');
            $table->index(['bundle_id', 'status']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('course_id')->nullable()->change();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->nullable()->constrained()->nullOnDelete();
            $table->string('course_title');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['order_id', 'course_id']);
        });

        DB::table('orders')
            ->whereNotNull('course_id')
            ->orderBy('id')
            ->eachById(function ($order) {
                $course = DB::table('courses')->where('id', $order->course_id)->first();
                if (! $course) {
                    return;
                }

                DB::table('orders')->where('id', $order->id)->update([
                    'product_title' => $course->title,
                    'requires_payment_verification' => (bool) ($course->requires_payment_verification ?? false),
                ]);

                DB::table('order_items')->insert([
                    'order_id' => $order->id,
                    'course_id' => $course->id,
                    'course_title' => $course->title,
                    'sort_order' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');

        DB::table('orders')->whereNotNull('bundle_id')->delete();

        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['bundle_id']);
            $table->dropIndex(['bundle_id', 'status']);
            $table->dropColumn([
                'bundle_id',
                'product_title',
                'requires_payment_verification',
                'bundle_discount_amount',
            ]);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('course_id')->nullable(false)->change();
        });

        Schema::dropIfExists('bundle_course');
        Schema::dropIfExists('bundles');
    }
};
