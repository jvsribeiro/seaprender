<div>
    <x-ui.page-header title="Auditoria" subtitle="Quem entrou, o que abriu e o que mudou." />

    <div class="mb-5 grid grid-cols-1 max-w-2xl gap-3 sm:grid-cols-2">
        <div>
            <label for="pessoa" class="mb-1 block text-sm font-semibold">Pessoa</label>
            <input id="pessoa" type="search" class="field-input" wire:model.live.debounce.300ms="person">
        </div>
        <div>
            <label for="acao" class="mb-1 block text-sm font-semibold">Ação</label>
            <select id="acao" class="field-input" wire:model.live="action">
                <option value="">Todas</option>
                @foreach ($labels as $value => $label)
                    <option value="{{ $value }}" wire:key="action-{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="card">
        @if ($logs->isEmpty())
            <x-ui.empty title="Nenhum registro encontrado" />
        @else
            <ul class="divide-y-2 divide-noite/10">
                @foreach ($logs as $log)
                    <li class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1 py-2.5" wire:key="log-{{ $log->id }}">
                        <div class="min-w-0">
                            <p class="font-semibold">{{ $labels[$log->action] ?? $log->action }}</p>
                            <p class="text-sm text-noite/75">
                                {{ $log->actor?->name ?? 'Sistema' }}
                                @if ($log->metadata['title'] ?? $log->metadata['name'] ?? null)
                                    · {{ $log->metadata['title'] ?? $log->metadata['name'] }}
                                @endif
                            </p>
                        </div>
                        <p class="text-sm">
                            <time datetime="{{ $log->created_at->toIso8601String() }}">{{ $log->created_at->format('d/m/Y H:i') }}</time>
                            <span class="text-noite/75">· {{ $log->ip_address }}</span>
                        </p>
                    </li>
                @endforeach
            </ul>
            <div class="mt-3">{{ $logs->links('components.ui.pagination') }}</div>
        @endif
    </div>
</div>
