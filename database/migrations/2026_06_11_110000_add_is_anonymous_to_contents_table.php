<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Flag untuk konten tipe 'feedback': bila true, instruktur hanya melihat
     * jawaban secara agregat (anonim), tidak tahu siapa menjawab apa.
     */
    public function up(): void
    {
        Schema::table('contents', function (Blueprint $table) {
            $table->boolean('is_anonymous')->default(false)->after('allow_answer_download');
        });
    }

    public function down(): void
    {
        Schema::table('contents', function (Blueprint $table) {
            $table->dropColumn('is_anonymous');
        });
    }
};
