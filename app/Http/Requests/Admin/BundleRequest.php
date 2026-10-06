<?php

namespace App\Http\Requests\Admin;

use App\Models\Bundle;
use App\Models\Course;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class BundleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage bundles') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => Str::slug((string) ($this->input('slug') ?: $this->input('title'))),
            'is_active' => $this->boolean('is_active'),
            'requires_payment_verification' => $this->boolean('requires_payment_verification'),
        ]);
    }

    public function rules(): array
    {
        /** @var Bundle|null $bundle */
        $bundle = $this->route('bundle');

        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('bundles', 'slug')->ignore($bundle)],
            'description' => ['nullable', 'string', 'max:5000'],
            'price' => ['required', 'integer', 'min:1'],
            'is_active' => ['required', 'boolean'],
            'requires_payment_verification' => ['required', 'boolean'],
            'course_ids' => ['required', 'array', 'min:2'],
            'course_ids.*' => ['integer', 'distinct', 'exists:courses,id'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $ids = array_values(array_unique(array_map('intval', $this->input('course_ids', []))));
                if (count($ids) < 2) {
                    return;
                }

                $validCount = Course::query()
                    ->whereKey($ids)
                    ->where('status', 'published')
                    ->where('visibility', 'catalog')
                    ->where('price', '>', 0)
                    ->count();

                if ($validCount !== count($ids)) {
                    $validator->errors()->add('course_ids', 'Semua course harus published, tampil di katalog, dan berbayar.');
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'course_ids.min' => 'Pilih minimal dua course untuk membuat bundle.',
            'course_ids.required' => 'Pilih course yang akan dimasukkan ke bundle.',
        ];
    }
}
