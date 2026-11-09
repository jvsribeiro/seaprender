<?php

namespace App\Livewire\Classrooms;

use App\Actions\Posts\PublishPost;
use App\Actions\Quizzes\RunLiveQuiz;
use App\Actions\RecordAuditLog;
use App\Enums\EnrollmentStatus;
use App\Enums\PostType;
use App\Enums\UserRole;
use App\Models\Classroom;
use App\Models\LiveQuiz;
use App\Models\User;
use App\Support\ClassroomDisplayName;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Tools extends Component
{
    #[Locked]
    public Classroom $classroom;

    #[Locked]
    public array $drawnIds = [];

    #[Locked]
    public ?string $drawnName = null;

    #[Locked]
    public array $groups = [];

    #[Locked]
    public ?string $drawKey = null;

    #[Locked]
    public ?string $groupsKey = null;

    #[Locked]
    public ?int $drawPostId = null;

    #[Locked]
    public ?int $groupsPostId = null;

    public string $quizId = '';

    public string $seconds = '30';

    public string $reflectionTitle = '';

    public string $reflectionInstructions = '';

    public string $pollQuestion = '';

    public string $pollOptions = '';

    public string $groupCount = '2';

    public function mount(Classroom $classroom): void
    {
        $this->classroom = $classroom;
    }

    public function openQuiz(RunLiveQuiz $runner): void
    {
        $this->authorizeActiveClass();

        $data = $this->validate([
            'quizId' => ['required', 'integer', 'min:1'],
            'seconds' => ['required', 'integer', 'between:10,120'],
        ], [], ['quizId' => 'quiz', 'seconds' => 'tempo por pergunta']);

        $room = $runner->open($this->user(), $this->classroom, (int) $data['quizId'], (int) $data['seconds']);

        $this->redirect(route('quizzes.live', $room), navigate: true);
    }

    public function publishReflection(PublishPost $publishPost): void
    {
        $this->authorizeActiveClass();

        $data = $this->validate([
            'reflectionTitle' => ['required', 'string', 'max:160'],
            'reflectionInstructions' => ['nullable', 'string', 'max:500'],
        ], [], ['reflectionTitle' => 'título', 'reflectionInstructions' => 'orientação']);

        $publishPost->handle($this->user(), $this->classroom, [
            'type' => PostType::Reflection,
            'title' => $data['reflectionTitle'],
            'body' => $data['reflectionInstructions'] ?: null,
        ]);

        $this->dispatch('toast', message: 'Reflexão publicada no mural.');
        $this->redirect(route('classrooms.show', $this->classroom), navigate: true);
    }

    public function publishPoll(PublishPost $publishPost): void
    {
        $this->authorizeActiveClass();

        $data = $this->validate([
            'pollQuestion' => ['required', 'string', 'max:160'],
            'pollOptions' => ['required', 'string', 'max:800'],
        ], [], ['pollQuestion' => 'pergunta', 'pollOptions' => 'alternativas']);

        $options = collect(preg_split('/\R/u', $data['pollOptions']))
            ->map(fn (string $option) => trim($option))->filter(fn (string $option) => $option !== '')->values();

        if ($options->count() < 2 || $options->count() > 6
            || $options->unique(fn (string $option) => mb_strtolower($option))->count() !== $options->count()
            || $options->contains(fn (string $option) => mb_strlen($option) > 120)) {
            throw ValidationException::withMessages(['pollOptions' => 'Informe de 2 a 6 alternativas diferentes, uma por linha, com até 120 caracteres cada.']);
        }

        $publishPost->handle($this->user(), $this->classroom, [
            'type' => PostType::Poll,
            'title' => $data['pollQuestion'],
            'poll_options' => $options->all(),
        ]);

        $this->dispatch('toast', message: 'Votação publicada no mural.');
        $this->redirect(route('classrooms.show', $this->classroom), navigate: true);
    }

    public function drawStudent(RecordAuditLog $recordAuditLog): void
    {
        $this->authorizeActiveClass();

        $student = $this->students()->whereNotIn('id', $this->drawnIds)->inRandomOrder()->first(['id', 'name']);
        if ($student === null) {
            throw ValidationException::withMessages(['draw' => 'Não há alunos disponíveis neste ciclo de sorteios.']);
        }

        $this->drawnIds[] = $student->id;
        $this->drawnName = $student->name;
        $this->drawKey = (string) Str::uuid();
        $this->drawPostId = null;
        $recordAuditLog->handle($this->user(), 'lesson.drawn', $this->classroom, ['student' => $student->name]);
    }

    public function resetDraw(): void
    {
        $this->authorizeActiveClass();
        $this->reset('drawnIds', 'drawnName', 'drawKey', 'drawPostId');
        $this->resetErrorBag('draw');
    }

    public function drawGroups(RecordAuditLog $recordAuditLog): void
    {
        $this->authorizeActiveClass();

        $data = $this->validate(['groupCount' => ['required', 'integer', 'between:2,20']], [], ['groupCount' => 'número de grupos']);
        $count = (int) $data['groupCount'];
        $names = $this->students()->inRandomOrder()->pluck('name');

        if ($names->count() < $count) {
            throw ValidationException::withMessages(['groupCount' => 'Escolha um número de grupos menor ou igual ao número de alunos matriculados.']);
        }

        $groups = array_fill(0, $count, []);
        foreach ($names as $index => $name) {
            $groups[$index % $count][] = $name;
        }

        $this->groups = $groups;
        $this->groupsKey = (string) Str::uuid();
        $this->groupsPostId = null;
        $recordAuditLog->handle($this->user(), 'lesson.teams_drawn', $this->classroom, ['title' => $count.' grupos · '.$names->count().' alunos']);
    }

    public function publishDraw(PublishPost $publisher): void
    {
        $this->authorizeActiveClass();
        abort_unless($this->drawKey && $this->drawnName, 422);

        $this->drawPostId = $publisher->handle($this->user(), $this->classroom, [
            'tool_key' => $this->drawKey,
            'type' => PostType::Draw,
            'title' => 'Resultado do sorteio',
            'body' => ClassroomDisplayName::abbreviate($this->drawnName).' foi sorteado(a).',
        ])->id;

        $this->dispatch('toast', message: 'Sorteio publicado no mural.');
    }

    public function publishGroups(PublishPost $publisher): void
    {
        $this->authorizeActiveClass();
        abort_unless($this->groupsKey && $this->groups, 422);

        $lines = [];
        foreach ($this->groups as $index => $group) {
            $lines[] = 'Grupo '.($index + 1).' · '.implode(', ', array_map(ClassroomDisplayName::abbreviate(...), $group));
        }

        $this->groupsPostId = $publisher->handle($this->user(), $this->classroom, [
            'tool_key' => $this->groupsKey,
            'type' => PostType::Groups,
            'title' => 'Grupos da aula',
            'body' => implode("\n", $lines),
        ])->id;

        $this->dispatch('toast', message: 'Grupos publicados no mural.');
    }

    public function render(RunLiveQuiz $runner): View
    {
        Gate::authorize('manage', $this->classroom);

        return view('livewire.classrooms.tools', [
            'activeRoom' => $runner->activeRoom($this->classroom),
            'quizzes' => $this->classroom->quizzes()->has('questions')->latest('id')->get(['id', 'title']),
            'finishedRooms' => LiveQuiz::query()->where('classroom_id', $this->classroom->id)
                ->where('phase', LiveQuiz::FINISHED)->latest('id')->limit(5)->get(['id', 'title', 'updated_at']),
            'remainingStudents' => $this->students()->whereNotIn('id', $this->drawnIds)->count(),
        ]);
    }

    /** @return Builder<User> */
    private function students(): Builder
    {
        return User::query()->where('role', UserRole::Student)->whereHas('enrollments', fn ($query) => $query
            ->where('classroom_id', $this->classroom->id)->where('status', EnrollmentStatus::Active));
    }

    private function authorizeActiveClass(): void
    {
        Gate::authorize('manage', $this->classroom);
        if (! $this->classroom->refresh()->isActive()) {
            throw ValidationException::withMessages(['tools' => 'Reative a turma para usar as ferramentas de aula.']);
        }
    }

    private function user(): User
    {
        /** @var User */
        return Auth::user();
    }
}
