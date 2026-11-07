@props(['href', 'active' => false, 'sprite'])

<a class="nav-item" href="{{ $href }}" wire:navigate @if ($active) aria-current="page" @endif>
    <x-pixel :name="$sprite" class="size-5 shrink-0" :palette="['w' => 'currentColor']" />
    {{ $slot }}
</a>
