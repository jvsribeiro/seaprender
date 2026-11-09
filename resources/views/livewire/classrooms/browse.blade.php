<div class="grid gap-6">
    <x-ui.hero title="Procurar turmas" :subtitle="$openEnrollment ? 'Encontre a turma e envie um pedido de inscrição ao professor.' : 'Use o código do professor para solicitar inscrição na sua turma.'">
        <div class="max-w-sm">
            <label for="busca" class="mb-1 block text-sm font-semibold">Turma, curso ou professor</label>
            <input id="busca" type="search" class="field-input" wire:model.live.debounce.300ms="search">
        </div>
    </x-ui.hero>

    @error('code')
        <p class="alert" role="alert">{{ $message }}</p>
    @enderror

    @if ($classrooms->isEmpty())
        <div class="card">
            <x-ui.empty :title="$search === '' ? ($openEnrollment ? 'Nenhuma turma aberta' : 'Nenhuma turma encontrada') : 'Nada encontrado'">
                @if (! $openEnrollment)
                    Peça o código ao professor para enviar uma solicitação de inscrição.
                @elseif ($search === '')
                    Assim que o professor abrir uma turma, ela aparece aqui.
                @endif
            </x-ui.empty>
        </div>
    @else
        <div>
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($classrooms as $classroom)
                    <article class="card flex flex-col p-0" wire:key="classroom-{{ $classroom->id }}">
                        <x-classroom-cover :classroom="$classroom" />
                        <div class="flex flex-1 flex-col gap-3 p-4">
                            <div class="min-w-0">
                                <h2 class="break-words font-marca text-lg font-semibold text-azul">{{ $classroom->name }}</h2>
                                <p class="mt-1 text-sm">{{ $classroom->course }} · {{ $classroom->curricular_unit }}</p>
                                <p class="mt-1 text-sm">{{ $classroom->period }} · {{ $classroom->teacher->name }}</p>
                                <p class="mt-1 text-sm font-medium">{{ $classroom->active_enrollments_count }} {{ $classroom->active_enrollments_count === 1 ? 'aluno' : 'alunos' }}</p>
                            </div>
                            <div class="mt-auto">
                                @php($status = $classroom->enrollments->first()?->status)
                                @if ($status === \App\Enums\EnrollmentStatus::Active)
                                    <x-ui.button variant="secondary" :href="route('classrooms.show', $classroom)">Abrir turma</x-ui.button>
                                @elseif ($status === \App\Enums\EnrollmentStatus::Pending)
                                    <x-ui.badge tone="muted">Aguardando aprovação</x-ui.badge>
                                @elseif ($status === \App\Enums\EnrollmentStatus::Rejected)
                                    <x-ui.badge tone="muted">Solicitação recusada</x-ui.badge>
                                    <p class="mt-1 text-sm">Fale com o professor.</p>
                                @elseif ($openEnrollment)
                                    <x-ui.button wire:click="enroll({{ $classroom->id }})" wire:loading.attr="disabled" wire:target="enroll">Solicitar inscrição</x-ui.button>
                                @endif
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
            <div class="mt-5">{{ $classrooms->links('components.ui.pagination') }}</div>
        </div>
    @endif

    <div class="max-w-xl">
        <livewire:classrooms.join-form />
    </div>
</div>
