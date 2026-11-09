<?php

namespace App\Livewire\Quizzes;

use App\Actions\Quizzes\PublishQuiz;
use App\Actions\RecordAuditLog;
use App\Enums\QuizStatus;
use App\Models\Classroom;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Index extends Component
{
    #[Locked]
    public Classroom $classroom;

    public function mount(Classroom $classroom): void
    {
        Gate::authorize('view', $classroom);

        $this->classroom = $classroom;
    }

    public function publish(int $quizId, PublishQuiz $publishQuiz): void
    {
        Gate::authorize('manage', $this->classroom);

        $publishQuiz->handle($this->user(), $this->classroom->quizzes()->findOrFail($quizId));

        $this->dispatch('toast', message: 'Quiz publicado no mural.');
    }

    public function remove(int $quizId, RecordAuditLog $recordAuditLog): void
    {
        Gate::authorize('manage', $this->classroom);

        $quiz = $this->classroom->quizzes()->findOrFail($quizId);

        $recordAuditLog->handle($this->user(), 'quiz.removed', $quiz, ['title' => $quiz->title]);

        $quiz->delete();

        $this->dispatch('toast', message: 'Quiz excluído.');
    }

    public function render(): View
    {
        $user = $this->user();
        $canManage = Gate::allows('manage', $this->classroom);

        return view('livewire.quizzes.index', [
            'canManage' => $canManage,
            'quizzes' => $this->classroom->quizzes()
                ->with(['subject:id,name', 'attempts' => fn ($query) => $query->where('user_id', $user->id)])
                ->withCount(['questions', 'attempts as answers_count'])
                ->when(! $canManage, fn ($query) => $query->where('status', QuizStatus::Published))
                ->latest('id')
                ->get(),
        ]);
    }

    private function user(): User
    {
        /** @var User */
        return Auth::user();
    }
}
