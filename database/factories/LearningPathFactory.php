<?php

namespace Database\Factories;

use App\Models\LearningPath;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class LearningPathFactory extends Factory
{
    protected $model = LearningPath::class;

    public function definition(): array
    {
        $title = fake()->unique()->sentence(3);

        return [
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(100, 99999),
            'short_description' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'is_active' => false,
        ];
    }
}
