<?php

namespace Database\Factories;

use App\Enums\PostType;
use App\Models\Classroom;
use App\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
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
            'type' => PostType::Announcement,
            'title' => 'Publicação '.fake()->unique()->numberBetween(100, 999),
            'body' => fake()->sentence(),
        ];
    }
}
