<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel ini MURNI TAMBAHAN. Menyimpan baseline tier achievement yang sudah
     * "dirayakan" per peserta, agar perayaan tier tidak muncul ulang ketika
     * login di device baru. Badge-nya sendiri tetap diturunkan dari statistik
     * server; tabel ini hanya menyimpan state perayaan.
     */
    public function up(): void
    {
        Schema::create('user_achievement_tiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Id achievement dari definisi mobile (mis. 'pejuang', 'maraton').
            $table->string('achievement_id', 64);
            // Index tier tertinggi yang sudah dirayakan (0 = belum).
            $table->unsignedTinyInteger('tier')->default(0);

            $table->timestamps();

            $table->unique(['user_id', 'achievement_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_achievement_tiers');
    }
};
