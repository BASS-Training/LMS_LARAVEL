<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('refund_settings', function (Blueprint $table) {
            $table->string('policy_mode', 30)->default('company_issue');
        });

        Schema::table('orders', function (Blueprint $table) {
            // Existing orders remain on the legacy seven-day policy.
            $table->string('refund_policy_mode', 30)->nullable();
            $table->unsignedSmallInteger('refund_window_days')->nullable();
            $table->unsignedTinyInteger('refund_max_progress')->nullable();
            $table->timestamp('refund_policy_accepted_at')->nullable();
        });

        $legacySettings = DB::table('refund_settings')->first();
        DB::table('orders')->whereNull('refund_policy_mode')->update([
            'refund_policy_mode' => 'seven_day',
            'refund_window_days' => $legacySettings?->request_window_days ?? 7,
            'refund_max_progress' => $legacySettings?->max_progress_percentage ?? 30,
        ]);
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['refund_policy_mode', 'refund_window_days', 'refund_max_progress', 'refund_policy_accepted_at']);
        });
        Schema::table('refund_settings', function (Blueprint $table) {
            $table->dropColumn('policy_mode');
        });
    }
};
