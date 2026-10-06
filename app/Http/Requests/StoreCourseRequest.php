<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'objectives' => 'nullable|string',
            'thumbnail' => 'nullable|image|max:2048',
            'status' => 'required|in:draft,published',
            'visibility' => 'nullable|in:private,catalog',
            'price' => 'nullable|integer|min:0|max:1000000000',
            'requires_payment_verification' => 'nullable|boolean',
            'short_description' => 'nullable|string|max:255',
            'program_type' => 'required|in:regular,avpn_ai',
            'training_start_date' => 'nullable|date|required_with:training_end_date',
            'training_end_date' => 'nullable|date|after_or_equal:training_start_date|required_with:training_start_date',
            'certificate_template_id' => 'nullable|exists:certificate_templates,id',
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer', 'distinct', Rule::exists('categories', 'id')->where('is_active', true)],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer', 'distinct', Rule::exists('tags', 'id')->where('is_active', true)],
            'taxonomy_present' => ['nullable', 'boolean'],

            'enable_periods' => 'nullable|boolean',
            'periods' => 'nullable|array',
            'periods.*.name' => 'required_with:periods|string|max:255',
            'periods.*.start_date' => 'nullable|date',
            'periods.*.end_date' => 'nullable|date|after:periods.*.start_date',
            'periods.*.description' => 'nullable|string',
            'periods.*.max_participants' => 'nullable|integer|min:1',

            'create_default_period' => 'nullable|boolean',
            'default_start_date' => 'nullable|date',
            'default_end_date' => 'nullable|date|after:default_start_date',
        ];
    }
}
