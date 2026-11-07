@props(['name', 'label', 'type' => 'text', 'autocomplete' => null, 'hint' => null])

@php
    $invalid = $errors->has($name);
    $describedBy = $invalid ? "{$name}-erro" : ($hint ? "{$name}-dica" : null);
@endphp

<div>
    <label for="{{ $name }}" class="mb-1 block text-sm font-semibold">{{ $label }}</label>
    <input
        id="{{ $name }}"
        name="{{ $name }}"
        type="{{ $type }}"
        class="field-input"
        required
        @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
        @if ($type !== 'password') value="{{ old($name) }}" @endif
        @if ($invalid) aria-invalid="true" @endif
        @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
        {{ $attributes }}
    >
    @error($name)
        <p id="{{ $name }}-erro" class="mt-1 text-sm font-medium text-erro">{{ $message }}</p>
    @elseif ($hint)
        <p id="{{ $name }}-dica" class="mt-1 text-sm text-noite/70">{{ $hint }}</p>
    @enderror
</div>
