<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Etalase kursus (katalog publik).
 *
 * `status` (draft|published) TIDAK diubah — itu tetap menandakan "sudah layak
 * dipakai atau belum". Keterlihatan di etalase dipisah ke sumbu sendiri supaya
 * satu course bisa sekaligus dijual dan dibagikan lewat token internal.
 *
 * Default `visibility = private` = perilaku lama persis: hanya bisa diakses
 * lewat token/kode enrollment, tidak muncul di katalog. Tidak ada satu pun
 * course lama yang tiba-tiba terekspos ke publik.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            // private = hanya lewat token/kode (perilaku lama)
            // catalog = tampil di etalase publik, bisa dilihat siapa pun
            $table->string('visibility', 20)->default('private')->after('status');

            // Harga dalam Rupiah penuh (tanpa sen). null / 0 = gratis.
            $table->unsignedBigInteger('price')->nullable()->after('visibility');

            // Ringkasan singkat untuk kartu di katalog (deskripsi utama HTML/panjang).
            $table->string('short_description')->nullable()->after('price');

            $table->index(['status', 'visibility']);
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropIndex(['status', 'visibility']);
            $table->dropColumn(['visibility', 'price', 'short_description']);
        });
    }
};
