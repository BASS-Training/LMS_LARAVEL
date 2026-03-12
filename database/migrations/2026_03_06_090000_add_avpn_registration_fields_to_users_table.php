<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('registration_program', 20)
                ->default('regular')
                ->after('email')
                ->index();

            $table->string('avpn_verification_status', 20)
                ->default('not_required')
                ->after('registration_program')
                ->index();

            $table->timestamp('avpn_google_form_submitted_at')
                ->nullable()
                ->after('avpn_verification_status');

            $table->timestamp('avpn_verified_at')
                ->nullable()
                ->after('avpn_google_form_submitted_at');

            $table->foreignId('avpn_verified_by')
                ->nullable()
                ->after('avpn_verified_at')
                ->constrained('users')
                ->nullOnDelete();

            $table->text('avpn_rejection_reason')
                ->nullable()
                ->after('avpn_verified_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['avpn_verified_by']);
            $table->dropColumn([
                'registration_program',
                'avpn_verification_status',
                'avpn_google_form_submitted_at',
                'avpn_verified_at',
                'avpn_verified_by',
                'avpn_rejection_reason',
            ]);
        });
    }
};
