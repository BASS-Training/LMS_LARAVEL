<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class Certificate extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'course_id',
        'certificate_template_id',
        'certificate_code',
        'path',
        'issued_at',
        // PENAMBAHAN: Kolom untuk data diri yang akan diisi peserta
        'place_of_birth',
        'date_of_birth',
        'identity_number',
        'institution_name',
        // New participant data fields
        'gender',
        'email',
        'occupation',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'issued_at' => 'datetime',
        'date_of_birth' => 'date', // PENAMBAHAN: Pastikan kolom tanggal lahir di-cast sebagai tanggal
    ];

    /**
     * Get the user that owns the certificate.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the course for this certificate.
     */
    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Get the template used for this certificate.
     */
    public function certificateTemplate()
    {
        return $this->belongsTo(CertificateTemplate::class);
    }

    /**
     * Alias for certificateTemplate for backward compatibility
     */
    public function template()
    {
        return $this->certificateTemplate();
    }

    /**
     * Get the full URL to the certificate PDF
     */
    public function getPdfUrlAttribute()
    {
        if ($this->path) {
            // Try multiple methods for URL generation
            try {
                // Method 1: Try Storage::url()
                return Storage::disk('public')->url($this->path);
            } catch (\Exception $e) {
                // Method 2: Use asset() as fallback
                return asset('storage/' . $this->path);
            }
        }
        return null;
    }

    /**
     * Get the verification URL for this certificate
     */
    public function getVerificationUrlAttribute()
    {
        return route('certificates.verify', $this->certificate_code);
    }

    /**
     * Check if the certificate file exists
     */
    public function fileExists()
    {
        if (!$this->path) {
            return false;
        }

        // Cek dengan berbagai metode
        if (\Storage::disk('public')->exists($this->path)) {
            return true;
        }

        // Cek dengan path absolut
        $absolutePath = storage_path('app/public/' . $this->path);
        if (file_exists($absolutePath)) {
            return true;
        }

        // Cek dengan fallback ke certificate_code
        $fallbackPath = 'certificates/' . $this->certificate_code . '.pdf';
        if (\Storage::disk('public')->exists($fallbackPath)) {
            // Update path di database
            $this->update(['path' => $fallbackPath]);
            return true;
        }

        return false;
    }

    /**
     * Generate a unique certificate code
     */
    public static function generateCertificateCode()
    {
        do {
            $code = 'CERT-' . strtoupper(\Illuminate\Support\Str::random(12));
        } while (self::where('certificate_code', $code)->exists());

        return $code;
    }

    /**
     * Get download URL for the certificate
     */
    public function getDownloadUrlAttribute()
    {
        return route('certificates.download', $this);
    }

    /**
     * Get public download URL (for verification page)
     */
    public function getPublicDownloadUrlAttribute()
    {
        return route('certificates.public-download', $this->certificate_code);
    }

    /**
     * Get the storage path for the certificate
     */
    public function getStoragePathAttribute()
    {
        return storage_path('app/public/' . $this->path);
    }

    /**
     * Resolve training date range from participant class enrollment.
     * Falls back to all classes in the course when user-class enrollment is unavailable.
     *
     * @return array{start: \Carbon\CarbonInterface, end: \Carbon\CarbonInterface}|null
     */
    public function getTrainingDateRange(): ?array
    {
        if (!$this->course) {
            return null;
        }

        // Opsi 1: single source of truth from course-level training schedule.
        if ($this->course->training_start_date && $this->course->training_end_date) {
            $startDate = $this->course->training_start_date instanceof Carbon
                ? $this->course->training_start_date
                : Carbon::parse($this->course->training_start_date);

            $endDate = $this->course->training_end_date instanceof Carbon
                ? $this->course->training_end_date
                : Carbon::parse($this->course->training_end_date);

            if ($endDate->lt($startDate)) {
                [$startDate, $endDate] = [$endDate, $startDate];
            }

            return [
                'start' => $startDate,
                'end' => $endDate,
            ];
        }

        // Backward compatibility fallback for historical certificates/courses.
        $baseQuery = $this->course->classes()
            ->whereNotNull('start_date')
            ->whereNotNull('end_date');

        $userClasses = (clone $baseQuery)
            ->whereHas('participants', function ($query) {
                $query->where('users.id', $this->user_id);
            })
            ->get(['start_date', 'end_date']);

        $classes = $userClasses->isNotEmpty()
            ? $userClasses->values()
            : $baseQuery->orderBy('start_date')->get(['start_date', 'end_date'])->values();

        if ($classes->isEmpty()) {
            return null;
        }

        $issuedAt = $this->issued_at
            ? ($this->issued_at instanceof Carbon ? $this->issued_at : Carbon::parse($this->issued_at))
            : now();

        $matchingClass = $classes
            ->filter(function ($class) use ($issuedAt) {
                $start = $class->start_date instanceof Carbon ? $class->start_date : Carbon::parse($class->start_date);
                $end = $class->end_date instanceof Carbon ? $class->end_date : Carbon::parse($class->end_date);
                return $issuedAt->between($start, $end, true);
            })
            ->sortByDesc('start_date')
            ->first();

        if (!$matchingClass) {
            $matchingClass = $classes
                ->filter(function ($class) use ($issuedAt) {
                    $end = $class->end_date instanceof Carbon ? $class->end_date : Carbon::parse($class->end_date);
                    return $end->lte($issuedAt);
                })
                ->sortByDesc('end_date')
                ->first();
        }

        if (!$matchingClass) {
            $matchingClass = $classes->sortBy('start_date')->first();
        }

        $start = $matchingClass->start_date;
        $end = $matchingClass->end_date;

        if (!$start || !$end) {
            return null;
        }

        $startDate = $start instanceof Carbon ? $start : Carbon::parse($start);
        $endDate = $end instanceof Carbon ? $end : Carbon::parse($end);

        if ($endDate->lt($startDate)) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        return [
            'start' => $startDate,
            'end' => $endDate,
        ];
    }

    public function getTrainingDateRangeLabel(): ?string
    {
        $range = $this->getTrainingDateRange();

        if (!$range) {
            return null;
        }

        return $this->formatDateIndonesian($range['start']) . ' - ' . $this->formatDateIndonesian($range['end']);
    }

    public function getTrainingStartDateLabel(): ?string
    {
        $range = $this->getTrainingDateRange();

        return $range ? $this->formatDateIndonesian($range['start']) : null;
    }

    public function getTrainingEndDateLabel(): ?string
    {
        $range = $this->getTrainingDateRange();

        return $range ? $this->formatDateIndonesian($range['end']) : null;
    }

    private function formatDateIndonesian(Carbon $date): string
    {
        $months = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];

        return $date->day . ' ' . $months[(int) $date->month] . ' ' . $date->year;
    }
}
