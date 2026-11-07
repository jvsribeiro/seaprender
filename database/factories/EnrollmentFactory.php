<?php

namespace Database\Factories;

use App\Enums\EnrollmentStatus;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Enrollment>
 */
class EnrollmentFactory extends Factory
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
            'user_id' => User::factory(),
            'status' => EnrollmentStatus::Active,
        ];
    }

    public function removed(): static
    {
        return $this->state(['status' => EnrollmentStatus::Removed]);
    }
}
