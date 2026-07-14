<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'course_id',
        'order_code',
        'amount',
        'status',
        'payment_type',
        'transaction_id',
        'snap_token',
        'snap_redirect_url',
        'paid_at',
        'expires_at',
        'raw_response',
    ];

    protected $casts = [
        'amount' => 'integer',
        'paid_at' => 'datetime',
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

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
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
        return 'Rp ' . number_format((int) $this->amount, 0, ',', '.');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'paid' => 'Lunas',
            'pending' => 'Menunggu pembayaran',
            'failed' => 'Gagal',
            'expired' => 'Kedaluwarsa',
            'cancelled' => 'Dibatalkan',
            default => ucfirst($this->status),
        };
    }
}
