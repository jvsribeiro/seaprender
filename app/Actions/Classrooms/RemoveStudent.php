<?php

namespace App\Actions\Classrooms;

use App\Actions\RecordAuditLog;
use App\Enums\EnrollmentStatus;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RemoveStudent
{
    public function __construct(private RecordAuditLog $recordAuditLog) {}

    public function handle(User $actor, Enrollment $enrollment): void
    {
        DB::transaction(function () use ($actor, $enrollment) {
            $enrollment->status = EnrollmentStatus::Removed;
            $enrollment->save();

            $this->recordAuditLog->handle($actor, 'enrollment.removed', $enrollment);
        });
    }
}
