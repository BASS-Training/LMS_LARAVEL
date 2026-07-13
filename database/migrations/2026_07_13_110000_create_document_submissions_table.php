<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pengumpulan tugas berupa DOKUMEN dari peserta untuk konten tipe 'document'
     * yang mengaktifkan pengumpulan (contents.collect_submission = true).
     *
     * Model "coba lagi otomatis": satu baris per attempt. Ketika sebuah attempt
     * dinilai 'failed' (belum lulus), peserta boleh membuat attempt baru
     * (attempt + 1). Attempt yang 'passed' mengunci pengumpulan selamanya.
     *
     * Status:
     *   draft     -> peserta masih menyiapkan (boleh upload/ganti/hapus file)
     *   submitted -> sudah dikumpulkan, terkunci, menunggu penilaian
     *   passed    -> dinilai LULUS (final)
     *   failed    -> dinilai BELUM LULUS (peserta boleh upload attempt berikutnya)
     */
    public function up(): void
    {
        Schema::create('document_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('content_id')->constrained()->onDelete('cascade');
            $table->unsignedInteger('attempt')->default(1);        // percobaan ke-berapa
            $table->string('file_path')->nullable();               // path file yang diupload peserta
            $table->string('original_name')->nullable();           // nama asli file
            $table->unsignedBigInteger('file_size')->nullable();   // ukuran (byte)
            $table->string('mime_type')->nullable();
            $table->string('status')->default('draft');            // draft|submitted|passed|failed
            $table->unsignedInteger('score')->nullable();          // nilai dari instruktur (opsional)
            $table->text('feedback')->nullable();                  // catatan/feedback instruktur
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('graded_at')->nullable();
            $table->foreignId('graded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // Satu baris unik per (peserta, konten, percobaan).
            $table->unique(['user_id', 'content_id', 'attempt']);
            $table->index(['content_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_submissions');
    }
};
