<?php

namespace App\Actions\Classrooms;

use App\Actions\RecordAuditLog;
use App\Enums\EnrollmentStatus;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class EnrollStudent
{
    public function __construct(private RecordAuditLog $recordAuditLog) {}

    /**
     * Enroll a student by e-mail. New accounts get a random password, so the
     * student has to prove ownership of the address through the password reset.
     *
     * @return bool Whether the student became active in the classroom.
     *
     * @throws ValidationException
     */
    public function handle(User $actor, Classroom $classroom, string $name, string $email): bool
    {
        return DB::transaction(function () use ($actor, $classroom, $name, $email) {
            $classroom = Classroom::query()->lockForUpdate()->findOrFail($classroom->id);
            if (! $classroom->isActive()) {
                throw ValidationException::withMessages(['classroom' => 'Reative a turma para matricular alunos.']);
            }

            $student = User::firstOrCreate(
                ['email' => Str::lower($email)],
                ['name' => $name, 'password' => Str::password(32)],
            );

            if (! $student->isStudent()) {
                throw ValidationException::withMessages(['studentEmail' => 'Esse e-mail é de uma conta de professor ou da administração.']);
            }

            if ($student->id === $classroom->teacher_id) {
                return false;
            }

            $enrollment = Enrollment::firstOrNew([
                'classroom_id' => $classroom->id,
                'user_id' => $student->id,
            ]);

            if ($enrollment->exists && $enrollment->isActive()) {
                return false;
            }

            $wasPending = $enrollment->status === EnrollmentStatus::Pending;
            $enrollment->status = EnrollmentStatus::Active;
            $enrollment->save();

            $this->recordAuditLog->handle($actor, $wasPending ? 'enrollment.approved' : 'enrollment.added', $enrollment);

            return true;
        });
    }
}
