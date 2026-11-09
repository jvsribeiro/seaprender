<?php

namespace App\Livewire\Classrooms;

use App\Models\AuditLog;
use App\Models\Classroom;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

class Audit extends Component
{
    use WithPagination;

    private const CATEGORIES = [
        'classroom' => ['Turma', 'book'],
        'enrollment' => ['Matrículas', 'users'],
        'post' => ['Mural', 'scroll'],
        'quiz' => ['Quizzes', 'star'],
        'live_quiz' => ['Ao vivo', 'trophy'],
        'lesson' => ['Ferramentas', 'lens'],
        'reward' => ['Lojinha', 'coin'],
    ];

    #[Locked]
    public Classroom $classroom;

    public string $category = '';

    public function mount(Classroom $classroom): void
    {
        $this->classroom = $classroom;
    }

    public function updatedCategory(): void
    {
        $this->resetPage('auditoria');
    }

    public function render(): View
    {
        Gate::authorize('manage', $this->classroom);

        return view('livewire.classrooms.audit', [
            'categories' => self::CATEGORIES,
            'labels' => AuditLog::LABELS,
            'logs' => AuditLog::query()->where('classroom_id', $this->classroom->id)->with('actor:id,name')
                ->when(isset(self::CATEGORIES[$this->category]), fn ($query) => $query->where('action', 'like', $this->category.'.%'))
                ->latest('id')->paginate(20, pageName: 'auditoria'),
        ]);
    }
}
