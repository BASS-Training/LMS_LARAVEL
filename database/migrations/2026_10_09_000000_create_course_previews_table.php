<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_previews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('content_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['course_id', 'content_id']);
            $table->index(['course_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_previews');
    }
};
