<div>
    <x-ui.page-header :title="$user->name" :subtitle="$user->email.' · '.$user->role->label()" :breadcrumbs="['Usuários' => route('admin.users')]" />

    @error('user')
        <p class="alert mb-5" role="alert">{{ $message }}</p>
    @enderror

    <div class="mb-6 grid gap-4 sm:grid-cols-3">
        <div class="card py-4">
            <p class="font-marca text-xl font-semibold text-azul">{{ $user->last_seen_at?->diffForHumans() ?? 'Nunca acessou' }}</p>
            <p class="mt-1 text-sm font-medium">Último acesso</p>
        </div>
        <x-ui.stat label="Publicações vistas" :value="$viewedPostsCount" sprite="star" />
        <x-ui.stat label="Quizzes respondidos" :value="$answeredQuizzesCount" sprite="trophy" />
    </div>

    <div class="grid gap-5 lg:grid-cols-[1fr_20rem]">
        <x-ui.card title="O que esta pessoa fez">
            @if ($logs->isEmpty())
                <x-ui.empty title="Nenhum registro ainda" />
            @else
                <ul class="mt-2 divide-y-2 divide-noite/10">
                    @foreach ($logs as $log)
                        <li class="flex flex-wrap items-baseline justify-between gap-x-4 py-2" wire:key="log-{{ $log->id }}">
                            <p class="min-w-0">
                                <span class="font-semibold">{{ $labels[$log->action] ?? $log->action }}</span>
                                @if ($log->metadata['title'] ?? null)
                                    <span class="text-noite/75">· {{ $log->metadata['title'] }}</span>
                                @endif
                            </p>
                            <time class="text-sm" datetime="{{ $log->created_at->toIso8601String() }}">{{ $log->created_at->format('d/m/Y H:i') }}</time>
                        </li>
                    @endforeach
                </ul>
                <div class="mt-3">{{ $logs->links('components.ui.pagination') }}</div>
            @endif
        </x-ui.card>

        <div class="grid content-start gap-5">
            <x-ui.card title="Ações">
                <div class="mt-3 grid justify-items-start gap-3">
                    <x-ui.confirm title="Gerar senha temporária?" label="Gerar senha" action="issueTemporaryPassword">
                        <x-slot:trigger>
                            <x-ui.button variant="secondary">Gerar senha temporária</x-ui.button>
                        </x-slot:trigger>
                        A senha atual para de funcionar e a pessoa sai de todos os aparelhos.
                    </x-ui.confirm>
                    <x-ui.button variant="secondary" wire:click="sendResetLink">Enviar link por e-mail</x-ui.button>
                    @unless ($user->is(auth()->user()))
                        <x-ui.confirm title="Excluir esta conta?" label="Excluir conta" action="deleteUser">
                            <x-slot:trigger>
                                <button type="button" class="btn-quiet text-sm text-erro">Excluir conta</button>
                            </x-slot:trigger>
                            Isso apaga a conta, as matrículas e as respostas de {{ $user->name }}. Não dá para desfazer.
                        </x-ui.confirm>
                    @endunless
                </div>
            </x-ui.card>

            <x-ui.card title="Turmas">
                @if ($classrooms->isEmpty() && $taughtClassrooms->isEmpty())
                    <p class="mt-2 text-sm">Nenhuma turma.</p>
                @else
                    <ul class="mt-2 grid gap-1 text-sm">
                        @foreach ($taughtClassrooms->concat($classrooms) as $classroom)
                            <li wire:key="classroom-{{ $classroom->id }}"><a class="font-medium text-azul underline underline-offset-2" href="{{ route('classrooms.show', $classroom) }}" wire:navigate>{{ $classroom->name }}</a></li>
                        @endforeach
                    </ul>
                @endif
            </x-ui.card>
        </div>
    </div>

    @if ($temporaryPassword)
        <x-ui.temporary-password :password="$temporaryPassword" :owner="$user->name" />
    @endif
</div>
