<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Relative storage path of the user's profile photo (public disk),
            // e.g. "avatars/abc.jpg". Served to clients as asset('storage/'.$avatar).
            $table->string('avatar')->nullable()->after('occupation');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('avatar');
        });
    }
};
