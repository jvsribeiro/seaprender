<?php

namespace App\Actions\Quizzes;

use App\Actions\RecordAuditLog;
use App\Models\Classroom;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaveQuiz
{
    public function __construct(private RecordAuditLog $recordAuditLog) {}

    /**
     * @param  array{title: string, description: ?string, subject_id: ?int}  $attributes
     * @param  list<array{prompt: string, options: list<string>, correct: int, explanation: ?string}>  $questions
     *
     * @throws ValidationException
     */
    public function handle(User $author, Classroom $classroom, ?Quiz $quiz, array $attributes, array $questions): Quiz
    {
        if (! $classroom->isActive()) {
            throw ValidationException::withMessages(['title' => 'Reative a turma para salvar quizzes.']);
        }

        if ($quiz?->attempts()->exists()) {
            throw ValidationException::withMessages(['title' => 'Este quiz já tem respostas e não pode mais ser alterado.']);
        }

        foreach ($questions as $index => $question) {
            if (! array_key_exists((int) $question['correct'], $question['options'])) {
                throw ValidationException::withMessages(["questions.{$index}.correct" => 'Marque a alternativa correta.']);
            }
        }

        return DB::transaction(function () use ($author, $classroom, $quiz, $attributes, $questions) {
            $quiz ??= $classroom->quizzes()->make(['author_id' => $author->id]);
            $quiz->fill($attributes)->save();

            $quiz->questions()->delete();

            foreach (array_values($questions) as $index => $question) {
                $quiz->questions()->create([
                    'position' => $index + 1,
                    'prompt' => $question['prompt'],
                    'options' => array_values($question['options']),
                    'correct_option' => (int) $question['correct'],
                    'explanation' => $question['explanation'] ?: null,
                ]);
            }

            $this->recordAuditLog->handle($author, 'quiz.saved', $quiz, ['title' => $quiz->title]);

            return $quiz;
        });
    }
}
