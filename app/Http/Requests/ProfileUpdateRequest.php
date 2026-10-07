<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'date_of_birth' => ['required', 'date'],
            'gender' => ['required', 'in:male,female'],
            'institution_name' => ['required', 'string', 'max:255'],
            'occupation' => ['required', 'string', 'max:255'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:max_width=4000,max_height=4000'],
            'remove_avatar' => ['nullable', 'boolean'],
            'instructor_bio' => [
                Rule::prohibitedIf(! $this->user()->can('manage own courses')),
                'nullable',
                'string',
                'max:10000',
            ],
        ];
    }
}
