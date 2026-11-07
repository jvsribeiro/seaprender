@props(['name', 'label', 'type' => 'text'])

<div>
    <label for="{{ $name }}" class="mb-1 block text-sm font-semibold">{{ $label }}</label>
    <input
        id="{{ $name }}"
        type="{{ $type }}"
        wire:model="{{ $name }}"
        @error($name) aria-invalid="true" aria-describedby="{{ $name }}-erro" @enderror
        {{ $attributes->class('field-input') }}
    >
    @error($name)
        <p id="{{ $name }}-erro" class="mt-1 text-sm font-medium text-erro">{{ $message }}</p>
    @enderror
</div>
