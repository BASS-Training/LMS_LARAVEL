<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('learning_reminder_email_enabled')->default(true);
        });

        Schema::table('certificates', function (Blueprint $table) {
            $table->timestamp('issued_email_queued_at')->nullable();
        });

        // Existing certificates predate issuance email and must never trigger
        // historical messages when their PDF path is repaired later.
        DB::table('certificates')->update(['issued_email_queued_at' => now()]);

        Schema::create('learning_reminder_dispatches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('reminder_type', 8);
            $table->dateTime('occurrence_start');
            $table->timestamp('queued_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['content_id', 'user_id', 'reminder_type', 'occurrence_start'],
                'learning_reminder_dispatch_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_reminder_dispatches');

        Schema::table('certificates', function (Blueprint $table) {
            $table->dropColumn('issued_email_queued_at');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('learning_reminder_email_enabled');
        });
    }
};
