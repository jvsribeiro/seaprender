<?php

namespace App\Livewire\Quizzes;

use App\Actions\Quizzes\RunLiveQuiz;
use App\Models\LiveQuiz;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Component;

class LiveRoom extends Component
{
    #[Locked]
    public LiveQuiz $liveQuiz;

    public function mount(LiveQuiz $liveQuiz): void
    {
        $this->liveQuiz = $liveQuiz;
    }

    public function join(RunLiveQuiz $runner): void
    {
        $runner->join($this->user(), $this->liveQuiz);
    }

    public function answer(int $round, int $choice, RunLiveQuiz $runner): void
    {
        $runner->answer($this->user(), $this->liveQuiz, $round, $choice);
    }

    public function advance(int $round, string $phase, RunLiveQuiz $runner): void
    {
        $runner->advance($this->user(), $this->liveQuiz, $round, $phase);
    }

    public function finish(RunLiveQuiz $runner): void
    {
        $runner->finish($this->user(), $this->liveQuiz);
    }

    public function publishToMural(RunLiveQuiz $runner): void
    {
        $runner->publishInvitation($this->user(), $this->liveQuiz);
        $this->dispatch('toast', message: 'Partida publicada no mural.');
    }

    public function render(RunLiveQuiz $runner): View
    {
        $room = $this->liveQuiz->refresh();
        $classroom = $room->classroom;
        Gate::authorize('view', $classroom);

        $now = now();
        $phase = $room->currentPhase($now);
        $phaseDeadline = match ($phase) {
            LiveQuiz::COUNTDOWN => $room->questionOpensAt(),
            LiveQuiz::QUESTION => $room->question_ends_at,
            default => null,
        };
        $remainingMilliseconds = max(0, ($phaseDeadline?->getTimestampMs() ?? 0) - $now->getTimestampMs());
        $user = $this->user();
        $canManage = Gate::allows('manage', $classroom);
        $isPlayer = $user->isStudent() && $runner->players($room)->where('user_id', $user->id)->exists();
        $answers = $runner->answers($room);
        $roundAnswers = (clone $answers)->where('question_index', $room->question_index);
        $choiceCounts = $phase === LiveQuiz::REVEAL
            ? (clone $roundAnswers)->select('choice')->selectRaw('count(*) as answers')->groupBy('choice')->pluck('answers', 'choice')
            : collect();
        $rankings = collect();

        if ($canManage && $phase === LiveQuiz::FINISHED) {
            $scores = (clone $answers)->select('user_id')->selectRaw('sum(score) as points')->groupBy('user_id');
            $rankings = $runner->players($room)->join('users', 'users.id', '=', 'live_quiz_players.user_id')
                ->leftJoinSub($scores, 'scores', 'scores.user_id', '=', 'users.id')
                ->select('users.id', 'users.name')->selectRaw('coalesce(scores.points, 0) as points')
                ->orderByDesc('points')->orderBy('users.name')->orderBy('users.id')->limit(10)->get();
            $rank = 0;
            $previousScore = null;
            foreach ($rankings as $index => $player) {
                if ($previousScore !== $player->points) {
                    $rank = $index + 1;
                    $previousScore = $player->points;
                }
                $player->rank = $rank;
            }
        }

        $question = in_array($phase, [LiveQuiz::QUESTION, LiveQuiz::REVEAL], true) ? $room->questions[$room->question_index] : null;

        if ($phase === LiveQuiz::QUESTION) {
            unset($question['correct'], $question['explanation']);
        }

        $game = $room->isQuestionGame();

        return view($game ? 'livewire.quizzes.se-pergunta-room' : 'livewire.quizzes.live-room', [
            'room' => $room,
            'classroom' => $classroom,
            'theme' => $game ? $room->game_settings['theme'] : $classroom->theme(),
            'publication' => $canManage ? $classroom->posts()->where('live_quiz_id', $room->id)->value('id') : null,
            'phase' => $phase,
            'question' => $question,
            'canManage' => $canManage,
            'isPlayer' => $isPlayer,
            'canJoin' => $user->isStudent() && $classroom->isActive() && $phase !== LiveQuiz::FINISHED,
            'playerCount' => $runner->players($room)->count(),
            'secondsLeft' => (int) ceil($remainingMilliseconds / 1000),
            'phaseDeadline' => $phaseDeadline?->getTimestampMs() ?? 0,
            'clockTimestamp' => $now->getTimestampMs(),
            'ownAnswer' => $isPlayer && $question ? (clone $roundAnswers)->where('user_id', $user->id)->value('choice') : null,
            'answered' => $canManage && $phase === LiveQuiz::QUESTION ? $roundAnswers->count() : $choiceCounts->sum(),
            'choiceCounts' => $choiceCounts,
            'rankings' => $rankings,
            'ownScore' => $isPlayer && $phase === LiveQuiz::FINISHED ? $answers->where('user_id', $user->id)->sum('score') : null,
        ])->title(($game ? 'Se Pergunta' : 'Ao vivo').' · '.$room->title);
    }

    private function user(): User
    {
        /** @var User */
        return Auth::user();
    }
}
