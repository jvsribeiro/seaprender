<?php

namespace App\Actions\Classrooms;

use App\Actions\RecordAuditLog;
use App\Models\Classroom;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateClassroom
{
    public function __construct(
        private GenerateEnrollmentCode $generateEnrollmentCode,
        private RecordAuditLog $recordAuditLog,
    ) {}

    /**
     * @param  array{name: string, course: string, curricular_unit: string, period: string, starts_on: string, ends_on: string}  $attributes
     */
    public function handle(User $teacher, array $attributes): Classroom
    {
        return DB::transaction(function () use ($teacher, $attributes) {
            $classroom = new Classroom($attributes);
            $classroom->teacher_id = $teacher->id;
            $classroom->enrollment_code = $this->generateEnrollmentCode->handle();
            $classroom->save();

            $this->recordAuditLog->handle($teacher, 'classroom.created', $classroom);

            return $classroom;
        });
    }
}
