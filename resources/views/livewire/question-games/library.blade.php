<div class="grid grid-cols-1 gap-5">
    @error('room') <p class="alert" role="alert">{{ $message }}</p> @enderror

    <x-classroom-theme :theme="\App\Support\ClassroomTheme::resolve('orbit')" class="se-pergunta-stage">
        <div class="relative flex flex-wrap items-center justify-between gap-4 p-5 sm:p-7">
            <div class="min-w-0">
                <p class="game-kicker">Jogos da turma</p>
                <h2 class="mt-2"><x-se-perguntar-logo class="w-60 max-w-full" /></h2>
                <p class="mt-2 max-w-xl text-sm text-white/90">{{ $canManage ? 'Monte o conteúdo, personalize a tela e publique a partida para a turma entrar.' : 'Entre nas partidas publicadas pelo professor e reveja suas pontuações.' }}</p>
            </div>
            @if ($canManage && $classroom->isActive())
                <x-ui.button :href="route('question-games.create', $classroom)">Montar jogo</x-ui.button>
            @endif
        </div>
    </x-classroom-theme>

    @if ($canManage)
        @if ($activeRoom)
            <x-ui.card class="flex flex-wrap items-center justify-between gap-3">
                <p class="wrap-anywhere text-sm font-semibold">Partida aberta · {{ $activeRoom->title }}</p>
                <x-ui.button :href="route('quizzes.live', $activeRoom)">Voltar à partida</x-ui.button>
            </x-ui.card>
        @endif

        @if ($games->isEmpty())
            <x-ui.card>
                <x-ui.empty title="Crie seu primeiro jogo" sprite="star">Escolha um assunto, escreva as perguntas e monte o visual da partida.</x-ui.empty>
            </x-ui.card>
        @else
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                @foreach ($games as $game)
                    <article class="card min-w-0 overflow-hidden p-0" wire:key="question-game-{{ $game->id }}">
                        <x-classroom-theme :theme="$game->theme" class="classroom-cover flex h-14 items-center justify-end px-4">
                            <x-pixel :name="$game->theme['sprite']" class="relative w-8" />
                        </x-classroom-theme>
                        <div class="p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-azul">{{ $game->topic ?: 'Se Pergunta' }}</p>
                            <h3 class="mt-1 wrap-anywhere font-marca text-lg font-bold text-noite">{{ $game->title }}</h3>
                            <p class="mt-1 text-sm">{{ count($game->questions) }} perguntas · {{ $game->seconds_per_question }} s por pergunta</p>
                            @if ($classroom->isActive())
                                <div class="mt-4 flex flex-wrap gap-2">
                                    @if (! $activeRoom || $activeRoom->question_game_id === $game->id)
                                        <x-ui.button wire:click="play({{ $game->id }}, true)" wire:loading.attr="disabled" wire:target="play">Publicar e abrir</x-ui.button>
                                        <x-ui.button variant="secondary" wire:click="play({{ $game->id }}, false)" wire:loading.attr="disabled" wire:target="play">Abrir sala</x-ui.button>
                                    @endif
                                    <x-ui.button variant="secondary" :href="route('question-games.edit', ['classroom' => $classroom, 'questionGame' => $game])">Editar jogo</x-ui.button>
                                </div>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
            {{ $games->links('components.ui.pagination') }}
        @endif
    @else
        <x-ui.card title="Minhas partidas">
            <ul class="mt-2 divide-y divide-noite/10">
                @forelse ($recentRooms as $room)
                    <li class="flex flex-wrap items-center justify-between gap-3 py-3" wire:key="played-game-{{ $room->id }}">
                        <p class="min-w-0 wrap-anywhere font-semibold">{{ $room->title }}</p>
                        <x-ui.button variant="secondary" :href="route('quizzes.live', $room)">Meu resultado</x-ui.button>
                    </li>
                @empty
                    <li class="py-2 text-sm">Suas partidas concluídas vão aparecer aqui.</li>
                @endforelse
            </ul>
        </x-ui.card>
    @endif
</div>
