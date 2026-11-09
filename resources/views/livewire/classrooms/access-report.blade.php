<div class="card min-w-0">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h2 class="font-marca text-xl font-semibold text-azul">Acessos da turma</h2>
        <label class="flex items-center gap-2 text-sm font-medium">
            <input type="checkbox" class="size-4 accent-azul" wire:model.live="onlyMissing">
            Só quem não viu nada
        </label>
    </div>

    @if ($students->isEmpty())
        <x-ui.empty :title="$onlyMissing ? 'Todo mundo viu alguma coisa' : 'Ninguém matriculado ainda'" sprite="coin" />
    @else
        <div class="mt-3 overflow-x-auto">
            <table class="w-full min-w-[34rem] text-left text-sm">
                <thead>
                    <tr class="border-b-2 border-noite">
                        <th scope="col" class="py-2 pr-3 font-semibold">Aluno</th>
                        <th scope="col" class="py-2 pr-3 font-semibold">Último acesso</th>
                        <th scope="col" class="py-2 pr-3 font-semibold">Mural</th>
                        <th scope="col" class="py-2 font-semibold">Quizzes</th>
                    </tr>
                </thead>
                <tbody class="divide-y-2 divide-noite/10">
                    @foreach ($students as $student)
                        <tr wire:key="student-{{ $student->id }}">
                            <td class="py-2 pr-3 font-semibold">{{ $student->name }}</td>
                            <td class="py-2 pr-3">{{ $student->last_seen_at?->diffForHumans() ?? 'Nunca acessou' }}</td>
                            <td class="py-2 pr-3">{{ $student->seen_posts_count }} de {{ $postsCount }}</td>
                            <td class="py-2">{{ $student->answered_quizzes_count }} de {{ $quizzesCount }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $students->links('components.ui.pagination') }}</div>
    @endif
</div>
