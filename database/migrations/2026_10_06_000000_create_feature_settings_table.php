<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feature_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('bundles_enabled')->default(true);
            $table->boolean('learning_paths_enabled')->default(true);
            $table->boolean('refund_requests_enabled')->default(true);
            $table->timestamps();
        });

        DB::table('feature_settings')->insert([
            'id' => 1,
            'bundles_enabled' => true,
            'learning_paths_enabled' => true,
            'refund_requests_enabled' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('feature_settings');
    }
};
