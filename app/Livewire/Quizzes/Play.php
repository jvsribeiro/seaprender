<?php

namespace App\Livewire\Quizzes;

use App\Actions\Quizzes\SubmitQuizAttempt;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Quiz')]
class Play extends Component
{
    #[Locked]
    public Quiz $quiz;

    /** @var array<int, int|string|null> */
    public array $answers = [];

    public function mount(Quiz $quiz): void
    {
        Gate::authorize('view', $quiz->classroom);

        abort_unless($quiz->isPublished() || Gate::allows('manage', $quiz->classroom), 404);

        $this->quiz = $quiz;
    }

    public function submit(SubmitQuizAttempt $submitQuizAttempt): void
    {
        Gate::authorize('view', $this->quiz->classroom);

        abort_unless($this->quiz->isPublished() && $this->user()->isStudent(), 403);

        $submitQuizAttempt->handle($this->user(), $this->quiz, $this->answers);

        $this->dispatch('toast', message: 'Respostas enviadas.');
    }

    public function render(): View
    {
        return view('livewire.quizzes.play', [
            'attempt' => $this->quiz->attempts()->where('user_id', $this->user()->id)->first(),
            'canManage' => Gate::allows('manage', $this->quiz->classroom),
            'questions' => $this->quiz->questions()->get(),
        ])->title($this->quiz->title);
    }

    private function user(): User
    {
        /** @var User */
        return Auth::user();
    }
}
