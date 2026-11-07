@props(['variant' => 'primary', 'href' => null, 'type' => 'button'])

@php
    $classes = ['btn-px', 'btn-sm', 'btn-'.$variant => $variant !== 'primary'];
@endphp

@if ($href)
    <a href="{{ $href }}" wire:navigate {{ $attributes->class($classes) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->class($classes) }}>{{ $slot }}</button>
@endif
