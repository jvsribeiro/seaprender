<?php

namespace App\Actions\Users;

use App\Actions\RecordAuditLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteUser
{
    public function __construct(private RecordAuditLog $recordAuditLog) {}

    /**
     * @throws ValidationException
     */
    public function handle(User $actor, User $user): void
    {
        if ($actor->is($user)) {
            throw ValidationException::withMessages(['user' => 'Você não pode excluir a própria conta.']);
        }

        if ($user->taughtClassrooms()->exists()) {
            throw ValidationException::withMessages(['user' => 'Esta conta é responsável por turmas e não pode ser excluída.']);
        }

        DB::transaction(function () use ($actor, $user) {
            $this->recordAuditLog->handle($actor, 'user.deleted', null, ['name' => $user->name, 'role' => $user->role->value]);

            DB::table('sessions')->where('user_id', $user->id)->delete();
            $user->enrollments()->delete();
            $user->delete();
        });
    }
}
