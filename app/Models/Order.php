<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_PAID = 'paid';

    public const STATUS_AWAITING_VERIFICATION = 'awaiting_verification';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_FAILED = 'failed';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_CANCELLATION_PENDING = 'cancellation_pending';

    public const STATUS_REFUNDED = 'refunded';

    protected $fillable = [
        'user_id',
        'course_id',
        'order_code',
        'invoice_number',
        'amount',
        'base_amount',
        'fee_amount',
        'coupon_code',
        'discount_amount',
        'status',
        'payment_type',
        'payment_method_key',
        'transaction_id',
        'snap_token',
        'snap_redirect_url',
        'paid_at',
        'payment_confirmed_at',
        'verified_by',
        'verified_at',
        'rejection_reason',
        'cancelled_by',
        'cancelled_at',
        'cancellation_reason',
        'expires_at',
        'raw_response',
    ];

    protected $casts = [
        'amount' => 'integer',
        'base_amount' => 'integer',
        'fee_amount' => 'integer',
        'discount_amount' => 'integer',
        'paid_at' => 'datetime',
        'payment_confirmed_at' => 'datetime',
        'verified_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'expires_at' => 'datetime',
        'raw_response' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    /** Super-admin yang menyetujui akses (mode verifikasi manual). */
    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function cancelledBy()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function refunds()
    {
        return $this->hasMany(Refund::class);
    }

    public function refund()
    {
        return $this->hasOne(Refund::class);
    }

    public function couponRedemption()
    {
        return $this->hasOne(CouponRedemption::class);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    public function isCancellationPending(): bool
    {
        return $this->status === self::STATUS_CANCELLATION_PENDING;
    }

    /** Uang sudah masuk, tapi menunggu persetujuan manusia sebelum akses dibuka. */
    public function isAwaitingVerification(): bool
    {
        return $this->status === self::STATUS_AWAITING_VERIFICATION;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function isRefunded(): bool
    {
        return $this->status === self::STATUS_REFUNDED;
    }

    /**
     * Uang sudah dikonfirmasi Midtrans (baik akses sudah dibuka maupun masih
     * menunggu verifikasi). Dipakai untuk memutuskan apakah invoice tersedia.
     */
    public function isPaymentConfirmed(): bool
    {
        return $this->payment_confirmed_at !== null;
    }

    /**
     * Masih bisa dilanjutkan bayarnya (link Snap-nya belum kedaluwarsa).
     */
    public function isPayable(): bool
    {
        return $this->isPending()
            && $this->snap_redirect_url
            && (! $this->expires_at || $this->expires_at->isFuture());
    }

    public function getAmountLabelAttribute(): string
    {
        return $this->rupiah($this->amount);
    }

    /** Harga kursus (pendapatan penjual) — jatuh balik ke total bila kosong. */
    public function getBaseAmountLabelAttribute(): string
    {
        return $this->rupiah($this->base_amount ?: $this->amount);
    }

    /** Biaya layanan yang dibebankan ke pembeli. */
    public function getFeeAmountLabelAttribute(): string
    {
        return $this->rupiah($this->fee_amount);
    }

    public function getDiscountAmountLabelAttribute(): string
    {
        return $this->rupiah($this->discount_amount);
    }

    public function getOriginalBaseAmountAttribute(): int
    {
        return (int) $this->base_amount + (int) $this->discount_amount;
    }

    public function getOriginalBaseAmountLabelAttribute(): string
    {
        return $this->rupiah($this->original_base_amount);
    }

    public function hasDiscount(): bool
    {
        return (int) $this->discount_amount > 0;
    }

    public function getOrderTitleAttribute(): string
    {
        return (string) ($this->course?->title ?: 'Pesanan');
    }

    /** Ada biaya layanan yang dirinci pada pesanan ini. */
    public function hasFee(): bool
    {
        return (int) $this->fee_amount > 0;
    }

    /**
     * Label metode pembayaran yang dipilih pembeli (mis. "QRIS",
     * "Transfer Bank (Virtual Account)"). Jatuh balik ke payment_type dari
     * Midtrans, lalu strip '—' bila keduanya kosong.
     */
    public function getPaymentMethodLabelAttribute(): string
    {
        if ($this->payment_method_key) {
            $label = config('midtrans.methods.list.'.$this->payment_method_key.'.label');

            if ($label) {
                return (string) $label;
            }
        }

        return $this->payment_type
            ? ucwords(str_replace('_', ' ', (string) $this->payment_type))
            : '—';
    }

    private function rupiah(int|string|null $value): string
    {
        return 'Rp '.number_format((int) $value, 0, ',', '.');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'paid' => 'Lunas & akses terbuka',
            'pending' => 'Menunggu pembayaran',
            'awaiting_verification' => 'Menunggu verifikasi',
            'rejected' => 'Ditolak',
            'failed' => 'Gagal',
            'expired' => 'Kedaluwarsa',
            'cancelled' => 'Dibatalkan',
            'cancellation_pending' => 'Pembatalan diproses',
            'refunded' => 'Dana dikembalikan',
            default => ucfirst($this->status),
        };
    }

    /**
     * Warna badge (Tailwind) per status — dipakai di riwayat & halaman status.
     * Mengembalikan [bg, text].
     *
     * @return array{0:string,1:string}
     */
    public function getStatusColorsAttribute(): array
    {
        return match ($this->status) {
            'paid' => ['bg-emerald-100', 'text-emerald-800'],
            'awaiting_verification' => ['bg-amber-100', 'text-amber-800'],
            'pending' => ['bg-blue-100', 'text-blue-800'],
            'rejected', 'failed' => ['bg-red-100', 'text-red-800'],
            'expired', 'cancelled' => ['bg-gray-100', 'text-gray-700'],
            'cancellation_pending' => ['bg-amber-100', 'text-amber-800'],
            'refunded' => ['bg-violet-100', 'text-violet-800'],
            default => ['bg-gray-100', 'text-gray-700'],
        };
    }
}
