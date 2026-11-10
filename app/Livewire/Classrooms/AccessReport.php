<?php

namespace App\Livewire\Classrooms;

use App\Enums\EnrollmentStatus;
use App\Enums\QuizStatus;
use App\Models\Classroom;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

class AccessReport extends Component
{
    use WithPagination;

    #[Locked]
    public Classroom $classroom;

    public bool $onlyMissing = false;

    public function mount(Classroom $classroom): void
    {
        Gate::authorize('manage', $classroom);

        $this->classroom = $classroom;
    }

    public function updatedOnlyMissing(): void
    {
        $this->resetPage('acessos');
    }

    public function render(): View
    {
        Gate::authorize('manage', $this->classroom);

        $classroomId = $this->classroom->id;

        return view('livewire.classrooms.access-report', [
            'postsCount' => $this->classroom->posts()->count(),
            'quizzesCount' => $this->classroom->quizzes()->where('status', QuizStatus::Published)->count(),
            'students' => User::query()
                ->whereHas('enrollments', fn ($query) => $query->where('classroom_id', $classroomId)->where('status', EnrollmentStatus::Active))
                ->withCount([
                    'viewedPosts as seen_posts_count' => fn ($query) => $query->where('posts.classroom_id', $classroomId),
                    'quizAttempts as answered_quizzes_count' => fn ($query) => $query->whereRelation('quiz', 'classroom_id', $classroomId),
                ])
                ->when($this->onlyMissing, fn ($query) => $query->whereDoesntHave('viewedPosts', fn ($query) => $query->where('posts.classroom_id', $classroomId)))
                ->orderBy('name')
                ->orderBy('id')
                ->paginate(25, pageName: 'acessos'),
        ]);
    }
}
