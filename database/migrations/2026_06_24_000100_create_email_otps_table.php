<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kode OTP 6-digit untuk verifikasi email & reset password (mobile-friendly,
 * tanpa deep-link). Tabel baru, additif, tidak menyentuh tabel lain.
 *
 *  - `code` disimpan dalam bentuk HASH (tidak pernah plaintext).
 *  - `purpose` membedakan kegunaan: 'email_verification' | 'password_reset'.
 *  - `expires_at` masa berlaku singkat; `attempts` untuk membatasi tebakan.
 *  - Satu OTP aktif per (email + purpose): generate baru menimpa yang lama.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_otps', function (Blueprint $table) {
            $table->id();
            $table->string('email')->index();
            $table->string('purpose', 32)->default('email_verification');
            $table->string('code'); // hashed
            $table->unsignedTinyInteger('attempts')->default(0);
            // dateTime (bukan timestamp) supaya MySQL TIDAK menambahkan
            // perilaku implisit ON UPDATE CURRENT_TIMESTAMP yang akan
            // mereset expires_at setiap kali baris di-update.
            $table->dateTime('expires_at');
            $table->dateTime('consumed_at')->nullable();
            $table->timestamps();

            $table->index(['email', 'purpose']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_otps');
    }
};
