<?php

namespace App\Livewire\Classrooms;

use App\Enums\EnrollmentStatus;
use App\Enums\UserRole;
use App\Models\Classroom;
use App\Models\LiveQuiz;
use App\Models\User;
use App\Support\ClassroomProgress;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

class Progress extends Component
{
    use WithPagination;

    #[Locked]
    public Classroom $classroom;

    public function mount(Classroom $classroom): void
    {
        $this->classroom = $classroom;
    }

    public function render(): View
    {
        Gate::authorize('view', $this->classroom);

        $classroomId = $this->classroom->id;
        $userId = Auth::id();
        $canManage = Gate::allows('manage', $this->classroom);
        $learners = User::query()
            ->select(['users.id', 'users.name'])
            ->withCount([
                'viewedPosts as mural_count' => fn ($query) => $query->where('posts.classroom_id', $classroomId),
            ]);
        ClassroomProgress::addCounts($learners, $this->classroom);

        return view('livewire.classrooms.progress', [
            'canManage' => $canManage,
            'totals' => [
                'mural' => $this->classroom->posts()->count(),
                ...ClassroomProgress::totals($this->classroom),
            ],
            'students' => $canManage
                ? $learners->where('role', UserRole::Student)
                    ->whereHas('enrollments', fn ($query) => $query->where('classroom_id', $classroomId)->where('status', EnrollmentStatus::Active))
                    ->orderBy('name')->orderBy('id')->paginate(25, pageName: 'progresso')
                : null,
            'personal' => $canManage ? null : $learners->findOrFail($userId),
            'notes' => $canManage ? null : ClassroomProgress::reflections($this->classroom)->where('post_responses.user_id', $userId)
                ->orderByDesc('post_responses.updated_at')->orderByDesc('posts.id')
                ->paginate(10, ['posts.id', 'posts.title', 'post_responses.summary'], 'resumos'),
            'games' => $canManage ? collect() : DB::table('live_quiz_players as players')
                ->join('live_quizzes as rooms', 'rooms.id', '=', 'players.live_quiz_id')
                ->where('players.user_id', $userId)->where('rooms.classroom_id', $classroomId)
                ->where('rooms.phase', LiveQuiz::FINISHED)
                ->select('rooms.id', 'rooms.title')
                ->addSelect(['points' => DB::table('live_quiz_answers')->whereColumn('live_quiz_id', 'rooms.id')
                    ->where('user_id', $userId)->selectRaw('coalesce(sum(score), 0)')])
                ->orderByDesc('rooms.updated_at')->orderByDesc('rooms.id')->limit(5)->get(),
        ]);
    }
}
