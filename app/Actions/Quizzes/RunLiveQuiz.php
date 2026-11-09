<?php

namespace App\Actions\Quizzes;

use App\Actions\Posts\PublishPost;
use App\Actions\RecordAuditLog;
use App\Enums\PostType;
use App\Models\Classroom;
use App\Models\LiveQuiz;
use App\Models\Post;
use App\Models\QuestionGame;
use App\Models\QuizQuestion;
use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class RunLiveQuiz
{
    public function __construct(private RecordAuditLog $recordAuditLog, private PublishPost $publishPost) {}

    public function open(User $host, Classroom $classroom, int $quizId, int $seconds): LiveQuiz
    {
        Gate::forUser($host)->authorize('manage', $classroom);

        return DB::transaction(function () use ($host, $classroom, $quizId, $seconds) {
            $classroom = Classroom::query()->lockForUpdate()->findOrFail($classroom->id);
            $this->ensureActive($classroom);

            $existing = $this->activeRoom($classroom);
            if ($existing) {
                if ($existing->isQuestionGame()) {
                    throw ValidationException::withMessages(['room' => 'Encerre o jogo do Se Pergunta antes de abrir o quiz.']);
                }

                return $existing;
            }

            $quiz = $classroom->quizzes()->findOrFail($quizId);
            $questions = $quiz->questions()->get()->map(fn (QuizQuestion $question) => [
                'prompt' => $question->prompt,
                'options' => $question->options,
                'correct' => $question->correct_option,
                'explanation' => $question->explanation,
            ]);
            if ($questions->isEmpty()) {
                throw ValidationException::withMessages(['quizId' => 'Adicione perguntas ao quiz antes de abrir a sala.']);
            }

            return $this->createRoom($host, [
                'classroom_id' => $classroom->id,
                'title' => $quiz->title,
                'questions' => $questions->all(),
                'seconds_per_question' => $seconds,
            ]);

        });
    }

    public function openGame(User $host, Classroom $classroom, int $gameId, bool $publish): LiveQuiz
    {
        Gate::forUser($host)->authorize('manage', $classroom);

        return DB::transaction(function () use ($host, $classroom, $gameId, $publish) {
            $classroom = Classroom::query()->lockForUpdate()->findOrFail($classroom->id);
            $this->ensureActive($classroom);
            $game = QuestionGame::query()->where('classroom_id', $classroom->id)->findOrFail($gameId);
            $room = $this->activeRoom($classroom);

            if ($room && $room->question_game_id !== $game->id) {
                throw ValidationException::withMessages(['room' => 'Encerre a partida atual antes de abrir outro jogo.']);
            }

            $room ??= $this->createRoom($host, [
                'classroom_id' => $classroom->id,
                'question_game_id' => $game->id,
                'title' => $game->title,
                'questions' => $game->questions,
                'seconds_per_question' => $game->seconds_per_question,
                'game_settings' => ['theme' => $game->theme, 'topic' => $game->topic, 'intro' => $game->intro],
            ]);

            if ($publish) {
                $this->publishInvitation($host, $room);
            }

            return $room;
        });
    }

    public function publishInvitation(User $host, LiveQuiz $room): Post
    {
        $room->refresh();
        $classroom = $room->classroom;
        Gate::forUser($host)->authorize('manage', $classroom);
        $this->ensureActive($classroom);

        return $this->publishPost->handle($host, $classroom, [
            'type' => PostType::LiveInvitation,
            'live_quiz_id' => $room->id,
            'title' => $room->title,
            'body' => $room->game_settings['intro'] ?? null,
        ]);
    }

    public function activeRoom(Classroom $classroom): ?LiveQuiz
    {
        return LiveQuiz::query()->where('classroom_id', $classroom->id)
            ->where('phase', '!=', LiveQuiz::FINISHED)->latest('id')->first(['id', 'title', 'question_game_id', 'game_settings']);
    }

    public function join(User $student, LiveQuiz $room): void
    {
        $this->authorizePlayer($student, $room);

        DB::transaction(function () use ($student, $room) {
            $room = LiveQuiz::query()->lockForUpdate()->findOrFail($room->id);
            $this->ensureActive($room->classroom);

            if ($room->phase === LiveQuiz::FINISHED) {
                return;
            }

            $this->players($room)->insertOrIgnore([
                'live_quiz_id' => $room->id,
                'user_id' => $student->id,
                'created_at' => now(),
            ]);
        });
    }

    public function answer(User $student, LiveQuiz $room, int $round, int $choice): void
    {
        $this->authorizePlayer($student, $room);

        DB::transaction(function () use ($student, $room, $round, $choice) {
            $room = LiveQuiz::query()->lockForUpdate()->findOrFail($room->id);
            $this->ensureActive($room->classroom);

            $phase = $room->currentPhase();
            if ($phase !== LiveQuiz::QUESTION || $room->question_index !== $round) {
                throw ValidationException::withMessages(['room' => $phase === LiveQuiz::COUNTDOWN && $room->question_index === $round
                    ? 'Aguarde o fim da contagem para responder.'
                    : 'O tempo desta pergunta acabou. Aguarde a próxima.']);
            }

            abort_unless($this->players($room)->where('user_id', $student->id)->exists(), 403);
            $question = $room->questions[$round];
            abort_unless(array_key_exists($choice, $question['options']), 422);

            $this->answers($room)->insertOrIgnore([
                'live_quiz_id' => $room->id,
                'user_id' => $student->id,
                'question_index' => $round,
                'choice' => $choice,
                'score' => $choice === $question['correct'] ? 100 : 0,
                'created_at' => now(),
            ]);
        });
    }

    public function advance(User $host, LiveQuiz $room, int $round, string $phase): void
    {
        Gate::forUser($host)->authorize('manage', $room->classroom);

        DB::transaction(function () use ($host, $room, $round, $phase) {
            $room = LiveQuiz::query()->lockForUpdate()->findOrFail($room->id);
            $this->ensureActive($room->classroom);

            if (! in_array($phase, [LiveQuiz::LOBBY, LiveQuiz::QUESTION, LiveQuiz::REVEAL], true)
                || $room->question_index !== $round || $room->currentPhase() !== $phase) {
                return;
            }

            if ($phase === LiveQuiz::LOBBY && ! $this->players($room)->exists()) {
                throw ValidationException::withMessages(['room' => 'Espere pelo menos um aluno entrar na sala.']);
            }

            if ($phase === LiveQuiz::QUESTION) {
                $room->update(['phase' => LiveQuiz::REVEAL, 'question_ends_at' => null]);

                return;
            }

            $nextRound = $phase === LiveQuiz::LOBBY ? 0 : $round + 1;
            if ($nextRound >= count($room->questions)) {
                $this->finishRoom($host, $room);

                return;
            }

            $opensAt = $room->isQuestionGame() ? now()->ceilSecond()->addSeconds(LiveQuiz::COUNTDOWN_SECONDS) : now();
            $room->update([
                'phase' => LiveQuiz::QUESTION,
                'question_index' => $nextRound,
                'question_ends_at' => $opensAt->addSeconds($room->seconds_per_question),
            ]);
            if ($phase === LiveQuiz::LOBBY) {
                $this->recordAuditLog->handle($host, 'live_quiz.started', $room, ['title' => $room->title]);
            }
        });
    }

    public function finish(User $host, LiveQuiz $room): void
    {
        Gate::forUser($host)->authorize('manage', $room->classroom);

        DB::transaction(function () use ($host, $room) {
            $room = LiveQuiz::query()->lockForUpdate()->findOrFail($room->id);
            if ($room->phase !== LiveQuiz::FINISHED) {
                $this->finishRoom($host, $room);
            }
        });
    }

    public function players(LiveQuiz $room): Builder
    {
        return DB::table('live_quiz_players')->where('live_quiz_players.live_quiz_id', $room->id);
    }

    public function answers(LiveQuiz $room): Builder
    {
        return DB::table('live_quiz_answers')->where('live_quiz_answers.live_quiz_id', $room->id);
    }

    private function finishRoom(User $host, LiveQuiz $room): void
    {
        $room->update(['phase' => LiveQuiz::FINISHED, 'question_ends_at' => null]);
        $this->recordAuditLog->handle($host, 'live_quiz.finished', $room, ['title' => $room->title]);
    }

    /** @param array<string, mixed> $attributes */
    private function createRoom(User $host, array $attributes): LiveQuiz
    {
        $room = LiveQuiz::create($attributes);
        $this->recordAuditLog->handle($host, 'live_quiz.opened', $room, ['title' => $room->title]);

        return $room;
    }

    private function authorizePlayer(User $student, LiveQuiz $room): void
    {
        Gate::forUser($student)->authorize('view', $room->classroom);
        abort_unless($student->isStudent(), 403);
    }

    private function ensureActive(Classroom $classroom): void
    {
        if (! $classroom->isActive()) {
            throw ValidationException::withMessages(['room' => 'A turma está arquivada.']);
        }
    }
}
