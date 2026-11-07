<?php

namespace App\Actions\Classrooms;

use App\Actions\RecordAuditLog;
use App\Enums\EnrollmentStatus;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EnrollStudent
{
    public function __construct(private RecordAuditLog $recordAuditLog) {}

    /**
     * Enroll a student by e-mail. New accounts get a random password, so the
     * student has to prove ownership of the address through the password reset.
     *
     * @return bool Whether the student became active in the classroom.
     */
    public function handle(User $actor, Classroom $classroom, string $name, string $email): bool
    {
        return DB::transaction(function () use ($actor, $classroom, $name, $email) {
            $student = User::firstOrCreate(
                ['email' => Str::lower($email)],
                ['name' => $name, 'password' => Str::password(32)],
            );

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

            $enrollment->status = EnrollmentStatus::Active;
            $enrollment->save();

            $this->recordAuditLog->handle($actor, 'enrollment.added', $enrollment);

            return true;
        });
    }
}
