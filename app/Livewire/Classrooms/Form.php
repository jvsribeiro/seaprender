<?php

namespace App\Livewire\Classrooms;

use App\Actions\Classrooms\CreateClassroom;
use App\Actions\Classrooms\UpdateClassroom;
use App\Models\Classroom;
use App\Models\User;
use App\Support\ClassroomTheme;
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

    public string $theme_palette = 'classic';

    public string $theme_pattern = 'grid';

    public string $theme_base = '#004C94';

    public string $theme_accent = '#F7941D';

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
        $theme = $classroom->theme();
        $this->theme_palette = $theme['palette'];
        $this->theme_pattern = $theme['pattern'];
        $this->theme_base = $theme['base'];
        $this->theme_accent = $theme['accent'];
    }

    public function updatedThemePalette(string $palette): void
    {
        if (isset(ClassroomTheme::PRESETS[$palette])) {
            $preset = ClassroomTheme::PRESETS[$palette];
            $this->theme_pattern = $preset['pattern'];
            $this->theme_base = $preset['base'];
            $this->theme_accent = $preset['accent'];
        }
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
            'theme_palette' => ['required', Rule::in([...array_keys(ClassroomTheme::PRESETS), 'custom'])],
            'theme_pattern' => ['required', Rule::in(array_keys(ClassroomTheme::PATTERNS))],
            'theme_base' => ['exclude_unless:theme_palette,custom', 'required', ClassroomTheme::COLOR_RULE],
            'theme_accent' => ['exclude_unless:theme_palette,custom', 'required', ClassroomTheme::COLOR_RULE],
        ];
    }

    public function save(CreateClassroom $createClassroom, UpdateClassroom $updateClassroom): void
    {
        /** @var User $user */
        $user = Auth::user();
        $classroom = $this->classroomId ? Classroom::findOrFail($this->classroomId) : null;

        Gate::authorize(...$classroom ? ['manage', $classroom] : ['create', Classroom::class]);

        $attributes = $this->validate();
        $attributes += ['theme_base' => null, 'theme_accent' => null];

        $classroom = $classroom
            ? $updateClassroom->handle($user, $classroom, $attributes)
            : $createClassroom->handle($user, $attributes);

        session()->flash('toast', 'Turma salva.');

        $this->redirectRoute('classrooms.show', $classroom, navigate: true);
    }

    public function render(): View
    {
        return view('livewire.classrooms.form', [
            'periods' => self::PERIODS,
            'palettes' => ClassroomTheme::PRESETS,
            'patterns' => ClassroomTheme::PATTERNS,
            'preview' => ClassroomTheme::resolve($this->theme_palette, $this->theme_pattern, $this->theme_base, $this->theme_accent),
        ]);
    }
}
