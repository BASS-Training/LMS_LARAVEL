<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pivot kursus yang disimpan (bookmark) per user. Fitur ini khusus aplikasi
 * mobile — web tidak memakai tabel ini. Disimpan di database agar koleksi
 * tetap bertahan walau user keluar aplikasi atau menghapus data aplikasi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saved_courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'course_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_courses');
    }
};
