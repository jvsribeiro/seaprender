<div class="grid grid-cols-1 gap-5">
    @error('title')
        <p class="alert" role="alert">{{ $message }}</p>
    @enderror

    @if ($canManage && $classroom->isActive())
        <div><x-ui.button :href="route('quizzes.create', $classroom)">Criar quiz</x-ui.button></div>
    @endif

    @if ($quizzes->isEmpty())
        <div class="card">
            <x-ui.empty title="Nenhum quiz ainda" sprite="star">
                @if ($canManage)
                    Crie o primeiro: pergunta, alternativas e a resposta certa.
                @else
                    Quando o professor publicar, aparece aqui.
                @endif
            </x-ui.empty>
        </div>
    @else
        @foreach ($quizzes as $quiz)
            @php($attempt = $quiz->attempts->first())
            <article class="card flex flex-wrap items-center justify-between gap-4" wire:key="quiz-{{ $quiz->id }}">
                <div class="min-w-0 flex-1 basis-56">
                    <div class="flex flex-wrap items-center gap-2">
                        @if ($canManage)
                            <x-ui.badge :tone="$quiz->isPublished() ? 'blue' : 'muted'">{{ $quiz->status->label() }}</x-ui.badge>
                        @endif
                        @if ($quiz->subject)
                            <x-ui.badge tone="muted">{{ $quiz->subject->name }}</x-ui.badge>
                        @endif
                    </div>
                    <h2 class="mt-1 wrap-anywhere font-marca text-lg font-semibold text-azul">{{ $quiz->title }}</h2>
                    <p class="text-sm">
                        {{ $quiz->questions_count }} {{ $quiz->questions_count === 1 ? 'pergunta' : 'perguntas' }}
                        @if ($canManage)
                            · {{ $quiz->answers_count }} {{ $quiz->answers_count === 1 ? 'resposta' : 'respostas' }}
                        @elseif ($attempt)
                            · Você acertou {{ $attempt->score }} de {{ $attempt->total }}
                        @endif
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    @if ($canManage)
                        @if ($quiz->isPublished())
                            <x-ui.button variant="secondary" :href="route('quizzes.results', $quiz)">Resultados</x-ui.button>
                        @elseif ($classroom->isActive())
                            <x-ui.button wire:click="publish({{ $quiz->id }})">Publicar</x-ui.button>
                        @endif
                        @if ($classroom->isActive() && $quiz->answers_count === 0)
                            <x-ui.button variant="secondary" :href="route('quizzes.edit', $quiz)">Editar</x-ui.button>
                        @endif
                        <x-ui.confirm title="Excluir este quiz?" label="Excluir" action="remove({{ $quiz->id }})">
                            <x-slot:trigger>
                                <button type="button" class="btn-quiet text-sm text-erro">Excluir<span class="sr-only"> {{ $quiz->title }}</span></button>
                            </x-slot:trigger>
                            As respostas dos alunos também são apagadas.
                        </x-ui.confirm>
                    @else
                        <x-ui.button :variant="$attempt ? 'secondary' : 'primary'" :href="route('quizzes.show', $quiz)">{{ $attempt ? 'Ver resultado' : 'Responder' }}</x-ui.button>
                    @endif
                </div>
            </article>
        @endforeach
    @endif
</div>
