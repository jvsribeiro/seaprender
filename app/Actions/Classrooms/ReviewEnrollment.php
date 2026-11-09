<?php

namespace App\Actions\Classrooms;

use App\Actions\RecordAuditLog;
use App\Enums\EnrollmentStatus;
use App\Models\Classroom;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ReviewEnrollment
{
    public function __construct(private RecordAuditLog $recordAuditLog) {}

    public function handle(User $teacher, Classroom $classroom, int $enrollmentId, bool $approve): bool
    {
        Gate::forUser($teacher)->authorize('manage', $classroom);

        return DB::transaction(function () use ($teacher, $classroom, $enrollmentId, $approve) {
            $classroom = Classroom::query()->lockForUpdate()->findOrFail($classroom->id);
            Gate::forUser($teacher)->authorize('manage', $classroom);

            if (! $classroom->isActive()) {
                throw ValidationException::withMessages(['classroom' => 'Reative a turma para analisar as solicitações.']);
            }

            $enrollment = $classroom->enrollments()->lockForUpdate()->findOrFail($enrollmentId);
            if ($enrollment->status !== EnrollmentStatus::Pending) {
                return false;
            }

            if ($approve && ! $enrollment->user->isStudent()) {
                throw ValidationException::withMessages(['classroom' => 'A conta solicitante precisa ter o perfil de aluno.']);
            }

            $enrollment->status = $approve ? EnrollmentStatus::Active : EnrollmentStatus::Rejected;
            $enrollment->save();

            $this->recordAuditLog->handle($teacher, $approve ? 'enrollment.approved' : 'enrollment.rejected', $enrollment);

            return true;
        });
    }
}
