<?php

namespace Database\Factories;

use App\Models\Content;
use App\Models\Course;
use App\Models\CoursePreview;
use App\Models\Lesson;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CoursePreview>
 */
class CoursePreviewFactory extends Factory
{
    protected $model = CoursePreview::class;

    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'content_id' => function (array $attributes) {
                $lesson = Lesson::factory()->create(['course_id' => $attributes['course_id']]);

                return Content::factory()->create(['lesson_id' => $lesson->id])->id;
            },
            'sort_order' => 0,
        ];
    }
}
