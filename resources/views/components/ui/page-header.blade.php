@props(['title', 'subtitle' => null, 'breadcrumbs' => []])

<header class="mb-6">
    @if ($breadcrumbs)
        <x-ui.breadcrumbs :title="$title" :items="$breadcrumbs" />
    @endif
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="min-w-0">
            <h1 class="title-bar wrap-anywhere font-marca text-3xl font-semibold text-azul sm:text-4xl">{{ $title }}</h1>
            @if ($subtitle)
                <p class="mt-3 text-noite/80">{{ $subtitle }}</p>
            @endif
        </div>
        @if (isset($actions))
            <div class="flex flex-wrap items-center gap-3">{{ $actions }}</div>
        @endif
    </div>
</header>
