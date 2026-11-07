<div class="mx-auto max-w-md pt-8">
    <div class="card text-center">
        @if ($classroom)
            <x-pixel name="star" class="mx-auto w-12" :palette="['o' => '#004C94']" />
            <h1 class="mt-4 break-words font-marca text-2xl font-semibold text-azul">{{ $classroom->name }}</h1>
            <p class="mt-1">{{ $classroom->curricular_unit }} · {{ $classroom->teacher->name }}</p>
            <div class="mt-5">
                <x-ui.button wire:click="join">Entrar na turma</x-ui.button>
            </div>
            @error('code')
                <p class="mt-3 text-sm font-medium text-erro">{{ $message }}</p>
            @enderror
        @else
            <x-ui.empty title="Convite inválido">
                Peça um novo código ao professor.
            </x-ui.empty>
        @endif
    </div>
</div>
