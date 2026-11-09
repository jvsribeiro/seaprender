<section class="mt-4 grid gap-3 border-2 border-noite/10 bg-creme/50 p-3 sm:p-4" @if ($isPoll && $post->closed_at === null && $post->classroom->isActive()) wire:poll.5s.visible @endif>
    @error('response')
        <p class="alert" role="alert">{{ $message }}</p>
    @enderror

    <div class="flex flex-wrap items-center justify-between gap-2">
        <x-ui.badge :tone="$post->closed_at || ! $post->classroom->isActive() ? 'muted' : 'blue'">
            {{ $post->closed_at ? 'Encerrada' : ($post->classroom->isActive() ? 'Aberta para a turma' : 'Pausada') }}
        </x-ui.badge>

        @if ($canManage && $post->closed_at === null && $post->classroom->isActive())
            <x-ui.confirm title="Encerrar esta participação?" label="Encerrar" action="close">
                <x-slot:trigger>
                    <button type="button" class="btn-quiet text-sm">Encerrar {{ $isPoll ? 'votação' : 'reflexão' }}</button>
                </x-slot:trigger>
                As respostas recebidas ficam disponíveis para consulta.
            </x-ui.confirm>
        @endif
    </div>

    @if ($isPoll)
        @php($totalVotes = $totals->sum())
        @if ($showResults)
            <p class="text-sm font-semibold" aria-live="polite">{{ $totalVotes }} {{ $totalVotes === 1 ? 'voto' : 'votos' }}</p>
        @elseif ($own)
            <p class="text-sm font-semibold" role="status">Seu voto foi registrado. O resultado aparece quando a votação for encerrada.</p>
        @elseif ($canRespond)
            <p class="text-sm">Escolha uma alternativa. Seu voto individual fica privado.</p>
        @endif

        <ul class="grid gap-2">
            @foreach ($post->poll_options as $choice => $option)
                <li class="min-w-0" wire:key="option-{{ $post->id }}-{{ $choice }}">
                    @if ($canRespond && ! $own)
                        <button type="button" class="btn-px btn-secondary w-full break-words text-left" wire:click="vote({{ $choice }})" wire:loading.attr="disabled" wire:target="vote">
                            {{ $option }}
                        </button>
                    @else
                        <div class="flex flex-wrap justify-between gap-2 text-sm">
                            <span class="break-words font-semibold">{{ $option }} @if ($own?->choice === $choice) <span class="text-azul">(seu voto)</span> @endif</span>
                            @if ($showResults)
                                <span>{{ $totals->get($choice, 0) }}</span>
                            @endif
                        </div>
                        @if ($showResults)
                            <progress class="h-2 w-full accent-azul" value="{{ $totals->get($choice, 0) }}" max="{{ max(1, $totalVotes) }}" aria-label="{{ $option }}"></progress>
                        @endif
                    @endif
                </li>
            @endforeach
        </ul>
    @elseif ($canManage)
        <p class="text-sm font-semibold">{{ $responseCount }} {{ $responseCount === 1 ? 'reflexão recebida' : 'reflexões recebidas' }}</p>
        @if ($responseCount)
            <button type="button" class="btn-quiet justify-self-start text-sm" wire:click="$toggle('showResponses')" aria-expanded="{{ $showResponses ? 'true' : 'false' }}">
                {{ $showResponses ? 'Ocultar respostas' : 'Ler respostas' }}
            </button>
        @endif
        @if ($reflections)
            @foreach ($reflections as $reflection)
                <article class="min-w-0 border-t-2 border-noite/10 pt-3 text-sm" wire:key="reflection-{{ $post->id }}-{{ $reflection->user_id }}">
                    <h3 class="font-bold text-azul">{{ $reflection->name }}</h3>
                    <dl class="mt-2 grid gap-2">
                        <div><dt class="font-semibold">Gostou de</dt><dd class="whitespace-pre-wrap break-words">{{ $reflection->liked }}</dd></div>
                        <div><dt class="font-semibold">Sugere melhorar</dt><dd class="whitespace-pre-wrap break-words">{{ $reflection->improve }}</dd></div>
                        <div><dt class="font-semibold">Resumo</dt><dd class="whitespace-pre-wrap break-words">{{ $reflection->summary }}</dd></div>
                    </dl>
                </article>
            @endforeach
            {{ $reflections->links('components.ui.pagination') }}
        @endif
    @elseif ($canRespond)
        <p class="text-sm">Só você e a equipe responsável pela turma podem ler sua reflexão.</p>
        <form wire:submit="saveReflection" class="grid gap-3">
            @foreach (['liked' => ['Do que você mais gostou na aula?', 800], 'improve' => ['O que poderia melhorar ou ficou confuso?', 800], 'summary' => ['Em poucas linhas, o que você aprendeu?', 1500]] as $field => [$label, $limit])
                <div wire:key="field-{{ $post->id }}-{{ $field }}">
                    <label class="mb-1 block text-sm font-semibold" for="{{ $field }}-{{ $post->id }}">{{ $label }}</label>
                    <textarea id="{{ $field }}-{{ $post->id }}" class="field-input" wire:model="{{ $field }}" rows="{{ $field === 'summary' ? 3 : 2 }}" maxlength="{{ $limit }}" required @error($field) aria-invalid="true" aria-describedby="{{ $field }}-{{ $post->id }}-erro" @enderror></textarea>
                    @error($field)
                        <p id="{{ $field }}-{{ $post->id }}-erro" class="text-sm text-erro">{{ $message }}</p>
                    @enderror
                </div>
            @endforeach
            <x-ui.button type="submit" class="justify-self-start">{{ $own ? 'Atualizar reflexão' : 'Enviar reflexão' }}</x-ui.button>
        </form>
        @if ($own)
            <p class="text-sm font-semibold text-azul" role="status">Reflexão enviada.</p>
        @endif
    @elseif ($own)
        <div class="text-sm">
            <p class="font-bold text-azul">Sua reflexão</p>
            <dl class="mt-2 grid gap-2">
                <div><dt class="font-semibold">O que você gostou</dt><dd class="whitespace-pre-wrap break-words">{{ $own->liked }}</dd></div>
                <div><dt class="font-semibold">O que pode melhorar</dt><dd class="whitespace-pre-wrap break-words">{{ $own->improve }}</dd></div>
                <div><dt class="font-semibold">Seu resumo</dt><dd class="whitespace-pre-wrap break-words">{{ $own->summary }}</dd></div>
            </dl>
        </div>
    @endif
</section>
