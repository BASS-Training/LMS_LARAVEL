<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bundle extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'description',
        'price',
        'is_active',
        'requires_payment_verification',
    ];

    protected $casts = [
        'price' => 'integer',
        'is_active' => 'boolean',
        'requires_payment_verification' => 'boolean',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function courses()
    {
        return $this->belongsToMany(Course::class)
            ->withPivot('sort_order')
            ->orderByPivot('sort_order');
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function scopeInCatalog(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->whereHas('courses')
            ->whereDoesntHave('courses', fn (Builder $query) => $query->where(function (Builder $query) {
                $query->where('status', '!=', 'published')
                    ->orWhere('visibility', '!=', 'catalog')
                    ->orWhereNull('price')
                    ->orWhere('price', '<=', 0);
            }));
    }

    public function isInCatalog(): bool
    {
        return self::query()->inCatalog()->whereKey($this->getKey())->exists();
    }

    public function originalPrice(): int
    {
        return (int) $this->courses->sum('price');
    }

    public function savings(): int
    {
        return max(0, $this->originalPrice() - (int) $this->price);
    }

    public function getPriceLabelAttribute(): string
    {
        return 'Rp '.number_format($this->price, 0, ',', '.');
    }

    public function getOriginalPriceLabelAttribute(): string
    {
        return 'Rp '.number_format($this->originalPrice(), 0, ',', '.');
    }

    public function getSavingsLabelAttribute(): string
    {
        return 'Rp '.number_format($this->savings(), 0, ',', '.');
    }
}
