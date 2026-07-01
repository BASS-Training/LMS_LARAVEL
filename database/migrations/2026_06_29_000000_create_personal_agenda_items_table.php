<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel ini MURNI TAMBAHAN. Menyimpan agenda pribadi yang dibuat sendiri
     * oleh peserta (sebelumnya hanya tersimpan lokal di perangkat via Hive),
     * sehingga kini ikut per-akun dan tersedia lintas device & web.
     */
    public function up(): void
    {
        Schema::create('personal_agenda_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('title');
            $table->text('note')->nullable();

            // Komponen tanggal acara yang dipakai mobile.
            $table->date('event_date');
            // Jam/menit opsional (agenda boleh tanpa waktu spesifik).
            $table->unsignedTinyInteger('hour')->nullable();
            $table->unsignedTinyInteger('minute')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'event_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_agenda_items');
    }
};
