<x-classroom-theme :theme="$classroom->theme()" class="classroom-surface">
    <x-ui.page-header
        :title="$gameId ? 'Editar jogo' : 'Montar Se Pergunta'"
        :breadcrumbs="[$classroom->name => route('classrooms.show', ['classroom' => $classroom, 'aba' => 'se-pergunta'])]"
    />

    <form wire:submit="save" class="grid w-full min-w-0 gap-5">
        <x-ui.card title="Conteúdo do jogo" class="grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2"><x-ui.input name="title" label="Nome do jogo" placeholder="Missão de revisão" maxlength="160" required /></div>
            <x-ui.input name="topic" label="Tema do conteúdo" placeholder="Frações, história, meio ambiente…" maxlength="100" />
            <x-ui.input name="seconds" label="Segundos por pergunta" type="number" min="10" max="120" required />
            <div class="sm:col-span-2">
                <label for="intro" class="mb-1 block text-sm font-semibold">Introdução da partida</label>
                <textarea id="intro" class="field-input" wire:model="intro" maxlength="500" rows="2" placeholder="Diga à turma o que ela vai jogar."></textarea>
                @error('intro') <p class="text-sm text-erro">{{ $message }}</p> @enderror
            </div>
        </x-ui.card>

        <x-ui.card title="Aparência do jogo">
            <div class="mt-3 grid gap-3 sm:grid-cols-2">
                <div>
                    <label for="palette" class="mb-1 block text-sm font-semibold">Paleta</label>
                    <select id="palette" class="field-input" wire:model.live="palette">
                        @foreach ($palettes as $key => $preset)
                            <option value="{{ $key }}" wire:key="game-palette-{{ $key }}">{{ $preset['label'] }}</option>
                        @endforeach
                        <option value="custom">Montar meu tema</option>
                    </select>
                    @error('palette') <p class="text-sm text-erro">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="pattern" class="mb-1 block text-sm font-semibold">Padrão de fundo</label>
                    <select id="pattern" class="field-input" wire:model.live="pattern">
                        @foreach ($patterns as $key => $motif)
                            <option value="{{ $key }}" wire:key="game-pattern-{{ $key }}">{{ $motif['label'] }}</option>
                        @endforeach
                    </select>
                    @error('pattern') <p class="text-sm text-erro">{{ $message }}</p> @enderror
                </div>
            </div>

            @if ($palette === 'custom')
                <div class="mt-3 grid gap-3 sm:grid-cols-2">
                    @foreach (['base' => ['Cor principal', $preview['base']], 'accent' => ['Cor de destaque', $preview['accent']]] as $field => [$label, $color])
                        <div wire:key="game-color-{{ $field }}">
                            <label for="game-{{ $field }}" class="mb-1 block text-sm font-semibold">{{ $label }}</label>
                            <input id="game-{{ $field }}" type="color" class="size-11 cursor-pointer border-2 border-noite bg-white p-1" wire:model.live="{{ $field }}" value="{{ $color }}">
                            @error($field) <p class="text-sm text-erro">{{ $message }}</p> @enderror
                        </div>
                    @endforeach
                </div>
            @endif

            <x-classroom-theme :theme="$preview" class="se-pergunta-stage mt-4">
                <div class="relative p-4 sm:p-6">
                    <div class="flex flex-wrap items-center gap-3">
                        <x-se-perguntar-logo class="w-36 max-w-full" />
                        <p class="game-kicker">Prévia do jogo</p>
                    </div>
                    <p class="mt-1 break-words font-marca text-2xl font-bold">{{ $title ?: 'Seu jogo' }}</p>
                    <div class="mt-4 grid grid-cols-2 gap-2">
                        <span class="game-option" data-option="0"><span class="game-symbol" aria-hidden="true">▲</span> Alternativa A</span>
                        <span class="game-option" data-option="1"><span class="game-symbol" aria-hidden="true">◆</span> Alternativa B</span>
                    </div>
                </div>
            </x-classroom-theme>
        </x-ui.card>

        @error('questions') <p class="alert" role="alert">{{ $message }}</p> @enderror
        @include('livewire.quizzes.question-fields')

        <div class="flex flex-wrap gap-3">
            <x-ui.button type="submit" wire:loading.attr="disabled">Salvar jogo</x-ui.button>
            <x-ui.button variant="secondary" :href="route('classrooms.show', ['classroom' => $classroom, 'aba' => 'se-pergunta'])">Voltar</x-ui.button>
        </div>
    </form>
</x-classroom-theme>
