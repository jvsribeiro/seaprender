<div class="grid gap-6">
    <x-ui.hero
        :title="'Olá, '.str($user->name)->before(' ').'!'"
        :subtitle="match (true) {
            $user->isAdmin() => 'Acompanhe turmas, usuários e auditoria.',
            $activeClassroomsCount === 0 && $user->isTeacher() => 'Crie a sua primeira turma para começar.',
            $activeClassroomsCount === 0 => 'Sua primeira fase é encontrar a sua turma.',
            default => $activeClassroomsCount === 1 ? 'Você tem 1 turma ativa.' : 'Você tem '.$activeClassroomsCount.' turmas ativas.',
        }"
    >
        @can('create', App\Models\Classroom::class)
            <x-ui.button :href="route('classrooms.create')">Nova turma</x-ui.button>
        @elseif ($user->isStudent())
            <x-ui.button :href="route('classrooms.browse')">Procurar turmas</x-ui.button>
        @endcan
    </x-ui.hero>

    @if ($user->isAdmin() || $user->isTeacher())
        <div class="grid gap-4 sm:grid-cols-3">
            <x-ui.stat label="Turmas ativas" :value="$activeClassroomsCount" sprite="chest" />
            <x-ui.stat label="Alunos matriculados" :value="$studentsCount" sprite="star" />
            @if ($user->isAdmin())
                <x-ui.stat label="Usuários" :value="$usersCount" sprite="trophy" />
            @endif
        </div>
    @endif

    @if ($firstSteps)
        <x-ui.quest title="Primeiros passos" :steps="$firstSteps" />
    @endif

    @if ($recentPosts->isNotEmpty())
        <x-ui.card title="Últimas do mural">
            <ul class="mt-2 divide-y-2 divide-noite/10">
                @foreach ($recentPosts as $post)
                    <li wire:key="post-{{ $post->id }}">
                        <a class="flex items-center gap-3 py-2.5" href="{{ route('classrooms.show', $post->classroom_id) }}" wire:navigate>
                            <x-pixel :name="$post->type->sprite()" class="w-7 shrink-0" />
                            <span class="min-w-0 flex-1">
                                <span class="block truncate font-semibold text-azul underline-offset-2 hover:underline">{{ $post->title }}</span>
                                <span class="block truncate text-sm">{{ $post->type->label() }} · {{ $post->classroom->name }} · {{ $post->created_at->diffForHumans() }}</span>
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </x-ui.card>
    @endif

    <section>
        <h2 class="mb-3 font-marca text-xl font-semibold text-azul">{{ $user->isAdmin() ? 'Turmas recentes' : 'Suas turmas' }}</h2>

        @if ($classrooms->isEmpty())
            <div class="card">
                <x-ui.empty title="Nenhuma turma por aqui">
                    @if ($user->isTeacher())
                        Crie a primeira para gerar o código de matrícula.
                    @elseif ($user->isStudent())
                        Depois da inscrição, ela aparece aqui.
                    @endif
                </x-ui.empty>
            </div>
        @else
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($classrooms as $classroom)
                    <x-classroom-card :classroom="$classroom" wire:key="classroom-{{ $classroom->id }}" />
                @endforeach
            </div>
        @endif
    </section>
</div>
