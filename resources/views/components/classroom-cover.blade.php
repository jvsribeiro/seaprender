@props(['classroom'])

@php($theme = $classroom->theme())

<x-classroom-theme :theme="$theme" class="classroom-cover flex h-16 items-center justify-end px-4" aria-hidden="true">
    <x-pixel :name="$theme['sprite']" class="relative w-10" />
</x-classroom-theme>
