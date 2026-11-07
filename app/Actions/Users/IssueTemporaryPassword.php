<?php

namespace App\Actions\Users;

use App\Actions\RecordAuditLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class IssueTemporaryPassword
{
    public function __construct(private RecordAuditLog $recordAuditLog) {}

    /**
     * Replace the password, end the user's sessions and require a new password on the next login.
     *
     * @return string The password, shown once to whoever issued it.
     */
    public function handle(User $actor, User $user): string
    {
        $password = Str::password(10, symbols: false);

        $user->password = $password;
        $user->must_change_password = true;
        $user->remember_token = Str::random(60);
        $user->save();

        DB::table('sessions')->where('user_id', $user->id)->delete();

        $this->recordAuditLog->handle($actor, 'user.password_reset', $user);

        return $password;
    }
}
