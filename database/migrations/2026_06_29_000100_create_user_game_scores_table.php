<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel ini MURNI TAMBAHAN. Menyimpan best score mini-game per peserta
     * (sebelumnya hanya lokal di perangkat), agar skor mengikuti akun lintas
     * device. Board/resume mid-game sengaja TETAP lokal (state transien).
     */
    public function up(): void
    {
        Schema::create('user_game_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Identitas game dari registry mobile (mis. '2048', 'schulte').
            $table->string('game_id', 64);

            $table->unsignedInteger('best_score')->default(0);
            $table->unsignedInteger('plays')->default(0);
            $table->timestamp('last_played_at')->nullable();

            $table->timestamps();

            // Satu baris best-score per (user, game).
            $table->unique(['user_id', 'game_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_game_scores');
    }
};
