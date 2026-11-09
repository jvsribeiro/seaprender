<?php

namespace App\Actions;

use App\Models\AuditLog;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\LiveQuiz;
use App\Models\Post;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class RecordAuditLog
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function handle(?User $actor, string $action, ?Model $subject = null, array $metadata = []): AuditLog
    {
        if ($subject instanceof Enrollment) {
            $metadata += ['student' => $subject->user?->name];
        }

        return AuditLog::create([
            'actor_id' => $actor?->id,
            'classroom_id' => match (true) {
                $subject instanceof Classroom => $subject->id,
                $subject instanceof Enrollment, $subject instanceof LiveQuiz, $subject instanceof Post, $subject instanceof Quiz => $subject->classroom_id,
                default => null,
            },
            'action' => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'metadata' => $metadata ?: null,
            'ip_address' => request()->ip(),
        ]);
    }
}
