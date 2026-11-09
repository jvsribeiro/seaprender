<?php

namespace App\Livewire\Classrooms;

use App\Models\Enrollment;

trait RedirectsAfterEnrollment
{
    private function redirectAfterEnrollment(Enrollment $enrollment): void
    {
        if ($enrollment->isActive()) {
            $this->redirectRoute('classrooms.show', $enrollment->classroom_id, navigate: true);

            return;
        }

        session()->flash('toast', 'Solicitação enviada. Aguarde a aprovação do professor.');
        $this->redirectRoute('classrooms.browse', navigate: true);
    }
}
