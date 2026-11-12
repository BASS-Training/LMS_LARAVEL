<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseSalesProfile extends Model
{
    use HasFactory;

    public const SALES_METHOD_CHECKOUT = 'checkout';

    public const SALES_METHOD_WHATSAPP = 'whatsapp';

    protected $fillable = [
        'slug',
        'headline',
        'target_audience',
        'learning_benefits',
        'requirements',
        'level',
        'estimated_duration_minutes',
        'language',
        'promo_video_url',
        'faq',
        'seo_title',
        'seo_description',
        'sales_status',
        'sales_method',
        'published_at',
    ];

    protected $casts = [
        'faq' => 'array',
        'estimated_duration_minutes' => 'integer',
        'published_at' => 'datetime',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function isPublished(): bool
    {
        return $this->sales_status === 'published';
    }

    public function usesWhatsappSales(): bool
    {
        return $this->sales_method === self::SALES_METHOD_WHATSAPP;
    }
}
