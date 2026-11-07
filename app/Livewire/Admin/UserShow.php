<?php

namespace App\Livewire\Admin;

use App\Actions\Users\DeleteUser;
use App\Actions\Users\IssueTemporaryPassword;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Usuário')]
class UserShow extends Component
{
    use WithPagination;

    #[Locked]
    public User $user;

    public ?string $temporaryPassword = null;

    public function mount(User $user): void
    {
        Gate::authorize('administer');

        $this->user = $user;
    }

    public function issueTemporaryPassword(IssueTemporaryPassword $issueTemporaryPassword): void
    {
        Gate::authorize('administer');

        $this->temporaryPassword = $issueTemporaryPassword->handle($this->actor(), $this->user);
    }

    public function clearTemporaryPassword(): void
    {
        $this->temporaryPassword = null;
    }

    public function sendResetLink(): void
    {
        Gate::authorize('administer');

        Password::sendResetLink(['email' => $this->user->email]);

        $this->dispatch('toast', message: 'Link enviado para o e-mail da conta.');
    }

    public function deleteUser(DeleteUser $deleteUser): void
    {
        Gate::authorize('administer');

        $deleteUser->handle($this->actor(), $this->user);

        session()->flash('toast', 'Conta excluída.');

        $this->redirectRoute('admin.users', navigate: true);
    }

    public function render(): View
    {
        Gate::authorize('administer');

        return view('livewire.admin.user-show', [
            'classrooms' => $this->user->enrolledClassrooms()->orderBy('name')->get(),
            'taughtClassrooms' => $this->user->taughtClassrooms()->orderBy('name')->get(),
            'viewedPostsCount' => $this->user->viewedPosts()->count(),
            'answeredQuizzesCount' => $this->user->quizAttempts()->count(),
            'labels' => AuditLogs::LABELS,
            'logs' => AuditLog::where('actor_id', $this->user->id)->latest('id')->paginate(15),
        ])->title($this->user->name);
    }

    private function actor(): User
    {
        /** @var User */
        return Auth::user();
    }
}
