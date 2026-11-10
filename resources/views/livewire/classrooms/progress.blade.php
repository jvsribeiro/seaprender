<div class="grid min-w-0 grid-cols-1 gap-5">
    <x-ui.card :title="$canManage ? 'Acompanhamento da turma' : 'Meu progresso'">
        <p class="mt-1 text-sm text-noite/80">Acompanhe as publicações abertas, os quizzes respondidos e as reflexões enviadas. Cada indicador tem seu próprio total.</p>

        @if ($canManage)
            @if ($students->isEmpty())
                <x-ui.empty title="Sem alunos matriculados" sprite="users">O progresso aparece quando houver alunos na turma.</x-ui.empty>
            @else
                <div class="mt-4 overflow-x-auto">
                    <table class="w-full min-w-[32rem] text-left text-sm">
                        <thead class="border-b-2 border-noite/15 text-azul">
                            <tr>
                                <th class="px-2 py-3" scope="col">Aluno</th>
                                <th class="px-2 py-3" scope="col">Mural aberto</th>
                                <th class="px-2 py-3" scope="col">Quizzes respondidos</th>
                                <th class="px-2 py-3" scope="col">Reflexões enviadas</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-noite/10">
                            @foreach ($students as $student)
                                <tr wire:key="student-progress-{{ $student->id }}">
                                    <th class="min-w-40 px-2 py-3 font-semibold" scope="row">
                                        {{ $student->name }}
                                        <x-classroom-progress-meter :student="$student" :totals="$totals" />
                                    </th>
                                    <td class="px-2 py-3">{{ $student->mural_count }} / {{ $totals['mural'] }}</td>
                                    <td class="px-2 py-3">{{ $student->quiz_count }} / {{ $totals['quiz'] }}</td>
                                    <td class="px-2 py-3">{{ $student->reflection_count }} / {{ $totals['reflection'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">{{ $students->links('components.ui.pagination') }}</div>
            @endif
        @else
            <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
                @foreach (['mural' => ['Mural aberto', 'book'], 'quiz' => ['Quizzes respondidos', 'star'], 'reflection' => ['Reflexões enviadas', 'scroll']] as $metric => [$label, $icon])
                    <div class="min-w-0 border-2 border-noite/10 bg-creme/50 p-3" wire:key="progress-{{ $metric }}">
                        <x-pixel :name="$icon" class="w-8" />
                        <p class="mt-2 text-sm font-semibold">{{ $label }}</p>
                        <p class="font-marca text-2xl font-bold text-azul">{{ $personal->{$metric.'_count'} }} <span class="text-sm font-semibold">de {{ $totals[$metric] }}</span></p>
                        <progress class="h-2 w-full accent-azul" value="{{ $personal->{$metric.'_count'} }}" max="{{ max(1, $totals[$metric]) }}" aria-label="{{ $label }}"></progress>
                    </div>
                @endforeach
            </div>
            <div class="mt-4 flex flex-wrap gap-2">
                <x-ui.button variant="secondary" :href="route('classrooms.show', $classroom)">Ver mural</x-ui.button>
                <x-ui.button variant="secondary" :href="route('classrooms.show', ['classroom' => $classroom, 'aba' => 'quizzes'])">Abrir quizzes</x-ui.button>
            </div>
        @endif
    </x-ui.card>

    @if ($notes)
        <x-ui.card title="Meus resumos">
            @forelse ($notes as $note)
                <article class="mt-3 border-t border-noite/10 pt-3" wire:key="summary-{{ $note->id }}">
                    <h3 class="wrap-anywhere font-semibold text-azul">{{ $note->title }}</h3>
                    <p class="mt-1 whitespace-pre-wrap wrap-anywhere text-sm">{{ $note->summary }}</p>
                </article>
            @empty
                <x-ui.empty title="Seu caderno começa aqui" sprite="scroll">Os resumos que você enviar nas reflexões do mural ficam guardados nesta aba.</x-ui.empty>
            @endforelse
            {{ $notes->links('components.ui.pagination') }}
        </x-ui.card>
    @endif

    @if ($games->isNotEmpty())
        <x-ui.card title="Partidas ao vivo recentes">
            <ul class="mt-2 divide-y divide-noite/10">
                @foreach ($games as $game)
                    <li class="flex flex-wrap items-center justify-between gap-3 py-3" wire:key="personal-game-{{ $game->id }}">
                        <div class="min-w-0">
                            <p class="wrap-anywhere font-semibold text-azul">{{ $game->title }}</p>
                            <p class="text-sm">{{ $game->points }} pontos na partida</p>
                        </div>
                        <x-ui.button variant="secondary" :href="route('quizzes.live', $game->id)">Ver resultado</x-ui.button>
                    </li>
                @endforeach
            </ul>
        </x-ui.card>
    @endif
</div>
