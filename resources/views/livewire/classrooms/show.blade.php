<x-classroom-theme :theme="$theme" class="classroom-surface">
    <x-ui.breadcrumbs :title="$classroom->name" :items="['Turmas' => route('classrooms.index')]" />

    <x-ui.hero :title="$classroom->name" :subtitle="$classroom->course.' · '.$classroom->curricular_unit" :pixel="false" :theme="$theme">
        <div class="flex flex-wrap items-center gap-3">
            @unless ($classroom->isActive())
                <x-ui.badge tone="muted">{{ $classroom->status->label() }}</x-ui.badge>
            @endunless
            @if ($canManage)
                <x-ui.button variant="secondary" :href="route('classrooms.edit', $classroom)">Editar</x-ui.button>
                <x-ui.confirm
                    :title="$classroom->isActive() ? 'Arquivar esta turma?' : 'Reativar esta turma?'"
                    :label="$classroom->isActive() ? 'Arquivar' : 'Reativar'"
                    action="toggleArchived"
                >
                    <x-slot:trigger>
                        <x-ui.button variant="secondary">{{ $classroom->isActive() ? 'Arquivar' : 'Reativar' }}</x-ui.button>
                    </x-slot:trigger>
                    {{ $classroom->isActive() ? 'Ela some da busca dos alunos e o código deixa de funcionar.' : 'Ela volta a aparecer na busca dos alunos.' }}
                </x-ui.confirm>
            @endif
        </div>
    </x-ui.hero>

    @error('classroom')
        <p class="alert mt-5" role="alert">{{ $message }}</p>
    @enderror

    <livewire:classrooms.live-notice :classroom="$classroom" :key="'live-notice-'.$classroom->id" />

    @php
        $tabIcons = ['mural' => 'book', 'progresso' => 'trophy', 'quizzes' => 'check', 'se-pergunta' => 'star', 'loja' => 'coin', 'ferramentas' => 'chest', 'alunos' => 'users', 'acessos' => 'eye', 'auditoria' => 'clipboard'];
        $showSidebar = in_array($activeTab, ['mural', 'quizzes', 'alunos'], true);
    @endphp
    <nav class="tabs mt-6 flex-wrap" aria-label="Seções da turma">
        @foreach ($tabs as $key => $label)
            <button type="button" class="tab inline-flex items-center gap-2 px-3" wire:click="$set('tab', '{{ $key }}')" wire:key="tab-{{ $key }}" @if ($activeTab === $key) aria-current="page" @endif>
                <x-pixel :name="$tabIcons[$key]" :palette="['w' => 'currentColor']" class="size-5 shrink-0" />
                {{ $label }}
                @if ($key === 'alunos' && $pendingCount > 0)
                    <span class="rounded-full bg-pessego px-2 py-0.5 text-xs font-bold text-noite" aria-label="{{ $pendingCount }} solicitações pendentes">{{ $pendingCount }}</span>
                @endif
            </button>
        @endforeach
    </nav>

    <div @class(['mt-5 grid gap-5', 'xl:grid-cols-[minmax(0,1fr)_20rem]' => $showSidebar])>
        <div class="grid min-w-0 grid-cols-1 content-start gap-5">
            @if ($activeTab === 'mural')
                @if ($canManage && $classroom->isActive())
                    <x-ui.card class="flex flex-wrap items-center justify-between gap-3 py-3">
                        <div>
                            <p class="font-marca font-semibold text-azul">Ferramentas para a aula de hoje</p>
                            <p class="text-sm">Quiz ao vivo, votação, reflexão, sorteio e grupos.</p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <x-ui.button wire:click="$set('tab', 'ferramentas')">Abrir ferramentas</x-ui.button>
                            <x-ui.button variant="secondary" wire:click="$set('tab', 'se-pergunta')">Se Pergunta</x-ui.button>
                        </div>
                    </x-ui.card>
                @endif
                <livewire:classrooms.feed :classroom="$classroom" :key="'feed-'.$classroom->id" />
            @elseif ($activeTab === 'quizzes')
                <livewire:quizzes.index :classroom="$classroom" :key="'quizzes-'.$classroom->id" />
            @elseif ($activeTab === 'se-pergunta')
                <livewire:question-games.library :classroom="$classroom" :key="'question-games-'.$classroom->id" />
            @elseif ($activeTab === 'progresso')
                <livewire:classrooms.progress :classroom="$classroom" :key="'progress-'.$classroom->id" />
            @elseif ($activeTab === 'loja')
                <livewire:classrooms.shop :classroom="$classroom" :key="'shop-'.$classroom->id" />
            @elseif ($activeTab === 'ferramentas')
                <livewire:classrooms.tools :classroom="$classroom" :key="'tools-'.$classroom->id" />
            @elseif ($activeTab === 'acessos')
                <livewire:classrooms.access-report :classroom="$classroom" :key="'access-'.$classroom->id" />
            @elseif ($activeTab === 'auditoria')
                <livewire:classrooms.audit :classroom="$classroom" :key="'audit-'.$classroom->id" />
            @else
                @if ($requests->isNotEmpty())
                    <x-ui.card title="Solicitações de inscrição">
                        <p class="mt-1 text-sm">O aluno recebe acesso ao mural e às atividades depois da aprovação.</p>
                        @unless ($classroom->isActive())
                            <p class="mt-2 text-sm">Reative a turma para analisar os pedidos.</p>
                        @endunless
                        <ul class="mt-3 divide-y divide-noite/10">
                            @foreach ($requests as $request)
                                <li class="flex flex-wrap items-center justify-between gap-3 py-3" wire:key="pending-enrollment-{{ $request->id }}">
                                    <div class="min-w-0 flex-1 basis-40">
                                        <p class="break-words font-semibold">{{ $request->user->name }}</p>
                                        <p class="break-all text-sm text-noite/75">{{ $request->user->email }}</p>
                                    </div>
                                    @if ($classroom->isActive())
                                        <div class="flex flex-wrap gap-2">
                                            <x-ui.button wire:click="reviewEnrollment({{ $request->id }}, true)" wire:loading.attr="disabled" wire:target="reviewEnrollment">Aprovar<span class="sr-only"> {{ $request->user->name }}</span></x-ui.button>
                                            <x-ui.button variant="secondary" wire:click="reviewEnrollment({{ $request->id }}, false)" wire:loading.attr="disabled" wire:target="reviewEnrollment">Recusar<span class="sr-only"> {{ $request->user->name }}</span></x-ui.button>
                                        </div>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                        {{ $requests->links('components.ui.pagination') }}
                    </x-ui.card>
                @endif
                <x-ui.card title="Alunos">
                    @if ($enrollments->isEmpty())
                        <x-ui.empty title="Ninguém matriculado ainda" sprite="coin">
                            Compartilhe o código da turma ou adicione os alunos abaixo.
                        </x-ui.empty>
                    @else
                        <ul class="mt-3 divide-y-2 divide-noite/10">
                            @foreach ($enrollments as $enrollment)
                                <li class="flex flex-wrap items-center justify-between gap-3 py-2.5" wire:key="enrollment-{{ $enrollment->id }}">
                                    <div class="flex min-w-0 flex-1 basis-48 items-center gap-3">
                                        <x-ui.avatar :name="$enrollment->user->name" />
                                        <div class="min-w-0 flex-1">
                                            <p class="truncate font-semibold">{{ $enrollment->user->name }}</p>
                                            <x-classroom-progress-meter :student="$enrollment->user" :totals="$progressTotals" />
                                            <p class="truncate text-sm text-noite/75">{{ $enrollment->user->email }}</p>
                                        </div>
                                    </div>
                                    <div class="flex shrink-0 items-center gap-4">
                                        <x-ui.confirm title="Gerar senha temporária?" label="Gerar senha" action="resetStudentPassword({{ $enrollment->id }})">
                                            <x-slot:trigger>
                                                <button type="button" class="btn-quiet text-sm">Nova senha<span class="sr-only"> para {{ $enrollment->user->name }}</span></button>
                                            </x-slot:trigger>
                                            A senha atual de {{ $enrollment->user->name }} para de funcionar.
                                        </x-ui.confirm>
                                        <x-ui.confirm title="Remover da turma?" label="Remover" action="removeStudent({{ $enrollment->id }})">
                                            <x-slot:trigger>
                                                <button type="button" class="btn-quiet text-sm text-erro">Remover<span class="sr-only"> {{ $enrollment->user->name }}</span></button>
                                            </x-slot:trigger>
                                            {{ $enrollment->user->name }} perde o acesso a esta turma.
                                        </x-ui.confirm>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                        <div class="mt-3">{{ $enrollments->links('components.ui.pagination') }}</div>
                    @endif
                </x-ui.card>

                @if ($classroom->isActive())
                    <x-ui.card title="Adicionar aluno">
                        <form wire:submit="addStudent" class="mt-3 grid gap-3 sm:grid-cols-[1fr_1fr_auto] sm:items-start">
                            <x-ui.input name="studentName" label="Nome" autocomplete="off" />
                            <x-ui.input name="studentEmail" label="E-mail" type="email" autocomplete="off" />
                            <x-ui.button type="submit" class="sm:mt-6">Adicionar</x-ui.button>
                        </form>
                    </x-ui.card>

                    <x-ui.card title="Importar CSV">
                        <p class="mt-1 text-sm">Os alunos importados já entram com a matrícula aprovada. Use as colunas <code class="font-semibold">nome</code> e <code class="font-semibold">email</code>, até 200 alunos. Quem ainda não tem conta define a senha em "Esqueci a senha".</p>
                        <form wire:submit="importStudents" class="mt-3 flex flex-wrap items-start gap-3">
                            <div class="min-w-56 flex-1">
                                <label for="csv" class="sr-only">Arquivo CSV</label>
                                <input id="csv" type="file" accept=".csv,text/csv" class="field-input" wire:model="csv">
                                <p wire:loading wire:target="csv" class="mt-1 text-sm">Enviando arquivo…</p>
                                @error('csv')
                                    <p class="mt-1 text-sm font-medium text-erro">{{ $message }}</p>
                                @enderror
                            </div>
                            <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="csv,importStudents">Importar</x-ui.button>
                        </form>

                        @if ($importResult)
                            <div class="mt-4 bg-creme p-3 text-sm" role="status">
                                <p class="font-semibold">{{ $importResult['enrolled'] }} matriculados · {{ $importResult['skipped'] }} já estavam na turma · {{ count($importResult['errors']) }} com erro</p>
                                @if ($importResult['errors'])
                                    <ul class="mt-2 grid gap-1">
                                        @foreach ($importResult['errors'] as $error)
                                            <li wire:key="import-error-{{ $loop->index }}">{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                        @endif
                    </x-ui.card>
                @endif
            @endif
        </div>

        @if ($showSidebar)
            <div class="grid content-start gap-5">
                @if ($canManage && $classroom->isActive())
                    <x-ui.card
                        title="Código da turma"
                        x-data="{
                            copied: false,
                            async copy() {
                                $refs.link.select();
                                try {
                                    await navigator.clipboard.writeText($refs.link.value);
                                } catch {
                                    document.execCommand('copy');
                                }
                                this.copied = true;
                                setTimeout(() => this.copied = false, 2000);
                            },
                        }"
                    >
                        <p class="ticket mt-3">{{ $classroom->enrollment_code }}</p>
                        <label for="link-convite" class="mt-3 block text-sm font-semibold">Link de convite</label>
                        <input id="link-convite" x-ref="link" type="text" readonly class="field-input text-sm" value="{{ route('invite', $classroom->enrollment_code) }}">
                        <div class="mt-3 flex flex-wrap items-center gap-3">
                            <x-ui.button x-on:click="copy" x-text="copied ? 'Copiado!' : 'Copiar link'">Copiar link</x-ui.button>
                            <x-ui.confirm title="Gerar um novo código?" label="Trocar código" action="regenerateCode">
                                <x-slot:trigger>
                                    <button type="button" class="btn-quiet text-sm">Trocar código</button>
                                </x-slot:trigger>
                                O código e o link atuais param de funcionar.
                            </x-ui.confirm>
                        </div>
                    </x-ui.card>
                @endif

                <x-ui.card title="Dados da turma">
                    <dl class="mt-2 grid gap-2 text-sm">
                        <div><dt class="font-semibold">Situação</dt><dd>{{ $classroom->status->label() }}</dd></div>
                        <div><dt class="font-semibold">Professor</dt><dd>{{ $classroom->teacher->name }}</dd></div>
                        <div><dt class="font-semibold">Período</dt><dd>{{ $classroom->period }}</dd></div>
                        <div><dt class="font-semibold">Tema</dt><dd>{{ $theme['label'] }}</dd></div>
                        <div><dt class="font-semibold">Datas</dt><dd>{{ $classroom->starts_on->format('d/m/Y') }} a {{ $classroom->ends_on->format('d/m/Y') }}</dd></div>
                    </dl>
                </x-ui.card>
            </div>
        @endif
    </div>

    @if ($temporaryPassword)
        <x-ui.temporary-password :password="$temporaryPassword" :owner="$temporaryPasswordOwner" />
    @endif
</x-classroom-theme>
