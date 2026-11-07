<?php

namespace App\Livewire\Admin;

use App\Models\AuditLog;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Auditoria')]
class AuditLogs extends Component
{
    use WithPagination;

    public const LABELS = [
        'auth.login' => 'Entrou no sistema',
        'auth.failed' => 'Errou a senha',
        'classroom.viewed' => 'Abriu a turma',
        'classroom.created' => 'Turma criada',
        'classroom.updated' => 'Turma editada',
        'classroom.status_changed' => 'Situação da turma alterada',
        'classroom.code_regenerated' => 'Código da turma trocado',
        'enrollment.joined' => 'Aluno entrou na turma',
        'enrollment.added' => 'Aluno matriculado',
        'enrollment.removed' => 'Aluno removido',
        'enrollment.imported' => 'Importação de alunos',
        'post.published' => 'Publicou no mural',
        'post.removed' => 'Removeu do mural',
        'post.downloaded' => 'Baixou um arquivo',
        'quiz.saved' => 'Salvou um quiz',
        'quiz.published' => 'Publicou um quiz',
        'quiz.answered' => 'Respondeu um quiz',
        'quiz.removed' => 'Excluiu um quiz',
        'user.role_changed' => 'Perfil alterado',
        'user.password_reset' => 'Senha temporária gerada',
        'user.password_changed' => 'Trocou a senha',
        'user.deleted' => 'Conta excluída',
    ];

    #[Url(as: 'acao', except: '')]
    public string $action = '';

    #[Url(as: 'pessoa', except: '')]
    public string $person = '';

    public function updated(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        Gate::authorize('administer');

        return view('livewire.admin.audit-logs', [
            'labels' => self::LABELS,
            'logs' => AuditLog::with('actor:id,name')
                ->when(array_key_exists($this->action, self::LABELS), fn ($query) => $query->where('action', $this->action))
                ->when($this->person !== '', fn ($query) => $query->whereHas('actor', fn ($query) => $query->whereLike('name', "%{$this->person}%")))
                ->latest('id')
                ->paginate(25),
        ]);
    }
}
