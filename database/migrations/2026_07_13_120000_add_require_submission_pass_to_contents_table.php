<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bila true (dan collect_submission aktif), peserta baru dianggap
     * menyelesaikan konten dokumen ini setelah pengumpulannya dinilai LULUS.
     * Sebelum lulus, konten berikutnya terkunci — mirip attendance_required.
     * Default false = perilaku lama (boleh lanjut setelah mengumpulkan).
     */
    public function up(): void
    {
        Schema::table('contents', function (Blueprint $table) {
            $table->boolean('require_submission_pass')->default(false)->after('submission_allowed_types');
        });
    }

    public function down(): void
    {
        Schema::table('contents', function (Blueprint $table) {
            $table->dropColumn('require_submission_pass');
        });
    }
};
