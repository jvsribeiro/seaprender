@props(['title', 'subtitle' => null, 'pixel' => true])

<section class="hero">
    <x-pixel name="cloud" class="cloud absolute right-[7%] top-5 hidden w-24 opacity-80 sm:block" />
    <x-pixel name="cloud" class="cloud absolute right-[30%] top-12 hidden w-14 opacity-50 md:block" />

    <div class="relative z-10 max-w-2xl">
        <h1 @class([
            'break-words',
            'font-pixel text-4xl font-bold leading-none sm:text-5xl' => $pixel,
            'font-marca text-3xl font-semibold sm:text-4xl' => ! $pixel,
        ])>{{ $title }}</h1>
        @if ($subtitle)
            <p class="mt-2 text-lg leading-snug text-white/90">{{ $subtitle }}</p>
        @endif
        @if ($slot->isNotEmpty())
            <div class="mt-5">{{ $slot }}</div>
        @endif
    </div>

    <x-scene compact />
</section>
