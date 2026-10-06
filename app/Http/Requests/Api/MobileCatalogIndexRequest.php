<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class MobileCatalogIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'min:2', 'max:100'],
            'harga' => ['nullable', 'in:free,paid'],
            'category' => ['nullable', 'string', Rule::exists('categories', 'slug')->where('is_active', true)],
            'tag' => ['nullable', 'string', Rule::exists('tags', 'slug')->where('is_active', true)],
            'page' => ['nullable', 'integer', 'min:1'],
            'perPage' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'status' => 'error',
            'message' => 'Parameter katalog tidak valid.',
            'errors' => $validator->errors(),
        ], 422));
    }
}
