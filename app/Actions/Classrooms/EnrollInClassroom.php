<?php

namespace App\Actions\Classrooms;

use App\Actions\RecordAuditLog;
use App\Enums\EnrollmentStatus;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EnrollInClassroom
{
    public function __construct(private RecordAuditLog $recordAuditLog) {}

    /**
     * @throws ValidationException
     */
    public function handle(User $user, Classroom $classroom): Enrollment
    {
        if ($classroom->teacher_id === $user->id) {
            $this->fail('Você é o professor desta turma.');
        }

        if (! $user->isStudent()) {
            $this->fail('Só contas de aluno podem se inscrever em turmas.');
        }

        return DB::transaction(function () use ($user, $classroom) {
            $classroom = Classroom::query()->lockForUpdate()->findOrFail($classroom->id);
            if (! $classroom->isActive()) {
                $this->fail('Esta turma não está aceitando inscrições.');
            }

            $enrollment = $classroom->enrollments()->firstOrCreate(
                ['user_id' => $user->id],
                ['status' => EnrollmentStatus::Pending],
            );

            if ($enrollment->status === EnrollmentStatus::Removed) {
                $this->fail('Sua matrícula nesta turma foi encerrada. Fale com o professor.');
            }

            if ($enrollment->status === EnrollmentStatus::Rejected) {
                $this->fail('Sua solicitação foi recusada. Fale com o professor.');
            }

            if ($enrollment->wasRecentlyCreated) {
                $this->recordAuditLog->handle($user, 'enrollment.requested', $enrollment);
            }

            return $enrollment;
        });
    }

    /**
     * @throws ValidationException
     */
    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['code' => $message]);
    }
}
