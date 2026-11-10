<x-classroom-theme :theme="$quiz->classroom->theme()" class="classroom-surface grid min-w-0 grid-cols-1 gap-5">
    <div>
        <x-ui.breadcrumbs :title="$quiz->title" :items="[$quiz->classroom->name => route('classrooms.show', [$quiz->classroom, 'aba' => 'quizzes'])]" />
        <x-ui.hero :title="$quiz->title" :subtitle="$quiz->description" :pixel="false" :theme="$quiz->classroom->theme()" />
    </div>

    @if ($attempt)
        <div class="card flex items-center gap-4" role="status">
            <x-pixel name="trophy" class="w-12 shrink-0" />
            <div>
                <p class="font-pixel text-4xl font-bold leading-none text-azul">{{ $attempt->score }} de {{ $attempt->total }}</p>
                <p class="mt-1 font-medium">acertos</p>
            </div>
        </div>
    @elseif ($canManage)
        <p class="card py-3 font-medium">Esta é a visão do professor, com as respostas certas à mostra.</p>
    @endif

    <form wire:submit="submit" class="grid grid-cols-1 gap-5">
        @foreach ($questions as $question)
            @php($chosen = $attempt?->answers[$question->id] ?? null)
            <fieldset class="card min-w-0" wire:key="question-{{ $question->id }}" aria-describedby="prompt-{{ $question->id }}">
                <legend class="sr-only">Pergunta {{ $loop->iteration }}</legend>
                <p class="text-sm font-semibold text-azul" aria-hidden="true">Pergunta {{ $loop->iteration }} de {{ $loop->count }}</p>
                <p id="prompt-{{ $question->id }}" class="mt-1 whitespace-pre-line wrap-anywhere font-marca text-lg font-semibold">{{ $question->prompt }}</p>

                <div class="mt-3 grid gap-2">
                    @foreach ($question->options as $optionIndex => $option)
                        @if ($attempt || $canManage)
                            <p @class([
                                'option',
                                'option-right' => $optionIndex === $question->correct_option,
                                'option-wrong' => $attempt && $chosen === $optionIndex && $optionIndex !== $question->correct_option,
                            ])>
                                <span class="min-w-0 wrap-anywhere">{{ $option }}</span>
                                @if ($optionIndex === $question->correct_option)
                                    <span class="ml-auto shrink-0 text-sm font-semibold">Correta</span>
                                @elseif ($attempt && $chosen === $optionIndex)
                                    <span class="ml-auto shrink-0 text-sm font-semibold">Sua resposta</span>
                                @endif
                            </p>
                        @else
                            <label class="option cursor-pointer" wire:key="option-{{ $question->id }}-{{ $optionIndex }}">
                                <input type="radio" class="size-5 shrink-0 accent-azul" name="question-{{ $question->id }}" value="{{ $optionIndex }}" wire:model="answers.{{ $question->id }}" required>
                                <span class="min-w-0 wrap-anywhere">{{ $option }}</span>
                            </label>
                        @endif
                    @endforeach
                </div>

                @if (($attempt || $canManage) && $question->explanation)
                    <p class="mt-3 bg-creme p-3 text-sm">{{ $question->explanation }}</p>
                @endif
            </fieldset>
        @endforeach

        @unless ($attempt || $canManage)
            @if ($quiz->classroom->isActive())
                @error('answers')
                    <p class="alert" role="alert">{{ $message }}</p>
                @enderror
                <div><x-ui.button type="submit">Enviar respostas</x-ui.button></div>
            @else
                <p class="card py-3 font-medium" role="status">A turma está arquivada e este quiz não recebe mais respostas.</p>
            @endif
        @endunless
    </form>
</x-classroom-theme>
