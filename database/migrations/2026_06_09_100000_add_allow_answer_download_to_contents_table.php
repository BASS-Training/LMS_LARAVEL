<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Flag untuk konten tipe 'case_study': menentukan apakah peserta boleh
     * mengunduh jawabannya menjadi PDF setelah dikumpulkan.
     */
    public function up(): void
    {
        Schema::table('contents', function (Blueprint $table) {
            $table->boolean('allow_answer_download')->default(false)->after('document_access_type');
        });
    }

    public function down(): void
    {
        Schema::table('contents', function (Blueprint $table) {
            $table->dropColumn('allow_answer_download');
        });
    }
};
