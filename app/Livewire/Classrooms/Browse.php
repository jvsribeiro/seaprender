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
    use RedirectsAfterEnrollment, WithPagination;

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
        abort_unless(config('app.classroom_open_enrollment'), 403);

        $classroom = Classroom::findOrFail($classroomId);

        $this->redirectAfterEnrollment($enrollInClassroom->handle($this->user(), $classroom));
    }

    public function render(): View
    {
        Gate::authorize('enroll', Classroom::class);

        $user = $this->user();
        $term = "%{$this->search}%";
        $openEnrollment = config('app.classroom_open_enrollment');

        return view('livewire.classrooms.browse', [
            'openEnrollment' => $openEnrollment,
            'classrooms' => Classroom::query()
                ->with(['teacher:id,name', 'enrollments' => fn ($query) => $query->where('user_id', $user->id)])
                ->withCount('activeEnrollments')
                ->where('status', ClassroomStatus::Active)
                ->when(! $openEnrollment, fn ($query) => $query
                    ->whereHas('enrollments', fn ($enrollments) => $enrollments->where('user_id', $user->id)))
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
