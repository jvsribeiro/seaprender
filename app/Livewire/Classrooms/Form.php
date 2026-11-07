<?php

namespace App\Livewire\Classrooms;

use App\Actions\Classrooms\CreateClassroom;
use App\Actions\Classrooms\UpdateClassroom;
use App\Models\Classroom;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Turma')]
class Form extends Component
{
    public const PERIODS = ['Manhã', 'Tarde', 'Noite', 'Integral'];

    #[Locked]
    public ?int $classroomId = null;

    public string $name = '';

    public string $course = '';

    public string $curricular_unit = '';

    public string $period = 'Noite';

    public string $starts_on = '';

    public string $ends_on = '';

    public function mount(?Classroom $classroom = null): void
    {
        if (! $classroom?->exists) {
            Gate::authorize('create', Classroom::class);

            return;
        }

        Gate::authorize('manage', $classroom);

        $this->classroomId = $classroom->id;
        $this->name = $classroom->name;
        $this->course = $classroom->course;
        $this->curricular_unit = $classroom->curricular_unit;
        $this->period = $classroom->period;
        $this->starts_on = $classroom->starts_on->toDateString();
        $this->ends_on = $classroom->ends_on->toDateString();
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'course' => ['required', 'string', 'max:120'],
            'curricular_unit' => ['required', 'string', 'max:120'],
            'period' => ['required', Rule::in(self::PERIODS)],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after_or_equal:starts_on'],
        ];
    }

    public function save(CreateClassroom $createClassroom, UpdateClassroom $updateClassroom): void
    {
        /** @var User $user */
        $user = Auth::user();
        $classroom = $this->classroomId ? Classroom::findOrFail($this->classroomId) : null;

        Gate::authorize(...$classroom ? ['manage', $classroom] : ['create', Classroom::class]);

        $attributes = $this->validate();

        $classroom = $classroom
            ? $updateClassroom->handle($user, $classroom, $attributes)
            : $createClassroom->handle($user, $attributes);

        session()->flash('toast', 'Turma salva.');

        $this->redirectRoute('classrooms.show', $classroom, navigate: true);
    }

    public function render(): View
    {
        return view('livewire.classrooms.form', ['periods' => self::PERIODS]);
    }
}
