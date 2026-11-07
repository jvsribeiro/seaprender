<div class="grid gap-5">
    @error('classroom')
        <p class="alert" role="alert">{{ $message }}</p>
    @enderror

    @if ($canManage && $classroom->isActive())
        @if ($composing)
            <form wire:submit="publish" class="card grid gap-4 sm:grid-cols-2">
                <h2 class="font-marca text-xl font-semibold text-azul sm:col-span-2">Nova publicação</h2>

                <div>
                    <label for="type" class="mb-1 block text-sm font-semibold">Tipo</label>
                    <select id="type" class="field-input" wire:model.live="type">
                        @foreach ($postTypes as $postType)
                            <option value="{{ $postType->value }}" wire:key="type-{{ $postType->value }}">{{ $postType->label() }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="subjectId" class="mb-1 block text-sm font-semibold">Matéria</label>
                    <select id="subjectId" class="field-input" wire:model="subjectId">
                        <option value="">Sem matéria</option>
                        @foreach ($subjects as $subject)
                            <option value="{{ $subject->id }}" wire:key="subject-option-{{ $subject->id }}">{{ $subject->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="sm:col-span-2"><x-ui.input name="title" label="Título" maxlength="160" /></div>

                <div class="sm:col-span-2">
                    <label for="body" class="mb-1 block text-sm font-semibold">Texto</label>
                    <textarea id="body" rows="4" maxlength="5000" class="field-input" wire:model="body"></textarea>
                    @error('body') <p class="mt-1 text-sm font-medium text-erro">{{ $message }}</p> @enderror
                </div>

                <x-ui.input name="url" label="Link" type="url" placeholder="https://" />

                @if (in_array($type, ['activity', 'exercise']))
                    <x-ui.input name="dueOn" label="Entrega" type="date" />
                @endif

                <div class="sm:col-span-2">
                    <label for="attachment" class="mb-1 block text-sm font-semibold">Arquivo</label>
                    <input id="attachment" type="file" class="field-input" wire:model="attachment">
                    <p wire:loading wire:target="attachment" class="mt-1 text-sm">Enviando arquivo…</p>
                    @error('attachment')
                        <p class="mt-1 text-sm font-medium text-erro">{{ $message }}</p>
                    @else
                        <p class="mt-1 text-sm text-noite/70">PDF, PowerPoint, Word, Excel, imagem ou ZIP, até 12 MB.</p>
                    @enderror
                </div>

                <div class="flex flex-wrap gap-3 sm:col-span-2">
                    <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="attachment,publish">Publicar</x-ui.button>
                    <x-ui.button variant="secondary" wire:click="$set('composing', false)">Cancelar</x-ui.button>
                </div>
            </form>
        @else
            <div><x-ui.button wire:click="$set('composing', true)">Nova publicação</x-ui.button></div>
        @endif
    @endif

    @if ($subjects->isNotEmpty() || $canManage)
        <div class="flex flex-wrap items-center gap-2" role="group" aria-label="Filtrar por matéria">
            <button type="button" class="chip" aria-pressed="{{ $filterSubject === 0 ? 'true' : 'false' }}" wire:click="$set('filterSubject', 0)">Todas</button>
            @foreach ($subjects as $subject)
                <button type="button" class="chip" aria-pressed="{{ $filterSubject === $subject->id ? 'true' : 'false' }}" wire:click="$set('filterSubject', {{ $subject->id }})" wire:key="subject-{{ $subject->id }}">{{ $subject->name }}</button>
            @endforeach
        </div>
    @endif

    @if ($canManage)
        <details class="card py-3">
            <summary class="cursor-pointer font-marca font-semibold text-azul">Organizar matérias</summary>
            <form wire:submit="addSubject" class="mt-3 flex flex-wrap items-start gap-3">
                <div class="min-w-48 flex-1"><x-ui.input name="newSubject" label="Nova matéria" maxlength="80" /></div>
                <x-ui.button type="submit" class="sm:mt-6">Adicionar</x-ui.button>
            </form>
            @if ($subjects->isNotEmpty())
                <ul class="mt-3 grid gap-1">
                    @foreach ($subjects as $subject)
                        <li class="flex items-center justify-between gap-3" wire:key="subject-row-{{ $subject->id }}">
                            {{ $subject->name }}
                            <x-ui.confirm title="Excluir esta matéria?" label="Excluir" action="removeSubject({{ $subject->id }})">
                                <x-slot:trigger>
                                    <button type="button" class="btn-quiet text-sm text-erro">Excluir<span class="sr-only"> {{ $subject->name }}</span></button>
                                </x-slot:trigger>
                                As publicações continuam no mural, só ficam sem matéria.
                            </x-ui.confirm>
                        </li>
                    @endforeach
                </ul>
            @endif
        </details>
    @endif

    @if ($posts->isEmpty())
        <div class="card">
            <x-ui.empty title="Nada no mural ainda" sprite="trophy">
                @if ($canManage)
                    Publique um aviso, uma atividade, um slide ou um link.
                @else
                    Quando o professor publicar, aparece aqui.
                @endif
            </x-ui.empty>
        </div>
    @else
        @foreach ($posts as $post)
            <article class="card" wire:key="post-{{ $post->id }}">
                <div class="flex items-start gap-3">
                    <x-pixel :name="$post->type->sprite()" class="w-9 shrink-0" />
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <x-ui.badge>{{ $post->type->label() }}</x-ui.badge>
                            @if ($post->subject)
                                <x-ui.badge tone="muted">{{ $post->subject->name }}</x-ui.badge>
                            @endif
                        </div>
                        <h2 class="mt-1 break-words font-marca text-lg font-semibold text-azul">{{ $post->title }}</h2>
                        <p class="text-sm text-noite/75">{{ $post->author?->name ?? 'Professor' }} · {{ $post->created_at->diffForHumans() }}</p>
                    </div>
                </div>

                @if ($post->body)
                    <p class="mt-3 whitespace-pre-line break-words">{{ $post->body }}</p>
                @endif

                @if ($post->due_on)
                    <p class="mt-3 text-sm font-semibold">Entrega até {{ $post->due_on->format('d/m/Y') }}</p>
                @endif

                @if ($post->url || $post->file_path || $post->quiz_id)
                    <div class="mt-3 flex flex-wrap gap-3">
                        @if ($post->quiz_id)
                            <x-ui.button :href="route('quizzes.show', $post->quiz_id)">Abrir quiz</x-ui.button>
                        @endif
                        @if ($post->url)
                            <a class="btn-px btn-sm btn-secondary" href="{{ $post->url }}" target="_blank" rel="noopener noreferrer">Abrir link<span class="sr-only"> (abre em nova aba)</span></a>
                        @endif
                        @if ($post->file_path)
                            <a class="btn-px btn-sm btn-secondary max-w-full" href="{{ route('posts.attachment', $post) }}"><span class="truncate">Baixar {{ $post->file_name }}</span></a>
                        @endif
                    </div>
                @endif

                <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t-2 border-noite/10 pt-3">
                    <button type="button" class="like" aria-pressed="{{ $post->liked ? 'true' : 'false' }}" wire:click="toggleLike({{ $post->id }})">
                        <x-pixel name="star" class="w-5" />
                        <span>Curtir</span>
                        <span class="font-bold">{{ $post->likers_count }}</span>
                    </button>

                    @if ($canManage)
                        <x-ui.confirm title="Remover esta publicação?" label="Remover" action="removePost({{ $post->id }})">
                            <x-slot:trigger>
                                <button type="button" class="btn-quiet text-sm text-erro">Remover<span class="sr-only"> {{ $post->title }}</span></button>
                            </x-slot:trigger>
                            Ela some do mural e o arquivo é apagado.
                        </x-ui.confirm>
                    @endif
                </div>

                @if ($canManage)
                    @php
                        $viewerIds = $post->viewers->pluck('id');
                        $missing = $students->whereNotIn('id', $viewerIds);
                    @endphp
                    <details class="mt-2 text-sm">
                        <summary class="cursor-pointer font-semibold">Visto por {{ $students->count() - $missing->count() }} de {{ $students->count() }}</summary>
                        @if ($missing->isEmpty())
                            <p class="mt-1">Toda a turma viu.</p>
                        @else
                            <p class="mt-1"><span class="font-semibold">Ainda não viram:</span> {{ $missing->pluck('name')->sort()->implode(', ') }}</p>
                        @endif
                    </details>
                @endif
            </article>
        @endforeach
        <div>{{ $posts->links('components.ui.pagination') }}</div>
    @endif
</div>
