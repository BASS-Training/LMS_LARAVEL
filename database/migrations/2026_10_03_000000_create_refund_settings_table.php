<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('refund_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('request_window_days')->default(7);
            $table->unsignedTinyInteger('max_progress_percentage')->default(30);
            $table->timestamps();
        });

        DB::table('refund_settings')->insert([
            'id' => 1,
            'request_window_days' => 7,
            'max_progress_percentage' => 30,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('refund_settings');
    }
};
