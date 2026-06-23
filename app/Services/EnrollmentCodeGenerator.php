<?php

namespace App\Services;

use App\Models\EnrollmentCode;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * Membuat kode pendaftaran sekali-pakai untuk sebuah course ATAU course_class.
 *
 * Memakai ulang TokenGenerator untuk bagian acak (charset tanpa karakter
 * ambigu seperti O/0/I/1) dan memastikan setiap kode unik di tabel
 * enrollment_codes. Dipakai oleh artisan command sekarang, dan UI admin nanti.
 */
class EnrollmentCodeGenerator
{
    /**
     * Generate sejumlah kode unik.
     *
     * @param  array{
     *   course_id?: int|null,
     *   course_class_id?: int|null,
     *   issued_to_email?: string|null,
     *   expires_at?: \DateTimeInterface|string|null,
     *   created_by?: int|null,
     *   count?: int,
     *   length?: int,
     *   prefix?: string|null,
     * }  $options
     * @return Collection<int, EnrollmentCode>
     */
    public function generate(array $options): Collection
    {
        $courseId = $options['course_id'] ?? null;
        $courseClassId = $options['course_class_id'] ?? null;

        // Tepat satu target: course ATAU kelas.
        if (($courseId && $courseClassId) || (! $courseId && ! $courseClassId)) {
            throw new InvalidArgumentException('Isi tepat salah satu: course_id ATAU course_class_id.');
        }

        $count = max(1, (int) ($options['count'] ?? 1));
        $length = max(6, (int) ($options['length'] ?? 10));
        $prefix = $options['prefix'] ?? null;

        $email = $options['issued_to_email'] ?? null;
        $email = $email !== null ? trim($email) : null;
        $email = $email === '' ? null : $email;

        $codes = collect();

        for ($i = 0; $i < $count; $i++) {
            $codes->push(EnrollmentCode::create([
                'code' => $this->uniqueCode($prefix, $length),
                'course_id' => $courseId,
                'course_class_id' => $courseClassId,
                'issued_to_email' => $email,
                'status' => EnrollmentCode::STATUS_AVAILABLE,
                'expires_at' => $options['expires_at'] ?? null,
                'created_by' => $options['created_by'] ?? null,
            ]));
        }

        return $codes;
    }

    /**
     * Hasilkan satu kode yang dijamin belum ada di tabel enrollment_codes.
     */
    private function uniqueCode(?string $prefix, int $length): string
    {
        $prefix = $prefix ? strtoupper(trim($prefix)) : null;
        $maxAttempts = 20;
        $attempts = 0;

        do {
            $random = TokenGenerator::generateRandom($length, 'alphanumeric');
            $code = $prefix ? "{$prefix}-{$random}" : $random;
            $attempts++;

            if ($attempts >= $maxAttempts) {
                throw new \RuntimeException("Gagal membuat kode unik setelah {$maxAttempts} percobaan.");
            }
        } while (EnrollmentCode::where('code', $code)->exists());

        return $code;
    }
}
