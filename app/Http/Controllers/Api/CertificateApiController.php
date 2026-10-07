<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Sertifikat peserta untuk aplikasi mobile.
 *
 * Aturannya SAMA PERSIS dengan web (User::isEligibleForCertificate):
 *  1. Kursus punya template sertifikat (certificate_template_id).
 *  2. Peserta terdaftar di kursus.
 *  3. Progress kursus 100%.
 *  4. Seluruh item bernilai (essay) yang wajib sudah dinilai instruktur.
 *  5. Data profil peserta lengkap (tgl lahir, institusi, gender, pekerjaan)
 *     sebelum sertifikat boleh diterbitkan.
 *
 * Sertifikat yang dibuat adalah record + PDF NYATA dengan kode CERT unik —
 * identik dengan yang dibuat lewat web (CertificateController::create), jadi
 * bisa diunduh & diverifikasi publik lewat route yang sama.
 */
class CertificateApiController extends Controller
{
    /** Field profil wajib sebelum sertifikat bisa diterbitkan (label Indonesia). */
    private const REQUIRED_PROFILE_FIELDS = [
        'date_of_birth' => 'Tanggal lahir',
        'institution_name' => 'Institusi',
        'gender' => 'Jenis kelamin',
        'occupation' => 'Pekerjaan',
    ];

    /**
     * Ringkasan sertifikat peserta: yang sudah terbit + yang siap diterbitkan.
     * Meniru dashboard peserta web (partials/my-certificates.blade.php).
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $issued = Certificate::where('user_id', $user->id)
            ->with('course')
            ->latest('issued_at')
            ->get()
            ->map(fn (Certificate $c) => $this->issuedPayload($c))
            ->values();

        $eligible = $user->courses()
            ->whereNotNull('certificate_template_id')
            ->with(['certificateTemplate', 'lessons.contents'])
            ->get()
            ->filter(
                fn ($course) => $user->isEligibleForCertificate($course)
                    && ! $user->hasCertificateForCourse($course)
            )
            ->map(fn ($course) => [
                'courseId' => (string) $course->id,
                'courseTitle' => $course->title,
            ])
            ->values();

        $missing = $this->missingProfileFields($user);

        return response()->json([
            'status' => 'success',
            'data' => [
                'issued' => $issued,
                'eligible' => $eligible,
                'profileComplete' => empty($missing),
                'missingProfileFields' => array_values($missing),
            ],
        ]);
    }

    /**
     * Terbitkan sertifikat untuk sebuah kursus (self-service peserta).
     * Meniru CertificateController::create — aturan & data-diri sama persis.
     */
    public function generate(Request $request, Course $course): JsonResponse
    {
        $user = $request->user();

        // Sudah punya → idempoten, kembalikan yang ada.
        if ($user->hasCertificateForCourse($course)) {
            return response()->json([
                'status' => 'success',
                'data' => $this->issuedPayload(
                    $user->getCertificateForCourse($course)->load('course')
                ),
            ]);
        }

        if (! $user->isEligibleForCertificate($course)) {
            return response()->json([
                'status' => 'error',
                'code' => 'not_eligible',
                'message' => 'Anda belum memenuhi syarat untuk mendapatkan sertifikat di kursus ini.',
            ], 422);
        }

        $missing = $this->missingProfileFields($user);
        if (! empty($missing)) {
            return response()->json([
                'status' => 'error',
                'code' => 'profile_incomplete',
                'message' => 'Lengkapi data profil Anda terlebih dahulu untuk mendapatkan sertifikat.',
                'missingProfileFields' => array_values($missing),
            ], 422);
        }

        $certificate = $this->createCertificate($course, $user);

        if (! $certificate) {
            return response()->json([
                'status' => 'error',
                'code' => 'generation_failed',
                'message' => 'Terjadi kesalahan saat membuat sertifikat. Silakan coba lagi.',
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'data' => $this->issuedPayload($certificate->load('course')),
        ]);
    }

    /** Data-diri wajib yang masih kosong (map field => label). */
    private function missingProfileFields(User $user): array
    {
        $missing = [];
        foreach (self::REQUIRED_PROFILE_FIELDS as $field => $label) {
            if (blank($user->{$field})) {
                $missing[$field] = $label;
            }
        }

        return $missing;
    }

    /** Bentuk payload sertifikat terbit untuk mobile. */
    private function issuedPayload(Certificate $c): array
    {
        return [
            'id' => (string) $c->id,
            'certificateCode' => $c->certificate_code,
            'courseId' => (string) $c->course_id,
            'courseTitle' => optional($c->course)->title,
            'issuedAt' => optional($c->issued_at)?->toISOString(),
            // URL langsung ke file PDF di storage publik — dipakai mobile untuk
            // MERENDER sertifikat asli (template bergambar) secara inline,
            // persis seperti pratinjau di web. Null bila file belum ada.
            'pdfUrl' => $c->path ? $c->pdf_url : null,
            'downloadUrl' => route('certificates.public-download', $c->certificate_code),
            'verifyUrl' => route('certificates.verify', $c->certificate_code),
        ];
    }

    /**
     * Buat record + PDF sertifikat. Meniru CertificateController::generateCertificate
     * (self-service) termasuk menyimpan data-diri peserta ke record sertifikat.
     */
    private function createCertificate(Course $course, User $user): ?Certificate
    {
        $template = $course->certificateTemplate;
        if (! $template) {
            return null;
        }

        $certificate = null;

        try {
            $certificateCode = Certificate::generateCertificateCode();

            $certificate = Certificate::create([
                'user_id' => $user->id,
                'course_id' => $course->id,
                'certificate_template_id' => $template->id,
                'certificate_code' => $certificateCode,
                'issued_at' => now(),
                'date_of_birth' => $user->date_of_birth,
                'institution_name' => $user->institution_name,
                'gender' => $user->gender,
                'email' => $user->email,
                'occupation' => $user->occupation,
            ]);

            $pdf = Pdf::loadView('certificates.template-render', compact('certificate'))
                ->setPaper('a4', $certificate->certificateTemplate?->paperOrientation() ?? 'landscape')
                ->setOptions([
                    'dpi' => 150,
                    'defaultFont' => 'times',
                    'isHtml5ParserEnabled' => true,
                    'isRemoteEnabled' => true,
                    'allowedRemoteHosts' => config('certificate.pdf_remote_hosts'),
                    'isPhpEnabled' => true,
                ]);

            $certificatesDir = 'certificates';
            if (! Storage::disk('public')->exists($certificatesDir)) {
                Storage::disk('public')->makeDirectory($certificatesDir);
            }

            $filePath = $certificatesDir.'/'.$certificateCode.'.pdf';
            Storage::disk('public')->put($filePath, $pdf->output());
            $certificate->update(['path' => $filePath]);

            Log::info("Mobile certificate generated: user {$user->id}, course {$course->id}, file {$filePath}");

            return $certificate;
        } catch (\Exception $e) {
            Log::error("Mobile certificate generation failed for user {$user->id} course {$course->id}: ".$e->getMessage());

            // Bersihkan record bila render PDF gagal.
            if ($certificate) {
                $certificate->delete();
            }

            return null;
        }
    }
}
