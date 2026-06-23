<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Kode pendaftaran pribadi yang sekali-pakai (1 kode = 1 peserta).
 *
 * Alternatif aman dari `enrollment_token` bersama di Course/CourseClass:
 * - sekali-pakai  : setelah diredeem, status menjadi `redeemed` dan tidak bisa lagi.
 * - bind email    : (opsional) hanya `issued_to_email` yang boleh meredeem.
 *
 * Sistem ini ADITIF — tidak menggantikan token lama. Course/kelas boleh memakai
 * token lama, kode baru, atau keduanya.
 */
class EnrollmentCode extends Model
{
    use HasFactory;

    public const STATUS_AVAILABLE = 'available';
    public const STATUS_REDEEMED = 'redeemed';
    public const STATUS_REVOKED = 'revoked';

    protected $fillable = [
        'code',
        'course_id',
        'course_class_id',
        'issued_to_email',
        'status',
        'redeemed_by',
        'redeemed_at',
        'expires_at',
        'created_by',
    ];

    protected $casts = [
        'redeemed_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    // ========================================
    // RELATIONS
    // ========================================

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function courseClass()
    {
        return $this->belongsTo(CourseClass::class);
    }

    public function redeemer()
    {
        return $this->belongsTo(User::class, 'redeemed_by');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ========================================
    // STATUS HELPERS
    // ========================================

    public function isAvailable(): bool
    {
        return $this->status === self::STATUS_AVAILABLE;
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /**
     * Cek apakah kode boleh diredeem oleh $user.
     *
     * @return string|null  null jika boleh; pesan error (Bahasa Indonesia) jika tidak.
     */
    public function redeemErrorFor(User $user): ?string
    {
        if ($this->status === self::STATUS_REVOKED) {
            return 'Kode ini sudah dibatalkan.';
        }

        if ($this->status === self::STATUS_REDEEMED) {
            return 'Kode ini sudah pernah digunakan.';
        }

        if (!$this->isAvailable()) {
            return 'Kode tidak dapat digunakan.';
        }

        if ($this->isExpired()) {
            return 'Kode sudah kadaluarsa.';
        }

        // Bind email (opsional): bandingkan tanpa peduli huruf besar/kecil.
        if ($this->issued_to_email !== null
            && strcasecmp($this->issued_to_email, (string) $user->email) !== 0) {
            return 'Kode ini terdaftar untuk email lain.';
        }

        return null;
    }

    /**
     * Tandai kode sebagai sudah dipakai oleh $user.
     * Pemanggil bertanggung jawab membungkus dalam transaksi + lockForUpdate.
     */
    public function markRedeemedBy(User $user): void
    {
        $this->status = self::STATUS_REDEEMED;
        $this->redeemed_by = $user->id;
        $this->redeemed_at = now();
        $this->save();
    }

    // ========================================
    // SCOPES
    // ========================================

    public function scopeAvailable($query)
    {
        return $query->where('status', self::STATUS_AVAILABLE);
    }
}
