<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambahkan konfigurasi "pengumpulan tugas dokumen" pada konten tipe
     * 'document'. Bersifat opsional & non-breaking: default OFF, sehingga
     * dokumen lama tetap murni materi baca (read-only) seperti sebelumnya.
     */
    public function up(): void
    {
        Schema::table('contents', function (Blueprint $table) {
            // Aktifkan area pengumpulan dokumen oleh peserta.
            $table->boolean('collect_submission')->default(false)->after('document_access_type');
            // Instruksi tugas yang tampil ke peserta (opsional).
            $table->text('submission_instructions')->nullable()->after('collect_submission');
            // Batas ukuran file (MB). Null = pakai default aplikasi (20 MB).
            $table->unsignedInteger('submission_max_size_mb')->nullable()->after('submission_instructions');
            // Ekstensi yang diizinkan, dipisah koma (mis. "pdf,doc,docx").
            // Null = pakai default aplikasi.
            $table->string('submission_allowed_types')->nullable()->after('submission_max_size_mb');
        });
    }

    public function down(): void
    {
        Schema::table('contents', function (Blueprint $table) {
            $table->dropColumn([
                'collect_submission',
                'submission_instructions',
                'submission_max_size_mb',
                'submission_allowed_types',
            ]);
        });
    }
};
