@props(['title', 'items'])

<nav aria-label="Trilha" class="mb-3 text-sm">
    <ol class="flex flex-wrap items-center gap-1.5">
        @foreach ($items as $label => $url)
            <li class="flex items-center gap-1.5">
                <a class="font-medium text-azul underline underline-offset-2" href="{{ $url }}" wire:navigate>{{ $label }}</a>
                <span aria-hidden="true">/</span>
            </li>
        @endforeach
        <li aria-current="page" class="max-w-full truncate">{{ $title }}</li>
    </ol>
</nav>
