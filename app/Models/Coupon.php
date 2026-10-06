<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    use HasFactory;

    public const TYPE_FIXED = 'fixed';

    public const TYPE_PERCENTAGE = 'percentage';

    protected $fillable = [
        'code',
        'discount_type',
        'discount_value',
        'minimum_amount',
        'usage_limit',
        'per_user_limit',
        'starts_at',
        'expires_at',
        'is_active',
        'applies_to_all_courses',
    ];

    protected $casts = [
        'discount_value' => 'integer',
        'minimum_amount' => 'integer',
        'usage_limit' => 'integer',
        'per_user_limit' => 'integer',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
        'applies_to_all_courses' => 'boolean',
    ];

    public function setCodeAttribute(?string $value): void
    {
        $this->attributes['code'] = mb_strtoupper(trim((string) $value));
    }

    public function courses()
    {
        return $this->belongsToMany(Course::class, 'coupon_course');
    }

    public function redemptions()
    {
        return $this->hasMany(CouponRedemption::class);
    }

    public function getDiscountLabelAttribute(): string
    {
        return $this->discount_type === self::TYPE_PERCENTAGE
            ? $this->discount_value.'%'
            : 'Rp '.number_format($this->discount_value, 0, ',', '.');
    }
}
