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

    public const LABELS = AuditLog::LABELS;

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
