<x-classroom-theme :theme="$quiz->classroom->theme()" class="classroom-surface">
    <x-ui.page-header :title="$quiz->title" subtitle="Resultados" :breadcrumbs="[$quiz->classroom->name => route('classrooms.show', [$quiz->classroom, 'aba' => 'quizzes'])]" />

    <div class="mb-6 grid gap-4 sm:grid-cols-3">
        <x-ui.stat label="Responderam" :value="$attempts->count().' de '.$students->count()" sprite="star" />
        <x-ui.stat label="Média de acertos" :value="$attempts->isEmpty() ? '-' : round($attempts->avg('score'), 1)" sprite="trophy" />
        <x-ui.stat label="Perguntas" :value="$questions->count()" sprite="chest" />
    </div>

    <div class="grid gap-5 lg:grid-cols-2">
        <x-ui.card title="Alunos">
            @if ($students->isEmpty())
                <x-ui.empty title="Ninguém matriculado ainda" sprite="coin" />
            @else
                <ul class="mt-2 divide-y-2 divide-noite/10">
                    @foreach ($students as $student)
                        <li class="flex items-center justify-between gap-3 py-2" wire:key="student-{{ $student->id }}">
                            <span class="min-w-0 truncate font-semibold">{{ $student->name }}</span>
                            @if ($attempts->has($student->id))
                                <span class="shrink-0">{{ $attempts[$student->id]->score }} de {{ $attempts[$student->id]->total }}</span>
                            @else
                                <span class="shrink-0 text-noite/75">Não respondeu</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-ui.card>

        <x-ui.card title="Acertos por pergunta">
            <ol class="mt-2 grid gap-3">
                @foreach ($questions as $question)
                    <li wire:key="question-{{ $loop->index }}">
                        <p class="break-words font-medium">{{ $loop->iteration }}. {{ $question['prompt'] }}</p>
                        <p class="text-sm">{{ $question['hits'] }} de {{ $attempts->count() }} acertaram</p>
                    </li>
                @endforeach
            </ol>
        </x-ui.card>
    </div>
</x-classroom-theme>
