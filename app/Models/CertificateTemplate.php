<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CertificateTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'layout_data',
    ];

    protected $casts = [
        'layout_data' => 'array',
    ];

    /**
     * Get the courses using this template.
     */
    public function courses()
    {
        return $this->hasMany(Course::class);
    }

    /**
     * Get certificates generated using this template.
     */
    public function certificates()
    {
        return $this->hasMany(Certificate::class);
    }

    public function firstPageDimensions(): array
    {
        $layout = $this->layout_data ?? [];
        $page = isset($layout[0]) ? $layout[0] : $layout;

        return [
            'width' => (float) ($page['width'] ?? 1123),
            'height' => (float) ($page['height'] ?? 794),
        ];
    }

    public function paperOrientation(): string
    {
        $dimensions = $this->firstPageDimensions();

        return $dimensions['width'] >= $dimensions['height'] ? 'landscape' : 'portrait';
    }

    public function editorType(): string
    {
        $layout = $this->layout_data ?? [];
        $page = isset($layout[0]) ? $layout[0] : $layout;

        if (in_array($page['editorType'] ?? null, ['enhanced', 'advanced'], true)) {
            return $page['editorType'];
        }

        foreach ($page['elements'] ?? [] as $element) {
            if (array_key_exists('type', $element)) {
                return 'advanced';
            }

            return 'enhanced';
        }

        return isset($page['width'], $page['height']) ? 'advanced' : 'enhanced';
    }

    /**
     * Get the background image URL if exists in layout_data
     */
    public function getBackgroundImageAttribute()
    {
        if (isset($this->layout_data['background_image'])) {
            return asset('storage/'.$this->layout_data['background_image']);
        }

        return null;
    }

    /**
     * Get template styles configuration
     */
    public function getStylesAttribute()
    {
        return $this->layout_data['styles'] ?? [
            'font_family' => 'Times New Roman',
            'primary_color' => '#000000',
            'secondary_color' => '#666666',
        ];
    }

    /**
     * Get template elements configuration
     */
    public function getElementsAttribute()
    {
        return $this->layout_data['elements'] ?? [];
    }
}
