<div class="grid grid-cols-1 gap-4"
    @if ($phase !== \App\Models\LiveQuiz::FINISHED && $classroom->isActive()) wire:poll.2s @endif
    data-game-phase="{{ $phase }}"
    data-game-deadline="{{ $phaseDeadline }}"
    data-game-clock="{{ $clockTimestamp }}"
    data-game-active="{{ $classroom->isActive() ? 1 : 0 }}"
    x-data="sePergunta"
    x-on:fullscreenchange.window="fullscreen = document.fullscreenElement === $refs.stage"
>
    <x-ui.breadcrumbs title="Se Pergunta" :items="[$classroom->name => route('classrooms.show', ['classroom' => $classroom, 'aba' => 'se-pergunta'])]" />

    @if ($canManage)
        <div class="flex flex-wrap gap-2">
            @include('livewire.quizzes.mural-publication')
            @if ($phase !== \App\Models\LiveQuiz::FINISHED)
                <x-ui.confirm title="Encerrar a partida?" label="Encerrar partida" action="finish">
                    <x-slot:trigger><x-ui.button variant="secondary">Encerrar partida</x-ui.button></x-slot:trigger>
                    A classificação será calculada com as respostas recebidas.
                </x-ui.confirm>
            @endif
        </div>
    @endif

    <x-classroom-theme :theme="$theme" class="se-pergunta-stage" x-ref="stage">
        <div wire:ignore>
            <audio x-ref="soundtrack" preload="none" loop
                x-on:play="soundOn = true" x-on:pause="soundOn = false" x-on:error="audioError = true">
                <source src="{{ asset('audio/quiz-celestial-8-bit.mp3') }}" type="audio/mpeg">
            </audio>
        </div>
        <div class="game-frame relative grid grid-cols-1 gap-5 p-4 sm:p-7">
            <header class="flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0">
                    <x-se-perguntar-logo class="mb-2 w-40 max-w-full sm:w-48" />
                    @if ($room->game_settings['topic'])
                        <p class="game-kicker">{{ $room->game_settings['topic'] }}</p>
                    @endif
                    <h1 class="mt-1 wrap-anywhere font-pixel text-3xl font-bold sm:text-4xl">{{ $room->title }}</h1>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="game-pill">{{ $playerCount }} {{ $playerCount === 1 ? 'jogador' : 'jogadores' }}</span>
                    @if ($phase !== \App\Models\LiveQuiz::FINISHED && $classroom->isActive())
                        <button type="button" class="game-pill game-pill-button" x-on:click="toggleMusic" x-bind:aria-pressed="soundOn.toString()" x-text="soundOn ? 'Desligar som' : 'Ligar som'">Ligar som</button>
                    @endif
                    @if ($canManage)
                        <button type="button" class="game-pill game-pill-button" x-on:click="toggleFullscreen" x-text="fullscreen ? 'Sair da tela cheia' : 'Tela cheia'">Tela cheia</button>
                    @endif
                </div>
            </header>

            <p class="text-sm" x-cloak x-show="fullscreenError" role="status">O navegador recusou a tela cheia. A partida pode continuar aqui.</p>
            <p class="text-sm" x-cloak x-show="audioError" role="status">A música não iniciou. Tente usar o botão Ligar som.</p>
            @foreach (['room', 'classroom'] as $field)
                @error($field) <p class="game-panel p-3 font-semibold" role="alert">{{ $message }}</p> @enderror
            @endforeach
            @unless ($classroom->isActive())
                <p class="game-panel p-3" role="status">Esta turma está arquivada.</p>
            @endunless

            @if ($phase === \App\Models\LiveQuiz::LOBBY)
                <section class="game-panel game-enter grid justify-items-center gap-3 p-5 text-center sm:p-8" wire:key="game-lobby">
                    <x-pixel name="trophy" class="game-trophy w-14" />
                    <h2 class="font-marca text-3xl font-bold">Sala de espera</h2>
                    @if ($room->game_settings['intro'])
                        <p class="max-w-xl wrap-anywhere">{{ $room->game_settings['intro'] }}</p>
                    @endif
                    <p class="text-sm">{{ count($room->questions) }} perguntas · {{ $room->seconds_per_question }} segundos por pergunta</p>
                    @if ($canManage && $classroom->isActive())
                        <p class="text-sm">Os alunos entram pela publicação no mural ou pelo aviso da turma.</p>
                        <x-ui.button wire:click="advance(0, 'lobby')" x-on:click="startMusic" wire:loading.attr="disabled" :disabled="$playerCount < 1">Começar jogo</x-ui.button>
                    @elseif ($isPlayer)
                        <p class="font-semibold" role="status">Você entrou. Aguarde o professor começar.</p>
                    @elseif ($canJoin)
                        <x-ui.button wire:click="join" wire:loading.attr="disabled">Entrar no jogo</x-ui.button>
                    @endif
                </section>
            @elseif ($phase === \App\Models\LiveQuiz::COUNTDOWN)
                <section class="game-countdown game-enter grid justify-items-center gap-4 px-4 py-9 text-center" wire:key="game-countdown-{{ $room->question_index }}">
                    <p class="game-kicker">Pergunta {{ $room->question_index + 1 }} de {{ count($room->questions) }}</p>
                    <h2 class="font-marca text-3xl font-bold sm:text-5xl">Prepare-se!</h2>
                    <div class="game-countdown-number font-pixel" role="timer" aria-label="Contagem regressiva" x-text="secondsLeft ? Math.min(secondsLeft, 3) : 'Já!'">{{ min(3, $secondsLeft) }}</div>
                    @if ($canJoin && ! $isPlayer)
                        <x-ui.button wire:click="join" wire:loading.attr="disabled">Entrar no jogo</x-ui.button>
                    @endif
                </section>
            @elseif ($question)
                @php
                    $answering = $phase === \App\Models\LiveQuiz::QUESTION;
                    $symbols = ['▲', '◆', '●', '■', '✦'];
                @endphp
                <section class="game-enter grid grid-cols-1 gap-3" wire:key="game-round-{{ $room->question_index }}-{{ $phase }}">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <span class="game-pill">Pergunta {{ $room->question_index + 1 }} de {{ count($room->questions) }}</span>
                        @if ($answering)
                            <span class="game-pill game-seconds" x-bind:class="{ 'game-seconds-low': secondsLeft <= 5 }" x-text="secondsLeft + ' s'">{{ $secondsLeft }} s</span>
                        @else
                            <span class="game-pill">Gabarito · {{ $answered }} respostas</span>
                        @endif
                    </div>
                    @if ($answering)
                        <progress class="game-timer h-3 w-full" value="{{ $secondsLeft }}" x-bind:value="remainingMs / 1000" max="{{ $room->seconds_per_question }}" aria-label="Tempo restante"></progress>
                    @endif

                    <h2 class="game-panel wrap-anywhere p-4 text-center font-marca text-xl font-bold sm:p-6 sm:text-3xl">{{ $question['prompt'] }}</h2>

                    @if ($canJoin && ! $isPlayer)
                        <x-ui.button class="justify-self-start" wire:click="join" wire:loading.attr="disabled">Entrar no jogo</x-ui.button>
                    @endif

                    <ol class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                        @foreach ($question['options'] as $choice => $option)
                            <li class="min-w-0" wire:key="game-answer-{{ $room->question_index }}-{{ $choice }}">
                                @if ($answering && $isPlayer && $canJoin && $ownAnswer === null)
                                    <button type="button" class="game-option game-option-button" data-option="{{ $choice }}" wire:click="answer({{ $room->question_index }}, {{ $choice }})" x-bind:disabled="remainingMs <= 0" wire:loading.attr="disabled" wire:target="answer">
                                        <span class="game-symbol" aria-hidden="true">{{ $symbols[$choice] ?? '✦' }}</span>
                                        <span class="min-w-0 wrap-anywhere"><span class="sr-only">Alternativa </span>{{ chr(65 + $choice) }}. {{ $option }}</span>
                                    </button>
                                @else
                                    <div @class(['game-option', 'game-option-correct' => ! $answering && ($question['correct'] ?? null) === $choice]) data-option="{{ $choice }}">
                                        <span class="game-symbol" aria-hidden="true">{{ $symbols[$choice] ?? '✦' }}</span>
                                        <div class="min-w-0 wrap-anywhere">
                                            <p>{{ chr(65 + $choice) }}. {{ $option }}</p>
                                            @unless ($answering)
                                                <p class="mt-1 text-xs">{{ ($question['correct'] ?? null) === $choice ? '✓ Resposta correta · ' : '' }}{{ $choiceCounts->get($choice, 0) }} respostas</p>
                                            @endunless
                                            @if ($ownAnswer === $choice)
                                                <p class="mt-1 text-xs">Sua resposta</p>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            </li>
                        @endforeach
                    </ol>

                    @if (! $answering && $question['explanation'])
                        <p class="game-panel whitespace-pre-wrap wrap-anywhere p-3">{{ $question['explanation'] }}</p>
                    @endif
                    @if ($canManage && $classroom->isActive())
                        <div class="flex flex-wrap items-center gap-3">
                            @if ($answering)
                                <x-ui.button wire:click="advance({{ $room->question_index }}, 'question')" wire:loading.attr="disabled">Revelar resposta</x-ui.button>
                                <span class="text-sm">{{ $answered }} de {{ $playerCount }} responderam</span>
                            @else
                                <x-ui.button wire:click="advance({{ $room->question_index }}, 'reveal')" wire:loading.attr="disabled">
                                    {{ $room->question_index + 1 < count($room->questions) ? 'Próxima pergunta' : 'Mostrar pódio' }}
                                </x-ui.button>
                            @endif
                        </div>
                    @elseif ($answering && $ownAnswer !== null)
                        <p role="status" class="font-semibold">Resposta registrada. Aguarde o gabarito.</p>
                    @endif
                </section>
            @else
                <section class="game-panel game-enter grid justify-items-center gap-4 p-5 text-center sm:p-8" wire:key="game-results">
                    <x-pixel name="trophy" class="game-trophy w-14" />
                    <h2 class="font-marca text-3xl font-bold">{{ $canManage ? 'Pódio da partida' : 'Fim de jogo' }}</h2>
                    @if ($canManage && $rankings->isNotEmpty())
                        <ol class="grid grid-cols-1 w-full max-w-2xl gap-2 sm:grid-cols-3">
                            @foreach ($rankings->take(3) as $player)
                                <li class="game-podium min-w-0 p-3" wire:key="game-podium-{{ $player->id }}">
                                    <p class="font-pixel text-2xl font-bold">{{ $player->rank }}º</p>
                                    <p class="wrap-anywhere font-semibold">{{ \App\Support\ClassroomDisplayName::abbreviate($player->name) }}</p>
                                    <p class="text-sm font-bold">{{ $player->points }} pontos</p>
                                </li>
                            @endforeach
                        </ol>
                    @elseif ($isPlayer)
                        <p class="font-pixel text-4xl font-bold">{{ $ownScore }} pontos</p>
                    @else
                        <p class="text-sm">A partida foi encerrada.</p>
                    @endif
                    <x-ui.button variant="secondary" :href="route('classrooms.show', ['classroom' => $classroom, 'aba' => 'se-pergunta'])">Voltar ao Se Pergunta</x-ui.button>
                </section>
            @endif
        </div>
    </x-classroom-theme>
</div>
