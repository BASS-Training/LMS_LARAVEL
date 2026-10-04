<?php

namespace Database\Factories;

use App\Models\Bundle;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class BundleFactory extends Factory
{
    protected $model = Bundle::class;

    public function definition(): array
    {
        $title = fake()->unique()->sentence(3);

        return [
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(100, 99999),
            'description' => fake()->paragraph(),
            'price' => fake()->numberBetween(100000, 1000000),
            'is_active' => false,
            'requires_payment_verification' => false,
        ];
    }
}
