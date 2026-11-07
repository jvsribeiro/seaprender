<?php

namespace App\Livewire\Classrooms;

use App\Actions\Classrooms\EnrollInClassroom;
use App\Enums\ClassroomStatus;
use App\Enums\EnrollmentStatus;
use App\Models\Classroom;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Procurar turmas')]
class Browse extends Component
{
    use WithPagination;

    #[Url(as: 'busca', except: '')]
    public string $search = '';

    public function mount(): void
    {
        Gate::authorize('enroll', Classroom::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function enroll(int $classroomId, EnrollInClassroom $enrollInClassroom): void
    {
        Gate::authorize('enroll', Classroom::class);

        $classroom = Classroom::findOrFail($classroomId);

        $enrollInClassroom->handle($this->user(), $classroom);

        session()->flash('toast', 'Inscrição feita.');

        $this->redirectRoute('classrooms.show', $classroom, navigate: true);
    }

    public function render(): View
    {
        $user = $this->user();
        $term = "%{$this->search}%";

        return view('livewire.classrooms.browse', [
            'classrooms' => Classroom::query()
                ->with('teacher:id,name')
                ->withCount('activeEnrollments')
                ->withExists(['activeEnrollments as enrolled' => fn ($query) => $query->where('user_id', $user->id)])
                ->where('status', ClassroomStatus::Active)
                ->whereDoesntHave('enrollments', fn ($query) => $query
                    ->where('user_id', $user->id)
                    ->where('status', EnrollmentStatus::Removed))
                ->when($this->search !== '', fn ($query) => $query->where(fn ($query) => $query
                    ->whereLike('name', $term)
                    ->orWhereLike('course', $term)
                    ->orWhereLike('curricular_unit', $term)
                    ->orWhereHas('teacher', fn ($query) => $query->whereLike('name', $term))))
                ->orderBy('name')
                ->orderBy('id')
                ->paginate(12),
        ]);
    }

    private function user(): User
    {
        /** @var User */
        return Auth::user();
    }
}
