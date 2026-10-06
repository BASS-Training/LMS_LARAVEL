<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Toggle per-course: apakah pembelian course ini harus DIVERIFIKASI manusia
 * dulu sebelum peserta dapat akses (false = akses otomatis setelah lunas).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->boolean('requires_payment_verification')
                ->default(false)
                ->after('price');
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn('requires_payment_verification');
        });
    }
};
