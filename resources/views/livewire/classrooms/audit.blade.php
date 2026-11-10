<x-ui.card title="Auditoria da turma">
    <div class="mt-2 flex flex-wrap items-end justify-between gap-3">
        <p class="max-w-lg text-sm text-noite/80">Histórico de matrículas, publicações, quizzes, ferramentas de aula e movimentações da lojinha.</p>
        <div>
            <label for="audit-category" class="mb-1 block text-sm font-semibold">Filtrar por categoria</label>
            <select id="audit-category" class="field-input" wire:model.live="category">
                <option value="">Todos os eventos</option>
                @foreach ($categories as $key => [$label, $icon])
                    <option value="{{ $key }}" wire:key="audit-category-{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <ol class="mt-4 grid gap-3">
        @forelse ($logs as $log)
            @php
                [$label, $icon] = $categories[str($log->action)->before('.')->value()] ?? ['Evento', 'scroll'];
                $details = $log->metadata ?? [];
            @endphp
            <li class="flex min-w-0 gap-3 border-2 border-noite/10 bg-creme/50 p-3 sm:p-4" wire:key="audit-{{ $log->id }}">
                <div class="grid size-10 shrink-0 place-items-center border-2 border-noite/10 bg-white">
                    <x-pixel :name="$icon" :palette="['w' => 'currentColor']" class="w-6 text-azul" />
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1">
                        <x-ui.badge tone="muted">{{ $label }}</x-ui.badge>
                        <time class="text-xs font-semibold text-noite/75" datetime="{{ $log->created_at->toIso8601String() }}">{{ $log->created_at->format('d/m/Y · H:i') }}</time>
                    </div>
                    <p class="mt-1 wrap-anywhere font-marca text-lg font-semibold text-azul">{{ $labels[$log->action] ?? 'Evento registrado' }}</p>
                    <p class="text-sm"><span class="font-semibold">Por</span> {{ $log->actor?->name ?? 'Sistema ou conta excluída' }}</p>
                    @if (isset($details['title']))
                        <p class="mt-1 wrap-anywhere text-sm">{{ $details['title'] }}</p>
                    @endif
                    @if (isset($details['student']))
                        <p class="wrap-anywhere text-sm"><span class="font-semibold">Aluno</span> {{ $details['student'] }}</p>
                    @endif
                    @if (isset($details['reason']))
                        <p class="wrap-anywhere text-sm"><span class="font-semibold">Motivo</span> {{ $details['reason'] }}</p>
                    @endif
                    @if (isset($details['points']))
                        <p class="text-sm font-semibold">{{ $details['points'] }} pontos</p>
                    @endif
                    @if (isset($details['score'], $details['total']))
                        <p class="text-sm font-semibold">{{ $details['score'] }} de {{ $details['total'] }} acertos</p>
                    @endif
                    @if (isset($details['changed']) && array_intersect($details['changed'], ['theme_palette', 'theme_pattern', 'theme_base', 'theme_accent']))
                        <p class="text-sm font-semibold">Tema e fundo atualizados.</p>
                    @endif
                    @if (isset($details['enrolled'], $details['skipped'], $details['errors']))
                        <p class="text-sm">{{ $details['enrolled'] }} matriculados · {{ $details['skipped'] }} já na turma · {{ $details['errors'] }} com erro</p>
                    @endif
                    @if (isset($details['status']))
                        <p class="text-sm font-semibold">{{ $details['status'] === 'active' ? 'Turma ativa' : 'Turma arquivada' }}</p>
                    @endif
                </div>
            </li>
        @empty
            <li><x-ui.empty title="Nenhum evento nesta seleção" sprite="scroll">As ações registradas nesta turma aparecem aqui.</x-ui.empty></li>
        @endforelse
    </ol>
    <div class="mt-4">{{ $logs->links('components.ui.pagination') }}</div>
</x-ui.card>
