<?php

namespace App\Actions\Classrooms;

use App\Actions\RecordAuditLog;
use App\Models\Classroom;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateClassroom
{
    public function __construct(private RecordAuditLog $recordAuditLog) {}

    /**
     * @param  array{name: string, course: string, curricular_unit: string, period: string, starts_on: string, ends_on: string}  $attributes
     */
    public function handle(User $actor, Classroom $classroom, array $attributes): Classroom
    {
        return DB::transaction(function () use ($actor, $classroom, $attributes) {
            $classroom->update($attributes);

            $this->recordAuditLog->handle($actor, 'classroom.updated', $classroom, [
                'changed' => array_keys($classroom->getChanges()),
            ]);

            return $classroom;
        });
    }
}
