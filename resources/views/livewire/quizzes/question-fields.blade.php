@foreach ($questions as $index => $question)
    <fieldset class="card grid gap-3" wire:key="question-{{ $index }}">
        <legend class="sr-only">Pergunta {{ $index + 1 }}</legend>
        <div class="flex items-center justify-between gap-3">
            <p class="font-marca text-lg font-semibold text-azul" aria-hidden="true">Pergunta {{ $index + 1 }}</p>
            @if (count($questions) > 1)
                <button type="button" class="btn-quiet text-sm text-erro" wire:click="removeQuestion({{ $index }})">Remover<span class="sr-only"> pergunta {{ $index + 1 }}</span></button>
            @endif
        </div>

        <div>
            <label for="prompt-{{ $index }}" class="mb-1 block text-sm font-semibold">Enunciado</label>
            <textarea id="prompt-{{ $index }}" rows="2" maxlength="1000" class="field-input" wire:model="questions.{{ $index }}.prompt"></textarea>
            @error("questions.{$index}.prompt") <p class="mt-1 text-sm font-medium text-erro">{{ $message }}</p> @enderror
        </div>

        <div class="grid gap-2">
            <p class="text-sm font-semibold">Alternativas (marque a correta)</p>
            @foreach ($question['options'] as $optionIndex => $option)
                <div wire:key="question-{{ $index }}-option-{{ $optionIndex }}">
                    <div class="flex items-center gap-2">
                        <input type="radio" class="size-5 shrink-0 accent-azul" name="correct-{{ $index }}" value="{{ $optionIndex }}" wire:model="questions.{{ $index }}.correct" aria-label="Alternativa {{ $optionIndex + 1 }} é a correta">
                        <input type="text" class="field-input py-2" maxlength="300" aria-label="Alternativa {{ $optionIndex + 1 }}" wire:model="questions.{{ $index }}.options.{{ $optionIndex }}">
                        @if (count($question['options']) > 2)
                            <button type="button" class="btn-quiet shrink-0 text-sm text-erro" wire:click="removeOption({{ $index }}, {{ $optionIndex }})">Tirar<span class="sr-only"> alternativa {{ $optionIndex + 1 }}</span></button>
                        @endif
                    </div>
                    @error("questions.{$index}.options.{$optionIndex}") <p class="mt-1 text-sm font-medium text-erro">{{ $message }}</p> @enderror
                </div>
            @endforeach
            @error("questions.{$index}.correct") <p class="text-sm font-medium text-erro">{{ $message }}</p> @enderror
            @if (count($question['options']) < $maxOptions)
                <div><button type="button" class="btn-quiet text-sm text-azul" wire:click="addOption({{ $index }})">Adicionar alternativa</button></div>
            @endif
        </div>

        <div>
            <label for="explanation-{{ $index }}" class="mb-1 block text-sm font-semibold">Explicação (aparece depois da resposta)</label>
            <input id="explanation-{{ $index }}" type="text" maxlength="1000" class="field-input" wire:model="questions.{{ $index }}.explanation">
        </div>
    </fieldset>
@endforeach

<div><x-ui.button variant="secondary" wire:click="addQuestion">Adicionar pergunta</x-ui.button></div>
