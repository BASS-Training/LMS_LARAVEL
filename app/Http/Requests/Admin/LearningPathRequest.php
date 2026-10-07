<?php

namespace App\Http\Requests\Admin;

use App\Models\Course;
use App\Models\LearningPath;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class LearningPathRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage learning paths') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => Str::slug((string) ($this->input('slug') ?: $this->input('title'))),
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    public function rules(): array
    {
        /** @var LearningPath|null $learningPath */
        $learningPath = $this->route('learningPath');

        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('learning_paths', 'slug')->ignore($learningPath)],
            'short_description' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:5000'],
            'is_active' => ['required', 'boolean'],
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
                    ->inCatalog()
                    ->count();

                if ($validCount !== count($ids)) {
                    $validator->errors()->add('course_ids', 'Semua course harus Regular, published, dan tampil di katalog.');
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'course_ids.min' => 'Pilih minimal dua course untuk membuat learning path.',
            'course_ids.required' => 'Pilih course yang akan dimasukkan ke learning path.',
        ];
    }
}
