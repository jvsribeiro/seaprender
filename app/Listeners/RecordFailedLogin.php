<?php

namespace App\Listeners;

use App\Actions\RecordAuditLog;
use App\Models\User;
use Illuminate\Auth\Events\Failed;

class RecordFailedLogin
{
    public function __construct(private RecordAuditLog $recordAuditLog) {}

    public function handle(Failed $event): void
    {
        if ($event->user instanceof User) {
            $this->recordAuditLog->handle($event->user, 'auth.failed');
        }
    }
}
