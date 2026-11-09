<?php

namespace Database\Factories;

use App\Models\Classroom;
use App\Models\LiveQuiz;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<LiveQuiz> */
class LiveQuizFactory extends Factory
{
    public function definition(): array
    {
        return [
            'classroom_id' => Classroom::factory(),
            'title' => 'Quiz de HTML ao vivo',
            'questions' => [[
                'prompt' => 'Qual tag cria um link?',
                'options' => ['<p>', '<a>'],
                'correct' => 1,
                'explanation' => 'A tag a cria um hiperlink.',
            ]],
            'seconds_per_question' => 30,
        ];
    }
}
