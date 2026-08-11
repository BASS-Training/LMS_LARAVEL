<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kolom status semula varchar(20) — tidak muat untuk 'awaiting_verification'
 * (21 karakter). Lebarkan agar status verifikasi manual bisa disimpan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('status', 32)->default('pending')->change();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('status', 20)->default('pending')->change();
        });
    }
};
