<?php

namespace App\Actions\Quizzes;

use App\Actions\RecordAuditLog;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class SubmitQuizAttempt
{
    public function __construct(private RecordAuditLog $recordAuditLog) {}

    /**
     * Grade the answers on the server. Each student answers a quiz once.
     *
     * @param  array<int|string, int|string|null>  $answers  Chosen option keyed by question id.
     *
     * @throws ValidationException
     */
    public function handle(User $student, Quiz $quiz, array $answers): QuizAttempt
    {
        if (! $quiz->classroom->isActive()) {
            throw ValidationException::withMessages(['answers' => 'A turma está arquivada e não recebe mais respostas.']);
        }

        $questions = $quiz->questions()->get();

        $unanswered = $questions->reject(fn (QuizQuestion $question) => $this->isValidAnswer($question, $answers[$question->id] ?? null));

        if ($unanswered->isNotEmpty()) {
            throw ValidationException::withMessages(['answers' => 'Responda todas as perguntas antes de enviar.']);
        }

        $attempt = QuizAttempt::createOrFirst(
            ['quiz_id' => $quiz->id, 'user_id' => $student->id],
            [
                'score' => $questions->filter(fn (QuizQuestion $question) => (int) $answers[$question->id] === $question->correct_option)->count(),
                'total' => $questions->count(),
                'answers' => $questions->mapWithKeys(fn (QuizQuestion $question) => [$question->id => (int) $answers[$question->id]])->all(),
            ],
        );

        if ($attempt->wasRecentlyCreated) {
            $this->recordAuditLog->handle($student, 'quiz.answered', $quiz, ['title' => $quiz->title, 'score' => $attempt->score, 'total' => $attempt->total]);
        }

        return $attempt;
    }

    private function isValidAnswer(QuizQuestion $question, int|string|null $answer): bool
    {
        return is_numeric($answer) && array_key_exists((int) $answer, $question->options);
    }
}
