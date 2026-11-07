<?php

namespace App\Livewire\Quizzes;

use App\Models\Enrollment;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Resultados do quiz')]
class Results extends Component
{
    #[Locked]
    public Quiz $quiz;

    public function mount(Quiz $quiz): void
    {
        Gate::authorize('manage', $quiz->classroom);

        $this->quiz = $quiz;
    }

    public function render(): View
    {
        Gate::authorize('manage', $this->quiz->classroom);

        $attempts = $this->quiz->attempts()->get()->keyBy('user_id');

        $students = $this->quiz->classroom->activeEnrollments()
            ->with('user:id,name')
            ->get()
            ->map(fn (Enrollment $enrollment) => $enrollment->user)
            ->sortBy('name');

        return view('livewire.quizzes.results', [
            'attempts' => $attempts,
            'students' => $students,
            'questions' => $this->quiz->questions()->get()->map(fn (QuizQuestion $question) => [
                'prompt' => $question->prompt,
                'hits' => $attempts->filter(fn (QuizAttempt $attempt) => (int) ($attempt->answers[$question->id] ?? -1) === $question->correct_option)->count(),
            ]),
        ]);
    }
}
