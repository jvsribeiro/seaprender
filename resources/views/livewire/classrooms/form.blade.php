<div>
    <x-ui.page-header
        :title="$classroomId ? 'Editar turma' : 'Nova turma'"
        :breadcrumbs="['Turmas' => route('classrooms.index')]"
    />

    <form wire:submit="save" class="card grid max-w-2xl gap-4 sm:grid-cols-2">
        <div class="sm:col-span-2"><x-ui.input name="name" label="Nome da turma" maxlength="120" /></div>
        <x-ui.input name="course" label="Curso" maxlength="120" />
        <x-ui.input name="curricular_unit" label="Unidade curricular" maxlength="120" />

        <div>
            <label for="period" class="mb-1 block text-sm font-semibold">Período</label>
            <select id="period" class="field-input" wire:model="period">
                @foreach ($periods as $option)
                    <option value="{{ $option }}" wire:key="period-{{ $option }}">{{ $option }}</option>
                @endforeach
            </select>
            @error('period') <p class="mt-1 text-sm font-medium text-erro">{{ $message }}</p> @enderror
        </div>

        <div class="hidden sm:block"></div>
        <x-ui.input name="starts_on" label="Início" type="date" />
        <x-ui.input name="ends_on" label="Término" type="date" />

        <div class="sm:col-span-2">
            <x-ui.button type="submit">Salvar turma</x-ui.button>
        </div>
    </form>
</div>
