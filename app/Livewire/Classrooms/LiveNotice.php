<?php

namespace App\Livewire\Classrooms;

use App\Actions\Quizzes\RunLiveQuiz;
use App\Models\Classroom;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Component;

class LiveNotice extends Component
{
    #[Locked]
    public Classroom $classroom;

    public function mount(Classroom $classroom): void
    {
        $this->classroom = $classroom;
    }

    public function render(RunLiveQuiz $liveQuiz): View
    {
        Gate::authorize('view', $this->classroom);

        return view('livewire.classrooms.live-notice', [
            'room' => $this->classroom->isActive() ? $liveQuiz->activeRoom($this->classroom) : null,
            'canManage' => Gate::allows('manage', $this->classroom),
        ]);
    }
}
