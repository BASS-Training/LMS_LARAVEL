<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Jawaban peserta untuk konten tipe 'case_study'. Struktur jawaban
     * (per-section, per-block, per-cell tabel) disimpan sebagai JSON di kolom
     * `answers`, mengikuti id pada template yang tersimpan di contents.body.
     */
    public function up(): void
    {
        Schema::create('case_study_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('content_id')->constrained()->onDelete('cascade');
            $table->json('answers')->nullable();              // { sectionId: { blockId: html | {r_c: text} } }
            $table->string('status')->default('draft');        // draft | submitted | graded
            $table->unsignedInteger('score')->nullable();      // skor dari instruktur (overall)
            $table->text('feedback')->nullable();              // feedback dari instruktur
            $table->string('pdf_path')->nullable();            // cache PDF yang sudah digenerate
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('graded_at')->nullable();
            $table->foreignId('graded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'content_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('case_study_submissions');
    }
};
