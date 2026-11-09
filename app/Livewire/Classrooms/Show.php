<?php

namespace App\Livewire\Classrooms;

use App\Actions\Classrooms\ChangeClassroomStatus;
use App\Actions\Classrooms\EnrollStudent;
use App\Actions\Classrooms\ImportStudents;
use App\Actions\Classrooms\RegenerateEnrollmentCode;
use App\Actions\Classrooms\RemoveStudent;
use App\Actions\Classrooms\ReviewEnrollment;
use App\Actions\RecordAuditLog;
use App\Actions\Users\IssueTemporaryPassword;
use App\Enums\ClassroomStatus;
use App\Models\Classroom;
use App\Models\User;
use App\Support\ClassroomProgress;
use Illuminate\Contracts\View\View;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Title('Turma')]
class Show extends Component
{
    use WithFileUploads, WithPagination;

    #[Locked]
    public Classroom $classroom;

    #[Url(as: 'aba', except: 'mural')]
    public string $tab = 'mural';

    public ?string $temporaryPassword = null;

    public ?string $temporaryPasswordOwner = null;

    public string $studentName = '';

    public string $studentEmail = '';

    /** @var UploadedFile|null */
    public $csv = null;

    /** @var array{enrolled: int, skipped: int, errors: list<string>}|null */
    public ?array $importResult = null;

    public function mount(Classroom $classroom, RecordAuditLog $recordAuditLog): void
    {
        Gate::authorize('view', $classroom);

        $this->classroom = $classroom;

        $user = $this->user();

        if ($user->isStudent() && Cache::add("classroom-viewed:{$user->id}:{$classroom->id}", true, now()->addHour())) {
            $recordAuditLog->handle($user, 'classroom.viewed', $classroom, ['title' => $classroom->name]);
        }
    }

    public function addStudent(EnrollStudent $enrollStudent): void
    {
        $this->authorizeManagement();

        $this->validate([
            'studentName' => ['required', 'string', 'max:255'],
            'studentEmail' => ['required', 'string', 'email', 'max:255'],
        ], [], ['studentName' => 'nome', 'studentEmail' => 'e-mail']);

        $enrolled = $enrollStudent->handle($this->user(), $this->classroom, $this->studentName, $this->studentEmail);

        $this->reset('studentName', 'studentEmail');
        $this->dispatch('toast', message: $enrolled ? 'Aluno matriculado.' : 'Esse e-mail já está na turma.');
    }

    public function importStudents(ImportStudents $importStudents): void
    {
        $this->authorizeManagement();

        $this->validate(['csv' => ['required', 'file', 'mimes:csv,txt', 'max:1024']], [], ['csv' => 'arquivo']);

        $this->importResult = $importStudents->handle($this->user(), $this->classroom, $this->csv->getRealPath());

        $this->reset('csv');
    }

    public function removeStudent(int $enrollmentId, RemoveStudent $removeStudent): void
    {
        Gate::authorize('manage', $this->classroom);

        $removeStudent->handle($this->user(), $this->classroom->activeEnrollments()->findOrFail($enrollmentId));

        $this->dispatch('toast', message: 'Aluno removido da turma.');
    }

    public function reviewEnrollment(int $enrollmentId, bool $approve, ReviewEnrollment $review): void
    {
        $changed = $review->handle($this->user(), $this->classroom, $enrollmentId, $approve);

        $this->dispatch('toast', message: match (true) {
            ! $changed => 'Essa solicitação já foi analisada.',
            $approve => 'Solicitação aprovada. O aluno já pode abrir a turma.',
            default => 'Solicitação recusada.',
        });
    }

    public function resetStudentPassword(int $enrollmentId, IssueTemporaryPassword $issueTemporaryPassword): void
    {
        Gate::authorize('manage', $this->classroom);

        $student = $this->classroom->activeEnrollments()->findOrFail($enrollmentId)->user;

        abort_unless($student->isStudent(), 403);

        $this->temporaryPassword = $issueTemporaryPassword->handle($this->user(), $student);
        $this->temporaryPasswordOwner = $student->name;
    }

    public function clearTemporaryPassword(): void
    {
        $this->reset('temporaryPassword', 'temporaryPasswordOwner');
    }

    public function regenerateCode(RegenerateEnrollmentCode $regenerateEnrollmentCode): void
    {
        $this->authorizeManagement();

        $regenerateEnrollmentCode->handle($this->user(), $this->classroom);

        $this->dispatch('toast', message: 'Novo código gerado. O anterior parou de funcionar.');
    }

    public function toggleArchived(ChangeClassroomStatus $changeClassroomStatus): void
    {
        Gate::authorize('manage', $this->classroom);

        $status = $this->classroom->isActive() ? ClassroomStatus::Archived : ClassroomStatus::Active;

        $changeClassroomStatus->handle($this->user(), $this->classroom, $status);

        $this->dispatch('toast', message: $status === ClassroomStatus::Active ? 'Turma reativada.' : 'Turma arquivada.');
    }

    public function render(): View
    {
        Gate::authorize('view', $this->classroom);

        $canManage = Gate::allows('manage', $this->classroom);

        $tabs = [
            'mural' => 'Mural',
            'progresso' => 'Progresso',
            'quizzes' => 'Quizzes',
            'se-pergunta' => 'Se Pergunta',
            'loja' => 'Lojinha',
            ...($canManage ? ['ferramentas' => 'Ferramentas', 'alunos' => 'Alunos', 'acessos' => 'Acessos', 'auditoria' => 'Auditoria'] : []),
        ];
        $activeTab = array_key_exists($this->tab, $tabs) ? $this->tab : 'mural';

        return view('livewire.classrooms.show', [
            'canManage' => $canManage,
            'theme' => $this->classroom->theme(),
            'tabs' => $tabs,
            'activeTab' => $activeTab,
            'pendingCount' => $canManage ? $this->classroom->pendingEnrollments()->count() : 0,
            'requests' => $activeTab === 'alunos'
                ? $this->classroom->pendingEnrollments()->with('user:id,name,email')->oldest('id')->paginate(10, pageName: 'solicitacoes')
                : null,
            'progressTotals' => $activeTab === 'alunos' ? ClassroomProgress::totals($this->classroom) : null,
            'enrollments' => $activeTab === 'alunos'
                ? $this->classroom->activeEnrollments()
                    ->with(['user' => function ($relation) {
                        $students = $relation->getQuery()->select(['users.id', 'users.name', 'users.email']);
                        ClassroomProgress::addCounts($students, $this->classroom);
                    }])
                    ->join('users', 'users.id', '=', 'enrollments.user_id')
                    ->select('enrollments.*')
                    ->orderBy('users.name')
                    ->paginate(25)
                : null,
        ])->title($this->classroom->name);
    }

    /**
     * @throws ValidationException
     */
    private function authorizeManagement(): void
    {
        Gate::authorize('manage', $this->classroom);

        if (! $this->classroom->isActive()) {
            throw ValidationException::withMessages(['classroom' => 'Reative a turma para fazer esta alteração.']);
        }
    }

    private function user(): User
    {
        /** @var User */
        return Auth::user();
    }
}
