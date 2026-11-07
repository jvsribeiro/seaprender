<?php

namespace App\Actions\Users;

use App\Actions\RecordAuditLog;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ChangeUserRole
{
    public function __construct(private RecordAuditLog $recordAuditLog) {}

    /**
     * @throws ValidationException
     */
    public function handle(?User $actor, User $user, UserRole $role): void
    {
        if ($actor?->is($user)) {
            throw ValidationException::withMessages(['role' => 'Você não pode alterar o próprio perfil.']);
        }

        DB::transaction(function () use ($actor, $user, $role) {
            $previousRole = $user->role;

            $user->role = $role;
            $user->save();

            $this->recordAuditLog->handle($actor, 'user.role_changed', $user, [
                'from' => $previousRole->value,
                'to' => $role->value,
            ]);
        });
    }
}
