<?php

namespace Database\Factories;

use App\Models\Coupon;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Coupon> */
class CouponFactory extends Factory
{
    protected $model = Coupon::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('SAVE##??')),
            'discount_type' => Coupon::TYPE_PERCENTAGE,
            'discount_value' => 10,
            'minimum_amount' => null,
            'usage_limit' => null,
            'per_user_limit' => 1,
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addMonth(),
            'is_active' => true,
            'applies_to_all_courses' => true,
        ];
    }
}
