<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_sales_profiles', function (Blueprint $table) {
            $table->string('sales_method', 20)->default('checkout')->after('sales_status');
        });
    }

    public function down(): void
    {
        Schema::table('course_sales_profiles', function (Blueprint $table) {
            $table->dropColumn('sales_method');
        });
    }
};
