<?php

namespace App\Livewire\Admin;

use App\Actions\Users\ChangeUserRole;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Enum;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Usuários')]
class Users extends Component
{
    use WithPagination;

    #[Url(as: 'busca', except: '')]
    public string $search = '';

    #[Url(as: 'sem-acesso', except: false)]
    public bool $neverAccessed = false;

    public function updated(): void
    {
        $this->resetPage();
    }

    public function changeRole(int $userId, string $role, ChangeUserRole $changeUserRole): void
    {
        Gate::authorize('administer');

        Validator::make(['role' => $role], ['role' => ['required', new Enum(UserRole::class)]])->validate();

        /** @var User $actor */
        $actor = Auth::user();

        $changeUserRole->handle($actor, User::findOrFail($userId), UserRole::from($role));

        $this->dispatch('toast', message: 'Perfil alterado.');
    }

    public function render(): View
    {
        Gate::authorize('administer');

        return view('livewire.admin.users', [
            'roles' => UserRole::cases(),
            'users' => User::query()
                ->when($this->neverAccessed, fn ($query) => $query->whereNull('last_seen_at'))
                ->when($this->search !== '', fn ($query) => $query->where(fn ($query) => $query
                    ->whereLike('name', "%{$this->search}%")
                    ->orWhereLike('email', "%{$this->search}%")))
                ->orderBy('name')
                ->orderBy('id')
                ->paginate(25),
        ]);
    }
}
