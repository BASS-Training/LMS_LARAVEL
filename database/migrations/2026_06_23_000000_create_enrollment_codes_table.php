<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Tabel ini MURNI TAMBAHAN — tidak mengubah tabel/kolom yang sudah ada.
     * Menyimpan kode pendaftaran pribadi yang sekali-pakai (1 kode = 1 peserta),
     * sebagai alternatif dari sistem `enrollment_token` bersama yang lama.
     * Sebuah course/kelas boleh tetap memakai token lama, memakai kode baru,
     * atau keduanya — sepenuhnya fleksibel.
     */
    public function up(): void
    {
        Schema::create('enrollment_codes', function (Blueprint $table) {
            $table->id();

            // Kode unik yang diredeem peserta.
            $table->string('code', 32)->unique();

            // Kode memberi akses ke SALAH SATU: course atau course_class.
            // nullOnDelete agar penghapusan course/kelas tidak merusak tabel ini.
            $table->foreignId('course_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('course_class_id')->nullable()->constrained()->nullOnDelete();

            // Bind OPSIONAL ke email pembeli. Jika diisi, hanya user dengan
            // email ini yang boleh meredeem (mencegah kode dicuri sebelum dipakai).
            $table->string('issued_to_email')->nullable();

            // available = belum dipakai, redeemed = sudah dipakai, revoked = dibatalkan admin.
            $table->string('status', 20)->default('available')->index();

            // Siapa yang memakai + kapan (jejak audit + bukti sekali-pakai).
            $table->foreignId('redeemed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('redeemed_at')->nullable();

            // Kedaluwarsa opsional.
            $table->timestamp('expires_at')->nullable();

            // Admin pembuat (audit).
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            // Indeks untuk query pencarian kode per course/kelas + status.
            $table->index(['course_id', 'status']);
            $table->index(['course_class_id', 'status']);
            $table->index('issued_to_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enrollment_codes');
    }
};
