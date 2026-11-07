@props(['title', 'sprite' => 'chest'])

<div {{ $attributes->class('grid justify-items-center gap-3 px-4 py-10 text-center') }}>
    <x-pixel :name="$sprite" class="w-14" />
    <p class="font-marca text-lg font-semibold text-azul">{{ $title }}</p>
    @if ($slot->isNotEmpty())
        <div class="max-w-sm">{{ $slot }}</div>
    @endif
</div>
