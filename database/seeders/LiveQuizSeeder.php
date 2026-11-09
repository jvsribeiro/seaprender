<?php

namespace Database\Seeders;

use App\Actions\Quizzes\RunLiveQuiz;
use App\Models\Classroom;
use Illuminate\Database\Seeder;

class LiveQuizSeeder extends Seeder
{
    public function run(RunLiveQuiz $liveQuiz): void
    {
        if (! app()->isLocal()) {
            return;
        }

        $classroom = Classroom::query()->where('name', 'Programação Web T1')
            ->whereHas('teacher', fn ($query) => $query->where('email', 'joao.ribeiro@example.test'))
            ->first();
        $quiz = $classroom?->quizzes()->where('title', 'Quiz de HTML')->first();

        if ($quiz && $classroom->isActive()) {
            $liveQuiz->open($classroom->teacher, $classroom, $quiz->id, 30);
        }
    }
}
