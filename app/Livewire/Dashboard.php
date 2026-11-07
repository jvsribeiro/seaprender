<?php

namespace App\Livewire;

use App\Enums\ClassroomStatus;
use App\Enums\EnrollmentStatus;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Painel')]
class Dashboard extends Component
{
    public function render(): View
    {
        /** @var User $user */
        $user = Auth::user();

        $visibleClassrooms = Classroom::visibleTo($user);

        $activeClassroomsCount = (clone $visibleClassrooms)->where('status', ClassroomStatus::Active)->count();

        $studentsCount = Enrollment::where('status', EnrollmentStatus::Active)
            ->whereIn('classroom_id', (clone $visibleClassrooms)->select('id'))
            ->distinct('user_id')
            ->count('user_id');

        return view('livewire.dashboard', [
            'user' => $user,
            'classrooms' => (clone $visibleClassrooms)
                ->with('teacher:id,name')
                ->withCount('activeEnrollments')
                ->where('status', ClassroomStatus::Active)
                ->latest('id')
                ->limit(6)
                ->get(),
            'recentPosts' => Post::whereIn('classroom_id', (clone $visibleClassrooms)->select('id'))
                ->with('classroom:id,name')
                ->latest('id')
                ->limit(5)
                ->get(),
            'activeClassroomsCount' => $activeClassroomsCount,
            'studentsCount' => $studentsCount,
            'usersCount' => $user->isAdmin() ? User::count() : 0,
            'firstSteps' => $this->firstSteps($user, $activeClassroomsCount, $studentsCount),
        ]);
    }

    /**
     * The getting-started checklist, which disappears once everything is done.
     *
     * @return list<array{label: string, done: bool}>
     */
    private function firstSteps(User $user, int $classroomsCount, int $studentsCount): array
    {
        $steps = match (true) {
            $user->isAdmin() => [],
            $user->isTeacher() => [
                ['label' => 'Criar a primeira turma', 'done' => $classroomsCount > 0],
                ['label' => 'Receber o primeiro aluno', 'done' => $studentsCount > 0],
            ],
            default => [
                ['label' => 'Criar a conta', 'done' => true],
                ['label' => 'Entrar em uma turma', 'done' => $classroomsCount > 0],
            ],
        };

        return collect($steps)->every('done') ? [] : $steps;
    }
}
