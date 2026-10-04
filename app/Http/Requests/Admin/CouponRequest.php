<?php

namespace App\Http\Requests\Admin;

use App\Models\Coupon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage coupons') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => mb_strtoupper(trim((string) $this->input('code'))),
            'is_active' => $this->boolean('is_active'),
            'applies_to_all_courses' => $this->boolean('applies_to_all_courses'),
        ]);
    }

    public function rules(): array
    {
        $coupon = $this->route('coupon');

        return [
            'code' => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Z0-9_-]+$/',
                Rule::unique('coupons', 'code')->ignore($coupon),
            ],
            'discount_type' => ['required', Rule::in([Coupon::TYPE_FIXED, Coupon::TYPE_PERCENTAGE])],
            'discount_value' => [
                'required',
                'integer',
                'min:1',
                Rule::when($this->input('discount_type') === Coupon::TYPE_PERCENTAGE, ['max:99']),
            ],
            'minimum_amount' => ['nullable', 'integer', 'min:1'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'per_user_limit' => ['nullable', 'integer', 'min:1'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after:starts_at'],
            'is_active' => ['required', 'boolean'],
            'applies_to_all_courses' => ['required', 'boolean'],
            'course_ids' => ['required_if:applies_to_all_courses,0', 'array'],
            'course_ids.*' => ['integer', 'exists:courses,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.regex' => 'Kode kupon hanya boleh berisi huruf, angka, tanda hubung, dan garis bawah.',
            'discount_value.max' => 'Diskon persentase maksimal 99% untuk menjaga total pembayaran tetap positif.',
            'course_ids.required_if' => 'Pilih minimal satu kursus untuk kupon khusus.',
            'expires_at.after' => 'Waktu berakhir harus setelah waktu mulai.',
        ];
    }
}
