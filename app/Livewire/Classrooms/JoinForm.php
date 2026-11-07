<?php

namespace App\Livewire\Classrooms;

use App\Actions\Classrooms\JoinClassroom;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class JoinForm extends Component
{
    public string $code = '';

    public function join(JoinClassroom $joinClassroom): void
    {
        $this->validate(['code' => ['required', 'string', 'max:20']], [], ['code' => 'código']);

        /** @var User $user */
        $user = Auth::user();
        $enrollment = $joinClassroom->handle($user, $this->code);

        session()->flash('toast', 'Você entrou na turma.');

        $this->redirectRoute('classrooms.show', $enrollment->classroom_id, navigate: true);
    }

    public function render(): View
    {
        return view('livewire.classrooms.join-form');
    }
}
