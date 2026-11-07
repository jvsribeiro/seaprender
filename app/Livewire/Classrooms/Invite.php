<?php

namespace App\Livewire\Classrooms;

use App\Actions\Classrooms\JoinClassroom;
use App\Enums\ClassroomStatus;
use App\Models\Classroom;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Convite de turma')]
class Invite extends Component
{
    #[Locked]
    public string $code = '';

    public function mount(string $code): void
    {
        $this->code = Str::upper(trim($code));
    }

    public function join(JoinClassroom $joinClassroom): void
    {
        /** @var User $user */
        $user = Auth::user();
        $enrollment = $joinClassroom->handle($user, $this->code);

        session()->flash('toast', 'Você entrou na turma.');

        $this->redirectRoute('classrooms.show', $enrollment->classroom_id, navigate: true);
    }

    public function render(): View
    {
        return view('livewire.classrooms.invite', [
            'classroom' => Classroom::with('teacher:id,name')
                ->where('enrollment_code', $this->code)
                ->where('status', ClassroomStatus::Active)
                ->first(),
        ]);
    }
}
