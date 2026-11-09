<form wire:submit="join" class="card">
    <h2 class="font-marca text-xl font-semibold text-azul">Tem um código de convite?</h2>
    <div class="mt-3 flex flex-wrap items-start gap-3">
        <div class="min-w-48 flex-1">
            <label for="code" class="sr-only">Código da turma</label>
            <input
                id="code"
                type="text"
                class="field-input font-marca uppercase tracking-[0.2em]"
                placeholder="Código da turma"
                autocomplete="off"
                maxlength="20"
                wire:model="code"
                @error('code') aria-invalid="true" aria-describedby="code-erro" @enderror
            >
            @error('code')
                <p id="code-erro" class="mt-1 text-sm font-medium text-erro">{{ $message }}</p>
            @enderror
        </div>
        <x-ui.button type="submit">Solicitar inscrição</x-ui.button>
    </div>
</form>
