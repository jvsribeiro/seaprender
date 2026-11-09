<?php

namespace App\Livewire\QuestionGames;

use App\Actions\Quizzes\RunLiveQuiz;
use App\Models\Classroom;
use App\Models\LiveQuiz;
use App\Models\QuestionGame;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

class Library extends Component
{
    use WithPagination;

    #[Locked]
    public Classroom $classroom;

    public function mount(Classroom $classroom): void
    {
        $this->classroom = $classroom;
    }

    public function play(int $gameId, bool $publish, RunLiveQuiz $runner): void
    {
        Gate::authorize('manage', $this->classroom);
        /** @var User $host */
        $host = Auth::user();
        $room = $runner->openGame($host, $this->classroom, $gameId, $publish);

        $this->redirect(route('quizzes.live', $room), navigate: true);
    }

    public function render(RunLiveQuiz $runner): View
    {
        Gate::authorize('view', $this->classroom);
        $canManage = Gate::allows('manage', $this->classroom);
        $activeRoom = $this->classroom->isActive() ? $runner->activeRoom($this->classroom) : null;

        return view('livewire.question-games.library', [
            'canManage' => $canManage,
            'activeRoom' => $activeRoom,
            'games' => $canManage ? QuestionGame::query()->where('classroom_id', $this->classroom->id)
                ->orderByDesc('updated_at')->orderByDesc('id')->paginate(12, pageName: 'jogos') : null,
            'recentRooms' => $canManage ? collect() : LiveQuiz::query()
                ->where('live_quizzes.classroom_id', $this->classroom->id)
                ->whereNotNull('game_settings')->where('phase', LiveQuiz::FINISHED)
                ->join('live_quiz_players as players', 'players.live_quiz_id', '=', 'live_quizzes.id')
                ->where('players.user_id', Auth::id())->latest('live_quizzes.id')
                ->limit(5)->get(['live_quizzes.id', 'title']),
        ]);
    }
}
