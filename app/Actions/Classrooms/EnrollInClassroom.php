<?php

namespace App\Actions\Classrooms;

use App\Actions\RecordAuditLog;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class EnrollInClassroom
{
    public function __construct(private RecordAuditLog $recordAuditLog) {}

    /**
     * @throws ValidationException
     */
    public function handle(User $user, Classroom $classroom): Enrollment
    {
        if (! $classroom->isActive()) {
            $this->fail('Esta turma não está aceitando inscrições.');
        }

        if ($classroom->teacher_id === $user->id) {
            $this->fail('Você é o professor desta turma.');
        }

        if (! $user->isStudent()) {
            $this->fail('Só contas de aluno podem se inscrever em turmas.');
        }

        $enrollment = Enrollment::createOrFirst([
            'classroom_id' => $classroom->id,
            'user_id' => $user->id,
        ]);

        if (! $enrollment->wasRecentlyCreated && ! $enrollment->isActive()) {
            $this->fail('Sua matrícula nesta turma foi encerrada. Fale com o professor.');
        }

        if ($enrollment->wasRecentlyCreated) {
            $this->recordAuditLog->handle($user, 'enrollment.joined', $enrollment);
        }

        return $enrollment;
    }

    /**
     * @throws ValidationException
     */
    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['code' => $message]);
    }
}
