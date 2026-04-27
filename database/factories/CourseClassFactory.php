<?php

namespace Database\Factories;

use App\Models\Course;
use Illuminate\Database\Eloquent\Factories\Factory;

class CourseClassFactory extends Factory
{
    public function definition(): array
    {
        $start = now()->subDays(rand(1, 30));

        return [
            'course_id'    => Course::factory(),
            'name'         => 'Kelas ' . $this->faker->bothify('??-####'),
            'status'       => 'active',
            'start_date'   => $start,
            'end_date'     => $start->copy()->addDays(30),
            'program_type' => 'regular',
        ];
    }
}
