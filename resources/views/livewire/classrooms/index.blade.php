<div>
    <x-ui.page-header title="Turmas">
        <x-slot:actions>
            @can('create', App\Models\Classroom::class)
                <x-ui.button :href="route('classrooms.create')">Nova turma</x-ui.button>
            @endcan
            @if (auth()->user()->isStudent())
                <x-ui.button :href="route('classrooms.browse')">Procurar turmas</x-ui.button>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    <div class="mb-5 max-w-sm">
        <label for="busca" class="mb-1 block text-sm font-semibold">Buscar turma</label>
        <input id="busca" type="search" class="field-input" wire:model.live.debounce.300ms="search">
    </div>

    <div wire:loading.delay class="mb-3 text-sm font-medium">Carregando…</div>

    <h2 class="sr-only">Lista de turmas</h2>

    @if ($classrooms->isEmpty())
        <div class="card">
            <x-ui.empty :title="$search === '' ? 'Nenhuma turma por aqui' : 'Nada encontrado'" />
        </div>
    @else
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($classrooms as $classroom)
                <x-classroom-card :classroom="$classroom" wire:key="classroom-{{ $classroom->id }}" />
            @endforeach
        </div>
        <div class="mt-5">{{ $classrooms->links('components.ui.pagination') }}</div>
    @endif
</div>
