<?php

namespace App\Models;

use App\Enums\RefundStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Refund extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'requested_by',
        'reviewed_by',
        'amount',
        'reason',
        'admin_note',
        'status',
        'provider_refund_id',
        'idempotency_key',
        'attempts',
        'failure_message',
        'requested_at',
        'reviewed_at',
        'processed_at',
        'failed_at',
        'raw_response',
    ];

    protected $casts = [
        'amount' => 'integer',
        'status' => RefundStatus::class,
        'attempts' => 'integer',
        'requested_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'processed_at' => 'datetime',
        'failed_at' => 'datetime',
        'raw_response' => 'array',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isRequested(): bool
    {
        return $this->status === RefundStatus::Requested;
    }

    public function isFailed(): bool
    {
        return $this->status === RefundStatus::Failed;
    }

    public function isApproved(): bool
    {
        return $this->status === RefundStatus::Approved;
    }

    public function requiresManualProcessing(): bool
    {
        return $this->status === RefundStatus::ManualRequired;
    }

    public function isRefunded(): bool
    {
        return $this->status === RefundStatus::Refunded;
    }

    public function getAmountLabelAttribute(): string
    {
        return 'Rp '.number_format($this->amount, 0, ',', '.');
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->status->label();
    }

    public function getStatusColorsAttribute(): array
    {
        return match ($this->status) {
            RefundStatus::Requested => ['bg-amber-100', 'text-amber-800'],
            RefundStatus::Approved, RefundStatus::Processing => ['bg-blue-100', 'text-blue-800'],
            RefundStatus::ManualRequired => ['bg-orange-100', 'text-orange-800'],
            RefundStatus::Refunded => ['bg-emerald-100', 'text-emerald-800'],
            RefundStatus::Rejected, RefundStatus::Failed => ['bg-red-100', 'text-red-800'],
        };
    }
}
