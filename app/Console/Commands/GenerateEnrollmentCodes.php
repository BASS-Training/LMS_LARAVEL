<?php

namespace App\Console\Commands;

use App\Models\Course;
use App\Models\CourseClass;
use App\Services\EnrollmentCodeGenerator;
use Illuminate\Console\Command;

/**
 * Generate kode pendaftaran sekali-pakai dari terminal.
 *
 * Contoh:
 *   php artisan enrollment:generate-codes 12 --course=3
 *   php artisan enrollment:generate-codes 1 --class=5 --email=budi@mail.com
 *   php artisan enrollment:generate-codes 20 --course=3 --prefix=BASS --expires=2026-12-31
 */
class GenerateEnrollmentCodes extends Command
{
    protected $signature = 'enrollment:generate-codes
        {count=1 : Jumlah kode yang dibuat}
        {--course= : ID course tujuan}
        {--class= : ID course class (kelas) tujuan}
        {--email= : Bind ke email pembeli (opsional)}
        {--expires= : Tanggal kadaluarsa, mis. 2026-12-31 (opsional)}
        {--prefix= : Prefix kode, mis. BASS (opsional)}
        {--length=10 : Panjang bagian acak kode}';

    protected $description = 'Generate kode pendaftaran sekali-pakai untuk sebuah course atau kelas';

    public function handle(EnrollmentCodeGenerator $generator): int
    {
        $courseId = $this->option('course');
        $classId = $this->option('class');

        if (($courseId && $classId) || (! $courseId && ! $classId)) {
            $this->error('Isi tepat salah satu: --course=ID ATAU --class=ID.');

            return self::FAILURE;
        }

        // Validasi target ada.
        if ($courseId) {
            $course = Course::find($courseId);
            if (! $course) {
                $this->error("Course dengan ID {$courseId} tidak ditemukan.");

                return self::FAILURE;
            }
            $target = "Course #{$course->id}: {$course->title}";
        } else {
            $class = CourseClass::with('course')->find($classId);
            if (! $class) {
                $this->error("Kelas dengan ID {$classId} tidak ditemukan.");

                return self::FAILURE;
            }
            $target = "Kelas #{$class->id}: {$class->name} (course: ".($class->course->title ?? '-').')';
        }

        try {
            $codes = $generator->generate([
                'course_id' => $courseId ? (int) $courseId : null,
                'course_class_id' => $classId ? (int) $classId : null,
                'issued_to_email' => $this->option('email'),
                'expires_at' => $this->option('expires'),
                'prefix' => $this->option('prefix'),
                'length' => (int) $this->option('length'),
                'count' => (int) $this->argument('count'),
            ]);
        } catch (\Throwable $e) {
            $this->error('Gagal membuat kode: '.$e->getMessage());

            return self::FAILURE;
        }

        $email = $this->option('email');
        $this->info("Berhasil membuat {$codes->count()} kode untuk {$target}".
            ($email ? " (bind ke: {$email})" : ' (tanpa bind email)'));

        $this->table(
            ['#', 'Kode'],
            $codes->values()->map(fn ($c, $i) => [$i + 1, $c->code])->all()
        );

        return self::SUCCESS;
    }
}
