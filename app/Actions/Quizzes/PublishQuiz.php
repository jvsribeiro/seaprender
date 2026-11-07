<?php

namespace App\Actions\Quizzes;

use App\Actions\Posts\PublishPost;
use App\Actions\RecordAuditLog;
use App\Enums\PostType;
use App\Enums\QuizStatus;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PublishQuiz
{
    public function __construct(
        private PublishPost $publishPost,
        private RecordAuditLog $recordAuditLog,
    ) {}

    /**
     * Open the quiz to the students and announce it on the classroom feed.
     *
     * @throws ValidationException
     */
    public function handle(User $actor, Quiz $quiz): void
    {
        if ($quiz->isPublished()) {
            return;
        }

        if (! $quiz->questions()->exists()) {
            throw ValidationException::withMessages(['title' => 'Adicione pelo menos uma pergunta antes de publicar.']);
        }

        DB::transaction(function () use ($actor, $quiz) {
            $quiz->status = QuizStatus::Published;
            $quiz->published_at = now();
            $quiz->save();

            $this->publishPost->handle($actor, $quiz->classroom, [
                'type' => PostType::Quiz,
                'title' => $quiz->title,
                'body' => $quiz->description,
                'subject_id' => $quiz->subject_id,
                'quiz_id' => $quiz->id,
            ]);

            $this->recordAuditLog->handle($actor, 'quiz.published', $quiz, ['title' => $quiz->title]);
        });
    }
}
