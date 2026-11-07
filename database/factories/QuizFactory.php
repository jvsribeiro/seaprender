<?php

namespace Database\Factories;

use App\Enums\QuizStatus;
use App\Models\Classroom;
use App\Models\Quiz;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Quiz>
 */
class QuizFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'classroom_id' => Classroom::factory(),
            'title' => 'Quiz '.fake()->unique()->numberBetween(100, 999),
            'status' => QuizStatus::Draft,
        ];
    }

    public function published(): static
    {
        return $this->state(['status' => QuizStatus::Published, 'published_at' => now()]);
    }
}
