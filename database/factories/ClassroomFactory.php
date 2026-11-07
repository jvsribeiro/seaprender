<?php

namespace Database\Factories;

use App\Enums\ClassroomStatus;
use App\Models\Classroom;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Classroom>
 */
class ClassroomFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'teacher_id' => User::factory()->teacher(),
            'name' => 'Turma '.fake()->unique()->numberBetween(100, 999),
            'course' => 'Técnico em Informática',
            'curricular_unit' => 'Programação Web',
            'period' => 'Noite',
            'enrollment_code' => fake()->unique()->regexify('[A-HJ-NP-Z2-9]{8}'),
            'starts_on' => now()->startOfMonth(),
            'ends_on' => now()->addMonths(6),
            'status' => ClassroomStatus::Active,
        ];
    }

    public function archived(): static
    {
        return $this->state(['status' => ClassroomStatus::Archived]);
    }
}
