<?php

namespace App\Livewire\Account;

use App\Actions\RecordAuditLog;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Trocar senha')]
class ChangePassword extends Component
{
    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function save(RecordAuditLog $recordAuditLog): void
    {
        $user = $this->user();

        $this->validate([
            'current_password' => $user->must_change_password ? ['nullable'] : ['required', 'current_password:web'],
            'password' => ['required', 'string', Password::default(), 'confirmed'],
        ], [], ['current_password' => 'senha atual', 'password' => 'nova senha']);

        $user->password = $this->password;
        $user->must_change_password = false;
        $user->setRememberToken(Str::random(60));
        $user->save();

        DB::table('sessions')->where('user_id', $user->id)->where('id', '!=', session()->getId())->delete();

        $recordAuditLog->handle($user, 'user.password_changed');

        session()->flash('toast', 'Senha alterada.');

        $this->redirectRoute('dashboard', navigate: true);
    }

    public function render(): View
    {
        return view('livewire.account.change-password', ['mustChange' => $this->user()->must_change_password]);
    }

    private function user(): User
    {
        /** @var User */
        return Auth::user();
    }
}
