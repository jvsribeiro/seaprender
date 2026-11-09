<?php

namespace App\Livewire\Classrooms;

use App\Actions\Classrooms\JoinClassroom;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class JoinForm extends Component
{
    use RedirectsAfterEnrollment;

    public string $code = '';

    public function join(JoinClassroom $joinClassroom): void
    {
        $this->validate(['code' => ['required', 'string', 'max:20']], [], ['code' => 'código']);

        /** @var User $user */
        $user = Auth::user();
        $this->redirectAfterEnrollment($joinClassroom->handle($user, $this->code));
    }

    public function render(): View
    {
        return view('livewire.classrooms.join-form');
    }
}
