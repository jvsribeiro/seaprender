<?php

namespace App\Actions\Classrooms;

use App\Actions\RecordAuditLog;
use App\Enums\ClassroomStatus;
use App\Models\Classroom;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ChangeClassroomStatus
{
    public function __construct(private RecordAuditLog $recordAuditLog) {}

    public function handle(User $actor, Classroom $classroom, ClassroomStatus $status): void
    {
        DB::transaction(function () use ($actor, $classroom, $status) {
            $classroom->status = $status;
            $classroom->save();

            $this->recordAuditLog->handle($actor, 'classroom.status_changed', $classroom, ['status' => $status->value]);
        });
    }
}
