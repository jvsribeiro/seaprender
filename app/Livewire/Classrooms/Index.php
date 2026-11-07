<?php

namespace App\Livewire\Classrooms;

use App\Models\Classroom;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Turmas')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'busca', except: '')]
    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        /** @var User $user */
        $user = Auth::user();

        return view('livewire.classrooms.index', [
            'classrooms' => Classroom::visibleTo($user)
                ->with('teacher:id,name')
                ->withCount('activeEnrollments')
                ->when($this->search !== '', fn ($query) => $query->whereLike('name', "%{$this->search}%"))
                ->orderBy('status')
                ->orderByDesc('id')
                ->paginate(12),
        ]);
    }
}
