<x-classroom-theme :theme="$classroom->theme()" class="classroom-surface">
    <x-ui.page-header
        :title="$quizId ? 'Editar quiz' : 'Novo quiz'"
        :breadcrumbs="[$classroom->name => route('classrooms.show', [$classroom, 'aba' => 'quizzes'])]"
    />

    <form wire:submit="saveDraft" class="grid w-full min-w-0 max-w-4xl gap-5">
        <div class="card grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2"><x-ui.input name="title" label="Título" maxlength="160" /></div>
            <div class="sm:col-span-2">
                <label for="description" class="mb-1 block text-sm font-semibold">Descrição</label>
                <textarea id="description" rows="2" maxlength="2000" class="field-input" wire:model="description"></textarea>
            </div>
            <div>
                <label for="subjectId" class="mb-1 block text-sm font-semibold">Matéria</label>
                <select id="subjectId" class="field-input" wire:model="subjectId">
                    <option value="">Sem matéria</option>
                    @foreach ($subjects as $subject)
                        <option value="{{ $subject->id }}" wire:key="subject-{{ $subject->id }}">{{ $subject->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="card">
            <h2 class="font-marca text-lg font-semibold text-azul">Gerar perguntas com IA</h2>
            @if ($aiAvailable)
                <div class="mt-2 flex flex-wrap items-start gap-3">
                    <div class="min-w-48 flex-1"><x-ui.input name="aiTopic" label="Assunto" maxlength="200" /></div>
                    <x-ui.button variant="secondary" class="sm:mt-6" wire:click="generateWithAi">Gerar</x-ui.button>
                </div>
            @else
                <p class="mt-1 text-sm">A IA ainda não foi conectada. Quando for, as perguntas geradas chegam aqui para você revisar antes de publicar.</p>
            @endif
        </div>

        @include('livewire.quizzes.question-fields')

        <div class="flex flex-wrap gap-3">
            <x-ui.button wire:click="saveAndPublish" wire:loading.attr="disabled" wire:target="saveAndPublish,saveDraft">Publicar no mural</x-ui.button>
            <x-ui.button type="submit" variant="secondary" wire:loading.attr="disabled" wire:target="saveAndPublish,saveDraft">Salvar rascunho</x-ui.button>
        </div>
    </form>
</x-classroom-theme>
