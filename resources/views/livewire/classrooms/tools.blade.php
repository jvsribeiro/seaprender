<div class="grid gap-5">
    @error('tools')
        <p class="alert" role="alert">{{ $message }}</p>
    @enderror
    @error('room')
        <p class="alert" role="alert">{{ $message }}</p>
    @enderror

    @if ($classroom->isActive())
        <x-ui.card class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-marca text-xl font-semibold text-azul">Ferramentas da aula</h2>
                <p class="mt-1 text-sm">Prepare a dinâmica e publique no mural quando estiver pronta. Os nomes aparecem abreviados nos resultados dos sorteios e grupos.</p>
            </div>
            <x-ui.button :href="route('classrooms.show', ['classroom' => $classroom, 'aba' => 'se-pergunta'])">Montar Se Pergunta</x-ui.button>
        </x-ui.card>
        <nav class="flex flex-wrap gap-2" aria-label="Atalhos de ferramentas">
            <a class="chip inline-flex items-center" href="#sorteio">Sorteio</a>
            <a class="chip inline-flex items-center" href="#grupos">Grupos</a>
            <a class="chip inline-flex items-center" href="#votacao">Votação</a>
            <a class="chip inline-flex items-center" href="#reflexao">Reflexão</a>
            <a class="chip inline-flex items-center" href="#quiz-ao-vivo">Quiz ao vivo</a>
        </nav>
        <x-ui.card id="quiz-ao-vivo" class="scroll-mt-20 border-azul">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex min-w-0 items-start gap-3">
                    <x-pixel name="trophy" class="w-12 shrink-0" />
                    <div>
                        <h2 class="font-marca text-2xl font-semibold text-azul">Quiz ao vivo</h2>
                        <p class="mt-1 text-sm">Abra uma sala a partir de um quiz da turma. Os alunos entram pelo mural e jogam pergunta por pergunta.</p>
                    </div>
                </div>
                <x-ui.button variant="secondary" :href="route('quizzes.create', $classroom)">Criar novo quiz</x-ui.button>
            </div>
            @if ($activeRoom)
                <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-2 border-noite/10 bg-creme p-3">
                    <p class="break-words font-semibold">{{ $activeRoom->title }} · Sala aberta</p>
                    <x-ui.button :href="route('quizzes.live', $activeRoom)">Voltar à sala</x-ui.button>
                </div>
            @elseif ($quizzes->isNotEmpty())
                <form wire:submit="openQuiz" class="mt-4 grid gap-3 sm:grid-cols-[minmax(0,1fr)_10rem_auto] sm:items-start">
                    <div>
                        <label for="live-quiz-choice" class="mb-1 block text-sm font-semibold">Quiz da turma</label>
                        <select id="live-quiz-choice" class="field-input" wire:model="quizId" required>
                            <option value="">Selecione um quiz</option>
                            @foreach ($quizzes as $quiz)
                                <option value="{{ $quiz->id }}" wire:key="quiz-option-{{ $quiz->id }}">{{ $quiz->title }}</option>
                            @endforeach
                        </select>
                        @error('quizId') <p class="text-sm text-erro" role="alert">{{ $message }}</p> @enderror
                    </div>
                    <x-ui.input name="seconds" label="Segundos por pergunta" type="number" min="10" max="120" required />
                    <x-ui.button type="submit" class="sm:mt-6">Abrir sala</x-ui.button>
                </form>
            @else
                <p class="mt-4 text-sm font-semibold">Crie um quiz com perguntas para abrir a primeira sala.</p>
            @endif
        </x-ui.card>

        <div class="grid gap-4 lg:grid-cols-2">
            <x-ui.card id="votacao" class="scroll-mt-20">
                <div class="flex items-center gap-3">
                    <x-pixel name="coin" class="w-8" />
                    <h2 class="font-marca text-xl font-semibold text-azul">Votação rápida</h2>
                </div>
                <p class="mt-2 text-sm">Publique uma pergunta no mural e acompanhe os totais enquanto os alunos votam.</p>
                <form wire:submit="publishPoll" class="mt-3 grid gap-3">
                    <x-ui.input name="pollQuestion" label="Pergunta" placeholder="Qual tema devemos revisar?" maxlength="160" required />
                    <div>
                        <label for="poll-options" class="mb-1 block text-sm font-semibold">Alternativas, uma por linha</label>
                        <textarea id="poll-options" class="field-input" wire:model="pollOptions" rows="3" maxlength="800" required @error('pollOptions') aria-invalid="true" aria-describedby="poll-options-error" @enderror></textarea>
                        @error('pollOptions') <p id="poll-options-error" class="text-sm text-erro">{{ $message }}</p> @enderror
                    </div>
                    <x-ui.button type="submit" class="justify-self-start">Publicar votação</x-ui.button>
                </form>
            </x-ui.card>

            <x-ui.card id="reflexao" class="scroll-mt-20">
                <div class="flex items-center gap-3">
                    <x-pixel name="scroll" class="w-8" />
                    <h2 class="font-marca text-xl font-semibold text-azul">Reflexão de fim de aula</h2>
                </div>
                <p class="mt-2 text-sm">Abra no mural um espaço privado para cada aluno dizer o que gostou, sugerir melhorias e resumir o que aprendeu.</p>
                <form wire:submit="publishReflection" class="mt-3 grid gap-3">
                    <x-ui.input name="reflectionTitle" label="Título da reflexão" placeholder="O que ficou da aula de hoje?" maxlength="160" required />
                    <div>
                        <label for="reflection-instructions" class="mb-1 block text-sm font-semibold">Orientação opcional</label>
                        <textarea id="reflection-instructions" class="field-input" wire:model="reflectionInstructions" rows="3" maxlength="500"></textarea>
                        @error('reflectionInstructions') <p class="text-sm text-erro" role="alert">{{ $message }}</p> @enderror
                    </div>
                    <x-ui.button type="submit" class="justify-self-start">Publicar reflexão</x-ui.button>
                </form>
            </x-ui.card>

            <x-ui.card id="sorteio" class="scroll-mt-20">
                <div class="flex items-center gap-3">
                    <x-pixel name="star" class="w-8" />
                    <h2 class="font-marca text-xl font-semibold text-azul">Sorteio de aluno</h2>
                </div>
                <p class="mt-2 text-sm">Sorteie entre os matriculados. Cada aluno pode sair uma vez por ciclo nesta tela.</p>
                <p class="mt-2 text-sm">{{ $remainingStudents }} disponíveis neste ciclo.</p>
                @error('draw') <p class="mt-2 text-sm text-erro" role="alert">{{ $message }}</p> @enderror
                <p class="mt-3 font-marca text-xl font-bold text-azul" role="status">{{ $drawnName }}</p>
                <div class="mt-3 flex flex-wrap gap-2">
                    <x-ui.button wire:click="drawStudent" wire:loading.attr="disabled" :disabled="$remainingStudents < 1">Sortear aluno</x-ui.button>
                    @if ($drawnIds)
                        <x-ui.button variant="secondary" wire:click="resetDraw" wire:loading.attr="disabled">Novo ciclo</x-ui.button>
                    @endif
                    @if ($drawPostId)
                        <x-ui.button variant="secondary" :href="route('classrooms.show', $classroom).'#post-'.$drawPostId">Ver sorteio no mural</x-ui.button>
                    @elseif ($drawnName)
                        <x-ui.button wire:click="publishDraw" wire:loading.attr="disabled">Publicar sorteio</x-ui.button>
                    @endif
                </div>
            </x-ui.card>

            <x-ui.card id="grupos" class="scroll-mt-20">
                <div class="flex items-center gap-3">
                    <x-pixel name="users" class="w-8" />
                    <h2 class="font-marca text-xl font-semibold text-azul">Formar grupos</h2>
                </div>
                <p class="mt-2 text-sm">Distribua os alunos aleatoriamente em grupos de tamanhos parecidos.</p>
                <form wire:submit="drawGroups" class="mt-3 flex flex-wrap items-start gap-3">
                    <x-ui.input name="groupCount" label="Número de grupos" type="number" min="2" max="20" required />
                    <x-ui.button type="submit" class="sm:mt-6">Sortear grupos</x-ui.button>
                </form>
                @if ($groups)
                    <ol class="mt-3 grid gap-2 text-sm" aria-label="Grupos sorteados">
                        @foreach ($groups as $group)
                            <li class="break-words border-t border-noite/10 pt-2" wire:key="drawn-group-{{ $loop->iteration }}">
                                <span class="font-bold text-azul">Grupo {{ $loop->iteration }}</span>
                                <span class="block">{{ implode(', ', $group) }}</span>
                            </li>
                        @endforeach
                    </ol>
                    <div class="mt-3">
                        @if ($groupsPostId)
                            <x-ui.button variant="secondary" :href="route('classrooms.show', $classroom).'#post-'.$groupsPostId">Ver grupos no mural</x-ui.button>
                        @else
                            <x-ui.button wire:click="publishGroups" wire:loading.attr="disabled">Publicar grupos</x-ui.button>
                        @endif
                    </div>
                @endif
            </x-ui.card>
        </div>
    @else
        <x-ui.card title="Ferramentas de aula">
            <p class="mt-2 text-sm">Reative a turma para publicar participações, sortear alunos ou iniciar jogos.</p>
            @if ($activeRoom)
                <x-ui.button class="mt-3" :href="route('quizzes.live', $activeRoom)">Abrir sala existente</x-ui.button>
            @endif
        </x-ui.card>
    @endif

    @if ($finishedRooms->isNotEmpty())
        <x-ui.card title="Últimas partidas">
            <ul class="mt-2 divide-y divide-noite/10">
                @foreach ($finishedRooms as $room)
                    <li class="flex flex-wrap items-center justify-between gap-2 py-2" wire:key="finished-room-{{ $room->id }}">
                        <div class="min-w-0">
                            <p class="break-words font-semibold">{{ $room->title }}</p>
                            <time class="text-sm" datetime="{{ $room->updated_at->toIso8601String() }}">{{ $room->updated_at->format('d/m/Y · H:i') }}</time>
                        </div>
                        <x-ui.button variant="secondary" :href="route('quizzes.live', $room)">Ver classificação</x-ui.button>
                    </li>
                @endforeach
            </ul>
        </x-ui.card>
    @endif
</div>
