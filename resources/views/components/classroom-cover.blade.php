@props(['classroom'])

@php
    [$background, $sprite] = [
        ['bg-azul', 'star'],
        ['bg-laranja', 'chest'],
        ['bg-noite', 'trophy'],
        ['bg-azul-fundo', 'coin'],
    ][$classroom->id % 4];
@endphp

<div class="flex h-16 items-center justify-end px-4 {{ $background }}" aria-hidden="true">
    <x-pixel :name="$sprite" class="w-10" />
</div>
