<?php

namespace App\Policies;

use App\Models\Classroom;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ClassroomPolicy
{
    /**
     * Administrators may perform every classroom action.
     */
    public function before(User $user): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function view(User $user, Classroom $classroom): Response
    {
        return $this->teaches($user, $classroom) || $this->attends($user, $classroom)
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    public function create(User $user): bool
    {
        return $user->isTeacher();
    }

    public function enroll(User $user): bool
    {
        return $user->isStudent();
    }

    public function manage(User $user, Classroom $classroom): Response
    {
        if ($this->teaches($user, $classroom)) {
            return Response::allow();
        }

        return $this->attends($user, $classroom) ? Response::deny() : Response::denyAsNotFound();
    }

    private function teaches(User $user, Classroom $classroom): bool
    {
        return $user->isTeacher() && $classroom->teacher_id === $user->id;
    }

    private function attends(User $user, Classroom $classroom): bool
    {
        return $classroom->activeEnrollments()->where('user_id', $user->id)->exists();
    }
}
