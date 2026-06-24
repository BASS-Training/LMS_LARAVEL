<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Penanda untuk membedakan AKUN LAMA vs AKUN BARU soal verifikasi email.
 *
 *  - `email_verification_optional = true`  -> akun LAMA: verifikasi cuma SARAN,
 *    boleh di-skip, tidak pernah memblokir login (supaya tidak dipersulit).
 *  - `email_verification_optional = false` -> akun BARU: verifikasi WAJIB saat
 *    mendaftar sebelum bisa memakai aplikasi.
 *
 * Aman & non-breaking (filosofi sama seperti tabel enrollment_codes):
 *  - Hanya MENAMBAH satu kolom baru. Tidak mengubah/menghapus data apa pun.
 *  - Default kolom = false (akun baru wajib verifikasi).
 *  - Lalu SEMUA akun yang sudah ada ditandai true (jadi verifikasi opsional bagi
 *    mereka) -> akun lama dijamin tidak terkunci, tetap bisa login seperti biasa.
 *  - `email_verified_at` SENGAJA dibiarkan apa adanya supaya akun lama yang belum
 *    verified tetap menerima "nudge" untuk verifikasi/perbarui email.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('email_verification_optional')->default(false)->after('email_verified_at');
        });

        // Tandai semua akun yang SUDAH ADA sebagai "verifikasi opsional" (akun lama).
        DB::table('users')->update(['email_verification_optional' => true]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('email_verification_optional');
        });
    }
};
