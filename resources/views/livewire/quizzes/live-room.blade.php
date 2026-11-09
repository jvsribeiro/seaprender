<div @if ($phase !== \App\Models\LiveQuiz::FINISHED && $classroom->isActive()) wire:poll.2s.visible @endif>
    <x-classroom-theme :theme="$theme" class="classroom-surface grid gap-5">
        <x-ui.breadcrumbs :title="'Quiz ao vivo'" :items="['Turmas' => route('classrooms.index'), $classroom->name => route('classrooms.show', $classroom)]" />

        <x-ui.hero :title="$room->title" :subtitle="$classroom->name.' · Quiz ao vivo'" :pixel="false" :theme="$theme">
            <div class="flex flex-wrap gap-2">
                <x-ui.badge>{{ $playerCount }} {{ $playerCount === 1 ? 'jogador' : 'jogadores' }}</x-ui.badge>
                <x-ui.badge tone="muted">
                    {{ match ($phase) { 'lobby' => 'Sala de espera', 'question' => 'Pergunta aberta', 'reveal' => 'Gabarito', default => 'Partida encerrada' } }}
                </x-ui.badge>
            </div>
        </x-ui.hero>

        @error('room')
            <p class="alert" role="alert">{{ $message }}</p>
        @enderror

        @if ($canManage)
            <div class="flex flex-wrap gap-2">@include('livewire.quizzes.mural-publication')</div>
        @endif

        @unless ($classroom->isActive())
            <p class="alert" role="status">A turma está arquivada.</p>
        @endunless

        @if ($phase === \App\Models\LiveQuiz::LOBBY)
            <x-ui.card :title="$canManage ? 'Sala de espera' : 'Pronto para jogar?'">
                <div class="mt-3 flex flex-wrap items-center gap-4">
                    <x-pixel name="trophy" class="w-14" />
                    <div class="min-w-0 flex-1">
                        @if ($canManage)
                            <p>Peça aos alunos para entrarem na partida pelo mural ou compartilhe o endereço abaixo.</p>
                            <label for="live-room-link" class="mt-3 block text-sm font-semibold">Link da partida</label>
                            <input id="live-room-link" class="field-input mt-1 text-sm" value="{{ route('quizzes.live', $room) }}" readonly x-on:focus="$el.select()">
                            @if ($classroom->isActive())
                                <x-ui.button class="mt-3" wire:click="advance(0, 'lobby')" wire:loading.attr="disabled" :disabled="$playerCount < 1">Começar partida</x-ui.button>
                                @if ($playerCount < 1)
                                    <p class="mt-2 text-sm">Aguardando o primeiro aluno entrar.</p>
                                @endif
                            @endif
                        @elseif ($isPlayer)
                            <p class="font-semibold text-azul" role="status">Você entrou. Aguarde o professor começar a partida.</p>
                        @elseif ($canJoin)
                            <p>Você terá {{ $room->seconds_per_question }} segundos para responder cada pergunta.</p>
                            <x-ui.button class="mt-3" wire:click="join" wire:loading.attr="disabled">Participar do quiz</x-ui.button>
                        @endif
                    </div>
                </div>
            </x-ui.card>
        @elseif ($question)
            <x-ui.card :title="$question['prompt']" wire:key="live-question-{{ $room->question_index }}">
                <div class="mt-2 flex flex-wrap items-center justify-between gap-2 text-sm font-semibold">
                    <span>Pergunta {{ $room->question_index + 1 }} de {{ count($room->questions) }}</span>
                    @if ($phase === \App\Models\LiveQuiz::QUESTION)
                        <span>{{ $secondsLeft }} s</span>
                    @else
                        <span>{{ $answered }} {{ $answered === 1 ? 'resposta' : 'respostas' }}</span>
                    @endif
                </div>
                @if ($phase === \App\Models\LiveQuiz::QUESTION)
                    <progress class="mt-1 h-3 w-full accent-azul" value="{{ $secondsLeft }}" max="{{ $room->seconds_per_question }}" aria-label="Tempo restante"></progress>
                @endif

                @if ($canJoin && ! $isPlayer)
                    <x-ui.button class="mt-4" wire:click="join" wire:loading.attr="disabled">Participar do quiz</x-ui.button>
                @endif

                <ol class="mt-4 grid gap-3 sm:grid-cols-2">
                    @foreach ($question['options'] as $choice => $option)
                        <li class="min-w-0" wire:key="round-{{ $room->question_index }}-choice-{{ $choice }}">
                            @if ($phase === \App\Models\LiveQuiz::QUESTION && $isPlayer && $canJoin && $ownAnswer === null)
                                <button type="button" class="btn-px btn-secondary h-full w-full break-words p-3 text-left" wire:click="answer({{ $room->question_index }}, {{ $choice }})" wire:loading.attr="disabled" wire:target="answer">
                                    <span class="mr-2 font-bold">{{ chr(65 + $choice) }}.</span>{{ $option }}
                                </button>
                            @else
                                <div @class(['h-full break-words border-2 p-3', 'border-azul bg-creme' => ($question['correct'] ?? null) === $choice, 'border-noite/15 bg-white' => ($question['correct'] ?? null) !== $choice])>
                                    <p><span class="mr-2 font-bold">{{ chr(65 + $choice) }}.</span>{{ $option }}</p>
                                    <div class="mt-1 flex flex-wrap gap-2 text-xs font-semibold">
                                        @if (($question['correct'] ?? null) === $choice)
                                            <span class="text-azul">Resposta correta</span>
                                        @endif
                                        @if ($ownAnswer === $choice)
                                            <span>Sua resposta</span>
                                        @endif
                                        @if ($phase === \App\Models\LiveQuiz::REVEAL)
                                            <span>{{ $choiceCounts->get($choice, 0) }} respostas</span>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </li>
                    @endforeach
                </ol>

                @if ($phase === \App\Models\LiveQuiz::REVEAL && $question['explanation'])
                    <p class="mt-4 whitespace-pre-wrap break-words border-l-4 border-azul bg-creme/70 p-3">{{ $question['explanation'] }}</p>
                @endif

                @if ($canManage && $classroom->isActive())
                    <div class="mt-4 flex flex-wrap items-center gap-3">
                        @if ($phase === \App\Models\LiveQuiz::QUESTION)
                            <x-ui.button wire:click="advance({{ $room->question_index }}, 'question')" wire:loading.attr="disabled">Mostrar gabarito</x-ui.button>
                            <p class="text-sm font-semibold">{{ $answered }} de {{ $playerCount }} responderam.</p>
                        @else
                            <x-ui.button wire:click="advance({{ $room->question_index }}, 'reveal')" wire:loading.attr="disabled">
                                {{ $room->question_index + 1 < count($room->questions) ? 'Próxima pergunta' : 'Mostrar resultados' }}
                            </x-ui.button>
                        @endif
                    </div>
                @elseif ($isPlayer && $ownAnswer !== null && $phase === \App\Models\LiveQuiz::QUESTION)
                    <p class="mt-4 font-semibold text-azul" role="status">Resposta registrada. Aguarde o gabarito.</p>
                @endif
            </x-ui.card>
        @else
            <x-ui.card title="Fim de jogo">
                @if ($canManage)
                    <p class="mt-2 text-sm">Até 10 jogadores na classificação. Cada acerto vale 100 pontos na partida.</p>
                    <ol class="mt-4 grid gap-2">
                        @foreach ($rankings as $player)
                            <li class="flex min-w-0 items-center gap-3 border-2 border-noite/10 bg-creme/50 p-3" wire:key="live-rank-{{ $player->id }}">
                                <span class="grid size-9 shrink-0 place-items-center border-2 border-azul bg-white font-bold text-azul">{{ $player->rank }}º</span>
                                <span class="min-w-0 flex-1 break-words font-semibold">{{ $player->name }}</span>
                                <span class="shrink-0 font-bold text-azul">{{ $player->points }} pts</span>
                            </li>
                        @endforeach
                    </ol>
                @elseif ($isPlayer)
                    <p class="mt-3 font-marca text-3xl font-bold text-azul">{{ $ownScore }} pontos</p>
                    <p class="mt-1 text-sm">Sua pontuação nesta partida. Cada acerto vale 100 pontos.</p>
                @else
                    <p class="mt-2">Esta partida já terminou.</p>
                @endif
                <x-ui.button variant="secondary" class="mt-4" :href="route('classrooms.show', ['classroom' => $classroom, 'aba' => $canManage ? 'ferramentas' : 'mural'])">Voltar à turma</x-ui.button>
            </x-ui.card>
        @endif

        @if ($canManage && $phase !== \App\Models\LiveQuiz::FINISHED)
            <div>
                <x-ui.confirm title="Encerrar a partida?" label="Encerrar partida" action="finish">
                    <x-slot:trigger><x-ui.button variant="secondary">Encerrar partida</x-ui.button></x-slot:trigger>
                    A classificação será calculada com as respostas já recebidas.
                </x-ui.confirm>
            </div>
        @endif
    </x-classroom-theme>
</div>
