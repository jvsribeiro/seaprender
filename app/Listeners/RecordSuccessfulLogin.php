<?php

namespace App\Listeners;

use App\Actions\RecordAuditLog;
use App\Models\User;
use Illuminate\Auth\Events\Login;

class RecordSuccessfulLogin
{
    public function __construct(private RecordAuditLog $recordAuditLog) {}

    public function handle(Login $event): void
    {
        if ($event->user instanceof User) {
            $this->recordAuditLog->handle($event->user, 'auth.login');
        }
    }
}
