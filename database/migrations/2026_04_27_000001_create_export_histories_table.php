<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('export_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('filter', 20); // 'all' or 'class'
            $table->foreignId('course_class_id')->nullable()->constrained('course_classes')->nullOnDelete();
            $table->string('file_path')->nullable();
            $table->enum('status', ['processing', 'done', 'failed'])->default('processing');
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'course_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('export_histories');
    }
};
