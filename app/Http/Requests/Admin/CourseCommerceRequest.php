<?php

namespace App\Http\Requests\Admin;

use App\Models\Course;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CourseCommerceRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Course|null $course */
        $course = $this->route('course');

        return ($this->user()?->can('manage course commerce') ?? false)
            && $course?->program_type === 'regular';
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'visibility' => $this->input('visibility', 'private'),
            'requires_payment_verification' => $this->boolean('requires_payment_verification'),
        ]);
    }

    public function rules(): array
    {
        /** @var Course $course */
        $course = $this->route('course');

        return [
            'visibility' => ['required', Rule::in(['private', 'catalog'])],
            'price' => ['nullable', 'integer', 'min:0', 'max:1000000000'],
            'requires_payment_verification' => ['required', 'boolean'],
            'short_description' => ['nullable', 'string', 'max:255'],
            'sales_profile' => ['required', 'array'],
            'sales_profile.slug' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('course_sales_profiles', 'slug')->ignore($course->salesProfile?->id),
            ],
            'sales_profile.headline' => ['nullable', 'string', 'max:255'],
            'sales_profile.target_audience' => ['nullable', 'string', 'max:5000'],
            'sales_profile.learning_benefits' => ['nullable', 'string', 'max:5000'],
            'sales_profile.requirements' => ['nullable', 'string', 'max:5000'],
            'sales_profile.level' => ['nullable', Rule::in(['beginner', 'intermediate', 'advanced', 'all_levels'])],
            'sales_profile.estimated_duration_minutes' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'sales_profile.language' => ['nullable', 'string', 'max:100'],
            'sales_profile.promo_video_url' => ['nullable', 'url', 'max:2048'],
            'sales_profile.faq' => ['nullable', 'array', 'max:20'],
            'sales_profile.faq.*.question' => ['required_with:sales_profile.faq.*.answer', 'nullable', 'string', 'max:255'],
            'sales_profile.faq.*.answer' => ['required_with:sales_profile.faq.*.question', 'nullable', 'string', 'max:2000'],
            'sales_profile.seo_title' => ['nullable', 'string', 'max:255'],
            'sales_profile.seo_description' => ['nullable', 'string', 'max:500'],
            'sales_profile.sales_status' => ['required', Rule::in(['draft', 'published', 'hidden'])],
        ];
    }
}
