<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LearningPath extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'short_description',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function courses()
    {
        return $this->belongsToMany(Course::class, 'course_learning_path')
            ->withPivot('sort_order')
            ->orderByPivot('sort_order');
    }

    public function scopeInCatalog(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->has('courses', '>=', 2)
            ->whereDoesntHave('courses', fn (Builder $query) => $query->where(function (Builder $query) {
                $query->where('program_type', '!=', 'regular')
                    ->orWhereNull('status')
                    ->orWhere('status', '!=', 'published')
                    ->orWhereNull('visibility')
                    ->orWhere('visibility', '!=', 'catalog')
                    ->orWhereHas('salesProfile', fn (Builder $query) => $query->where('sales_status', '!=', 'published'));
            }));
    }

    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        return $query;
    }

    public function isInCatalog(?User $user = null): bool
    {
        return self::query()->inCatalog()->visibleTo($user)->whereKey($this->getKey())->exists();
    }
}
