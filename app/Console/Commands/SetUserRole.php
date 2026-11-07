<?php

namespace App\Console\Commands;

use App\Actions\Users\ChangeUserRole;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;

class SetUserRole extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:set-user-role {email} {role : admin, teacher ou student}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Define o perfil de um usuário';

    /**
     * Execute the console command.
     */
    public function handle(ChangeUserRole $changeUserRole): int
    {
        $user = User::where('email', $this->argument('email'))->first();
        $role = UserRole::tryFrom($this->argument('role'));

        if (! $user || ! $role) {
            $this->error('Usuário ou perfil não encontrado.');

            return self::FAILURE;
        }

        $changeUserRole->handle(null, $user, $role);
        $this->info("{$user->email} agora é {$role->label()}.");

        return self::SUCCESS;
    }
}
