@props(['name'])

@php
    $initials = str($name)->explode(' ')->filter()->take(2)->map(fn (string $part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('');
@endphp

<span {{ $attributes->class('avatar') }} aria-hidden="true">{{ $initials }}</span>
