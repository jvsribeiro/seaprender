@props(['theme'])

<div
    data-classroom-pattern="{{ $theme['pattern'] }}"
    style="--classroom-base: {{ $theme['base'] }}; --classroom-accent: {{ $theme['accent'] }}"
    {{ $attributes }}
>{{ $slot }}</div>
