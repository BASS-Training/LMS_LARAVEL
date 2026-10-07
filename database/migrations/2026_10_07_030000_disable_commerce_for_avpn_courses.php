<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $avpnCourseIds = DB::table('courses')
                ->where('program_type', 'avpn_ai')
                ->pluck('id');

            if ($avpnCourseIds->isEmpty()) {
                return;
            }

            DB::table('courses')->whereIn('id', $avpnCourseIds)->update([
                'visibility' => 'private',
                'price' => null,
                'short_description' => null,
                'requires_payment_verification' => false,
                'updated_at' => now(),
            ]);
            DB::table('course_sales_profiles')->whereIn('course_id', $avpnCourseIds)->update([
                'sales_status' => 'hidden',
                'updated_at' => now(),
            ]);
            DB::table('coupon_course')->whereIn('course_id', $avpnCourseIds)->delete();
            DB::table('bundle_course')->whereIn('course_id', $avpnCourseIds)->delete();
            DB::table('course_learning_path')->whereIn('course_id', $avpnCourseIds)->delete();

            $validBundleIds = DB::table('bundle_course')
                ->select('bundle_id')
                ->groupBy('bundle_id')
                ->havingRaw('COUNT(*) >= 2');
            DB::table('bundles')->whereNotIn('id', $validBundleIds)->update([
                'is_active' => false,
                'updated_at' => now(),
            ]);

            $validPathIds = DB::table('course_learning_path')
                ->select('learning_path_id')
                ->groupBy('learning_path_id')
                ->havingRaw('COUNT(*) >= 2');
            DB::table('learning_paths')->whereNotIn('id', $validPathIds)->update([
                'is_active' => false,
                'updated_at' => now(),
            ]);
        });
    }

    public function down(): void
    {
        // Commerce AVPN tidak dipulihkan otomatis agar tidak membuka penjualan tanpa audit admin.
    }
};
