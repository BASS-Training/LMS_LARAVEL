<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_sales_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('slug')->unique();
            $table->string('headline')->nullable();
            $table->text('target_audience')->nullable();
            $table->text('learning_benefits')->nullable();
            $table->text('requirements')->nullable();
            $table->string('level', 30)->nullable();
            $table->unsignedInteger('estimated_duration_minutes')->nullable();
            $table->string('language', 100)->nullable();
            $table->string('promo_video_url', 2048)->nullable();
            $table->json('faq')->nullable();
            $table->string('seo_title')->nullable();
            $table->string('seo_description', 500)->nullable();
            $table->string('sales_status', 20)->default('draft')->index();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_sales_profiles');
    }
};
