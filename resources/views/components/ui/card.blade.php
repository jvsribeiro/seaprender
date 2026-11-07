@props(['title' => null])

<section {{ $attributes->class('card') }}>
    @if ($title)
        <h2 class="font-marca text-xl font-semibold text-azul">{{ $title }}</h2>
    @endif
    {{ $slot }}
</section>
