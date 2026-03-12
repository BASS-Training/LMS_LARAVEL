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
        Schema::table('courses', function (Blueprint $table) {
            $table->string('program_type', 20)
                ->default('regular')
                ->after('token_type')
                ->index();
        });

        Schema::table('course_classes', function (Blueprint $table) {
            $table->string('program_type', 20)
                ->default('regular')
                ->after('token_type')
                ->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_classes', function (Blueprint $table) {
            $table->dropColumn('program_type');
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn('program_type');
        });
    }
};
