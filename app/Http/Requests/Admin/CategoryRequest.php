<?php

namespace App\Http\Requests\Admin;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage course taxonomy') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'integer', Rule::exists('categories', 'id')],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:4294967295'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            /** @var Category|null $category */
            $category = $this->route('category');
            $parentId = $this->integer('parent_id') ?: null;

            if (! $category || ! $parentId) {
                return;
            }

            if ($parentId === $category->id || $category->descendantIds()->contains($parentId)) {
                $validator->errors()->add('parent_id', 'Induk kategori tidak boleh berasal dari kategori ini atau turunannya.');
            }
        }];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'parent_id' => $this->input('parent_id') ?: null,
            'is_active' => $this->boolean('is_active'),
            'sort_order' => $this->input('sort_order', 0),
        ]);
    }
}
