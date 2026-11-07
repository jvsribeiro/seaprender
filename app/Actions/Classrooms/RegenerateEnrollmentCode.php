<?php

namespace App\Actions\Classrooms;

use App\Actions\RecordAuditLog;
use App\Models\Classroom;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RegenerateEnrollmentCode
{
    public function __construct(
        private GenerateEnrollmentCode $generateEnrollmentCode,
        private RecordAuditLog $recordAuditLog,
    ) {}

    public function handle(User $actor, Classroom $classroom): void
    {
        DB::transaction(function () use ($actor, $classroom) {
            $classroom->enrollment_code = $this->generateEnrollmentCode->handle();
            $classroom->save();

            $this->recordAuditLog->handle($actor, 'classroom.code_regenerated', $classroom);
        });
    }
}
